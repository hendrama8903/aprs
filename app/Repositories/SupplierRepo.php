<?php

namespace App\Repositories;

use App\Models\Supplier;
use App\Models\SupplierAddress;
use App\Support\DBResult;
use Illuminate\Support\Facades\DB;

/**
 * Supplier master. Bisa dikelola manual (add/edit/delete); upload price master / PO OGS
 * juga meng-upsert kode & nama supplier (alamat dan status tidak disentuh import).
 *
 * Alamat surat (supplier_addresses) disimpan bersama supplier dalam satu Save: daftar dari form
 * menggantikan daftar lama (baris dengan AddressId di-update, tanpa AddressId ditambah, sisanya dihapus).
 * Dokumen APR menyimpan snapshot alamat, jadi menghapus alamat tidak mengubah surat yang sudah ada.
 */
class SupplierRepo
{
    /** @return object[] */
    public function search(?string $keyword, ?bool $isActive): array
    {
        return $this->query()
            ->when($keyword, fn ($q) => $q->where(fn ($q2) => $q2
                ->where('supplier_code', 'like', "%{$keyword}%")
                ->orWhere('supplier_name', 'like', "%{$keyword}%")))
            ->when($isActive !== null, fn ($q) => $q->where('is_active', $isActive))
            ->orderBy('supplier_name')
            ->get()
            ->map(fn (Supplier $s) => $this->mapSupplier($s))
            ->all();
    }

    public function getByKey(int $supplierId): ?object
    {
        $supplier = $this->query()->find($supplierId);
        if ($supplier === null) {
            return null;
        }

        $data = $this->mapSupplier($supplier);
        // Query baru: relasi yang di-eager-load di query() hanya berisi alamat default.
        $data->Addresses = $supplier->addresses()->get()->map(fn (SupplierAddress $a) => $this->mapAddress($a))->all();

        return $data;
    }

    /** Alamat aktif satu supplier (default di urutan pertama), untuk dropdown di form APR. */
    public function activeAddresses(int $supplierId): array
    {
        return SupplierAddress::query()
            ->where('supplier_id', $supplierId)
            ->where('is_active', true)
            ->orderByDesc('is_default')->orderBy('label')
            ->get()
            ->map(fn (SupplierAddress $a) => $this->mapAddress($a))
            ->all();
    }

    public function insert(array $data, array $addresses, string $loginUser): DBResult
    {
        $code = mb_strtoupper(trim((string) ($data['SupplierCode'] ?? '')));
        $fields = $this->fields($data);
        if ($code === '' || $fields['supplier_name'] === '') {
            return new DBResult(false, 'Supplier code and name are required.');
        }
        $addresses = $this->validateAddresses($addresses);
        if (is_string($addresses)) {
            return new DBResult(false, $addresses);
        }
        if (Supplier::query()->where('supplier_code', $code)->exists()) {
            return new DBResult(false, "Supplier code {$code} already exists.");
        }

        return DB::transaction(function () use ($code, $fields, $addresses, $loginUser) {
            $supplier = Supplier::create($fields + [
                'supplier_code' => mb_substr($code, 0, 10),
                'created_by' => $loginUser,
            ]);
            $this->syncAddresses($supplier, $addresses, $loginUser);

            return new DBResult(true, 'Data saved successfully.', $supplier->supplier_id);
        });
    }

    public function update(array $data, array $addresses, string $loginUser): DBResult
    {
        $fields = $this->fields($data);
        if ($fields['supplier_name'] === '') {
            return new DBResult(false, 'Supplier name is required.');
        }
        $addresses = $this->validateAddresses($addresses);
        if (is_string($addresses)) {
            return new DBResult(false, $addresses);
        }

        $supplier = Supplier::query()->find($data['SupplierId'] ?? 0);
        if ($supplier === null) {
            return new DBResult(false, 'Supplier not found.');
        }

        return DB::transaction(function () use ($supplier, $fields, $addresses, $loginUser) {
            // Kode tidak ikut di-update: kode adalah kunci upsert upload OGS dan dikunci di form saat EDIT.
            $supplier->update($fields + ['updated_by' => $loginUser]);
            $this->syncAddresses($supplier, $addresses, $loginUser);

            return new DBResult(true, 'Data updated successfully.');
        });
    }

    public function delete(int $supplierId, string $loginUser): DBResult
    {
        $supplier = Supplier::query()->find($supplierId);
        if ($supplier === null) {
            return new DBResult(false, 'Supplier not found.');
        }

        $usedIn = match (true) {
            $supplier->prices()->exists() => 'price data',
            DB::table('po_transactions')->where('supplier_id', $supplierId)->exists() => 'PO transactions',
            DB::table('aprs')->where('supplier_id', $supplierId)->exists() => 'APR documents',
            default => null,
        };
        if ($usedIn !== null) {
            return new DBResult(false, "Supplier {$supplier->supplier_code} is used in {$usedIn} and cannot be deleted. Set it to Inactive instead.");
        }

        // Alamat ikut terhapus (cascade).
        $supplier->delete();

        return new DBResult(true, 'Data deleted successfully.');
    }

