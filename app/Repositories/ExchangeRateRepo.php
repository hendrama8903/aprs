<?php

namespace App\Repositories;

use App\Models\ExchangeRate;
use App\Models\ItemPrice;
use App\Support\DBResult;
use Carbon\CarbonInterface;

/**
 * Exchange rate master: kurs mata uang asing ke IDR per periode berlaku, diinput manual.
 * Status (Valid / Expired / Not yet valid) dihitung dari periode terhadap hari ini, sama seperti price master.
 */
class ExchangeRateRepo
{
    /** Mata uang asing di price master (IDR tidak perlu kurs). */
    public const Currencies = ['USD', 'JPY'];

    /** @return object[] */
    public function search(?string $currency): array
    {
        $today = now();

        return ExchangeRate::query()
            ->when($currency, fn ($q) => $q->where('currency', $currency))
            ->orderBy('currency')
            ->orderByDesc('valid_from')
            ->get()
            ->map(fn (ExchangeRate $r) => $this->mapRate($r, $today))
            ->all();
    }

    /** @return array<string, ?float> kurs tiap mata uang yang berlaku pada $date (Y-m-d); null kalau tidak ada. */
    public function ratesOn(string $date): array
    {
        $rates = ExchangeRate::query()
            ->where('valid_from', '<=', $date)->where('valid_to', '>=', $date)
            ->pluck('rate', 'currency');

        return array_combine(self::Currencies, array_map(fn ($c) => isset($rates[$c]) ? (float) $rates[$c] : null, self::Currencies));
    }

    public function getByKey(int $exchangeRateId): ?object
    {
        $rate = ExchangeRate::query()->find($exchangeRateId);
        if ($rate === null) {
            return null;
        }

        // Untuk <input type="date"> di form: string Y-m-d, bebas pergeseran zona waktu saat serialisasi JSON.
        $data = $this->mapRate($rate, now());
        $data->ValidFromValue = $rate->valid_from?->format('Y-m-d');
        $data->ValidToValue = $rate->valid_to?->format('Y-m-d');

        return $data;
    }

    public function insert(array $data, string $loginUser): DBResult
    {
        $fields = $this->fields($data);
        if ($error = $this->validate($fields, null)) {
            return new DBResult(false, $error);
        }

        $rate = ExchangeRate::create($fields + ['created_by' => $loginUser]);

        return new DBResult(true, 'Data saved successfully.', $rate->exchange_rate_id);
    }

    public function update(array $data, string $loginUser): DBResult
    {
        $rate = ExchangeRate::query()->find((int) ($data['ExchangeRateId'] ?? 0));
        if ($rate === null) {
            return new DBResult(false, 'Exchange rate not found.');
        }

        $fields = $this->fields($data);
        if ($error = $this->validate($fields, $rate->exchange_rate_id)) {
            return new DBResult(false, $error);
        }

        $rate->update($fields + ['updated_by' => $loginUser]);

        return new DBResult(true, 'Data updated successfully.');
    }

    public function delete(int $exchangeRateId, string $loginUser): DBResult
    {
        $rate = ExchangeRate::query()->find($exchangeRateId);
        if ($rate === null) {
            return new DBResult(false, 'Exchange rate not found.');
        }

        // Dokumen APR menyimpan kurs sendiri, jadi menghapus kurs di master tidak mengubah APR.
        $rate->delete();

        return new DBResult(true, 'Data deleted successfully.');
    }

    private function fields(array $data): array
    {
        $text = fn (string $key, int $max) => ($v = trim((string) ($data[$key] ?? ''))) === '' ? null : mb_substr($v, 0, $max);

        return [
            'currency' => mb_strtoupper((string) $text('Currency', 3)),
            'rate' => is_numeric($data['Rate'] ?? null) ? (float) $data['Rate'] : null,
            'valid_from' => $text('ValidFrom', 10),
            'valid_to' => $text('ValidTo', 10),
            'remark' => $text('Remark', 255),
        ];
    }

    private function validate(array $fields, ?int $exceptId): ?string
    {
        if (!in_array($fields['currency'], self::Currencies, true)) {
            return 'Please choose the currency (' . implode(', ', self::Currencies) . ').';
        }
        if ($fields['rate'] === null || $fields['rate'] <= 0) {
            return 'Rate must be greater than zero.';
        }
        foreach (['valid_from' => 'Valid from', 'valid_to' => 'Valid to'] as $key => $label) {
            if ($fields[$key] === null || \DateTime::createFromFormat('!Y-m-d', $fields[$key]) === false) {
                return "{$label} is required.";
            }
        }
        if ($fields['valid_from'] > $fields['valid_to']) {
            return 'Valid from must not be later than valid to.';
        }

        $overlap = ExchangeRate::query()
            ->where('currency', $fields['currency'])
            ->when($exceptId, fn ($q) => $q->where('exchange_rate_id', '!=', $exceptId))
            ->where('valid_from', '<=', $fields['valid_to'])
            ->where('valid_to', '>=', $fields['valid_from'])
            ->orderBy('valid_from')
            ->first();
        if ($overlap !== null) {
            return sprintf(
                'Period overlaps with another %s rate (%s - %s, rate %s). Adjust the period or edit that rate instead.',
                $overlap->currency, $overlap->valid_from->format('d-m-Y'), $overlap->valid_to->format('d-m-Y'),
                rtrim(rtrim(number_format($overlap->rate, 4, '.', ','), '0'), '.'),
            );
        }

        return null;
    }

    private function mapRate(ExchangeRate $r, CarbonInterface $today): object
    {
        $status = ItemPrice::statusOn($r->valid_from, $r->valid_to, $today);

        return (object) [
            'ExchangeRateId' => $r->exchange_rate_id,
            'Currency' => $r->currency,
            'Rate' => $r->rate,
            'ValidFrom' => $r->valid_from,
            'ValidTo' => $r->valid_to,
            'Remark' => $r->remark,
            'Status' => $status === null ? null : ItemPrice::StatusLabels[$status],
            'CreatedBy' => $r->created_by,
            'CreatedDate' => $r->created_at,
            'ModifiedBy' => $r->updated_by,
            'ModifiedDate' => $r->updated_at,
        ];
    }
}
