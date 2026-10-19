<?php

namespace App\Repositories;

use App\Models\Supplier;
use App\Support\DBResult;

/**
 * Supplier dibuat otomatis dari import price master OGS (kode & nama).
 * Di APR hanya data pelengkap surat Price Confirmation (alamat, attention, email) dan status yang bisa diubah.
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

        return $supplier === null ? null : $this->mapSupplier($supplier);
    }

    public function update(array $data, string $loginUser): DBResult
    {
        $supplier = Supplier::query()->find($data['SupplierId'] ?? 0);
        if ($supplier === null) {
            return new DBResult(false, 'Supplier not found.');
        }

        $email = trim((string) ($data['Email'] ?? ''));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return new DBResult(false, 'Email address is not valid.');
        }

        // Kode & nama milik OGS, tidak diubah di sini.
        $supplier->update([
            'address' => $this->nullable($data['Address'] ?? null),
            'attention_name' => $this->nullable($data['AttentionName'] ?? null),
            'attention_title' => $this->nullable($data['AttentionTitle'] ?? null),
            'email' => $email === '' ? null : $email,
            'is_active' => $data['IsActive'] ?? true,
            'updated_by' => $loginUser,
        ]);

        return new DBResult(true, 'Data updated successfully.');
    }

    private function query()
    {
        return Supplier::query()->withCount([
            'prices as price_count',
            'prices as active_price_count' => fn ($q) => $q->where('is_active', true),
        ]);
    }

    private function nullable(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function mapSupplier(Supplier $s): object
    {
        return (object) [
            'SupplierId' => $s->supplier_id,
            'SupplierCode' => $s->supplier_code,
            'SupplierName' => $s->supplier_name,
            'Address' => $s->address,
            'AttentionName' => $s->attention_name,
            'AttentionTitle' => $s->attention_title,
            'Email' => $s->email,
            'PriceCount' => $s->price_count ?? 0,
            'ActivePriceCount' => $s->active_price_count ?? 0,
            'IsActive' => $s->is_active,
            'CreatedBy' => $s->created_by,
            'CreatedDate' => $s->created_at,
            'ModifiedBy' => $s->updated_by,
            'ModifiedDate' => $s->updated_at,
        ];
    }
}