    /** Kolom supplier yang boleh diubah dari form (tanpa supplier_code). */
    private function fields(array $data): array
    {
        return [
            'supplier_name' => mb_substr(trim((string) ($data['SupplierName'] ?? '')), 0, 100),
            'is_active' => $data['IsActive'] ?? true,
        ];
    }

    /** @return array|string daftar alamat siap simpan, atau pesan error. */
    private function validateAddresses(array $addresses): array|string
    {
        $text = fn (array $a, string $key, int $max) => ($v = trim((string) ($a[$key] ?? ''))) === '' ? null : mb_substr($v, 0, $max);

        $result = [];
        $labels = [];
        $plants = [];
        foreach (array_values($addresses) as $i => $a) {
            $no = $i + 1;
            $row = [
                'address_id' => (int) ($a['AddressId'] ?? 0) ?: null,
                'label' => $text($a, 'Label', 50),
                'plant_code' => ($p = $text($a, 'PlantCode', 5)) === null ? null : mb_strtoupper($p),
                'address' => $text($a, 'Address', 255),
                'attention_name' => $text($a, 'AttentionName', 100),
                'attention_title' => $text($a, 'AttentionTitle', 100),
                'email' => $text($a, 'Email', 100),
                'is_default' => filter_var($a['IsDefault'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'is_active' => filter_var($a['IsActive'] ?? true, FILTER_VALIDATE_BOOLEAN),
            ];
            if ($row['label'] === null || $row['address'] === null) {
                return "Address {$no}: label and address are required.";
            }
            $key = mb_strtoupper($row['label']);
            if (isset($labels[$key])) {
                return "Address label {$row['label']} is used more than once.";
            }
            $labels[$key] = true;
            if ($row['plant_code'] !== null) {
                if (isset($plants[$row['plant_code']])) {
                    return "Plant code {$row['plant_code']} is used by more than one address.";
                }
                $plants[$row['plant_code']] = true;
            }
            if ($row['email'] !== null && filter_var($row['email'], FILTER_VALIDATE_EMAIL) === false) {
                return "Address {$row['label']}: email address is not valid.";
            }
            if ($row['is_default'] && !$row['is_active']) {
                return "Address {$row['label']}: the default address must be active.";
            }
            $result[] = $row;
        }

        // Tepat satu default di antara alamat aktif; kalau tidak ada yang ditandai, alamat aktif pertama.
        $defaults = array_keys(array_filter($result, fn ($r) => $r['is_default']));
        if (count($defaults) > 1) {
            return 'Only one address can be the default.';
        }
        if ($defaults === []) {
            foreach ($result as $i => $r) {
                if ($r['is_active']) {
                    $result[$i]['is_default'] = true;
                    break;
                }
            }
        }

        return $result;
    }

    private function syncAddresses(Supplier $supplier, array $addresses, string $loginUser): void
    {
        $existing = $supplier->addresses()->get()->keyBy('address_id');
        $keep = [];

        foreach ($addresses as $row) {
            $id = $row['address_id'];
            unset($row['address_id']);

            if ($id !== null && $existing->has($id)) {
                $existing[$id]->update($row + ['updated_by' => $loginUser]);
                $keep[] = $id;
            } else {
                $keep[] = SupplierAddress::create($row + [
                    'supplier_id' => $supplier->supplier_id,
                    'created_by' => $loginUser,
                ])->address_id;
            }
        }

        // APR yang memakai alamat yang dihapus tetap punya snapshot-nya (FK nullOnDelete).
        SupplierAddress::query()
            ->where('supplier_id', $supplier->supplier_id)
            ->whereNotIn('address_id', $keep)
            ->delete();
    }

    private function query()
    {
        return Supplier::query()
            ->with(['addresses' => fn ($q) => $q->where('is_default', true)])
            ->withCount([
                'prices as price_count',
                'prices as valid_price_count' => fn ($q) => $q->validOn(now()->toDateString()),
                'addresses as address_count',
            ]);
    }

    private function mapSupplier(Supplier $s): object
    {
        // Relasi addresses di query() hanya memuat alamat default.
        $default = $s->addresses->firstWhere('is_default', true);

        return (object) [
            'SupplierId' => $s->supplier_id,
            'SupplierCode' => $s->supplier_code,
            'SupplierName' => $s->supplier_name,
            'AddressCount' => $s->address_count ?? 0,
            'DefaultAddress' => $default?->address,
            'AttentionName' => $default?->attention_name,
            'AttentionTitle' => $default?->attention_title,
            'Email' => $default?->email,
            'PriceCount' => $s->price_count ?? 0,
            'ValidPriceCount' => $s->valid_price_count ?? 0,
            'IsActive' => $s->is_active,
            'CreatedBy' => $s->created_by,
            'CreatedDate' => $s->created_at,
            'ModifiedBy' => $s->updated_by,
            'ModifiedDate' => $s->updated_at,
        ];
    }

    private function mapAddress(SupplierAddress $a): object
    {
        return (object) [
            'AddressId' => $a->address_id,
            'Label' => $a->label,
            'PlantCode' => $a->plant_code,
            'Address' => $a->address,
            'AttentionName' => $a->attention_name,
            'AttentionTitle' => $a->attention_title,
            'Email' => $a->email,
            'IsDefault' => $a->is_default,
            'IsActive' => $a->is_active,
        ];
    }
}
