<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    protected $fillable = ['code', 'sales_id', 'purchase_date', 'note', 'total_amount', 'completed_at'];

    protected $casts = [
        'purchase_date' => 'date',
        'completed_at'  => 'datetime',
        'total_amount'  => 'integer',
    ];

    public function sales(): BelongsTo
    {
        return $this->belongsTo(Sales::class, 'sales_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    /** Contoh: PB-20260929-0007 */
    public function makeCode(): string
    {
        return 'PB-' . $this->purchase_date->format('Ymd') . '-' . str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Status paket mengikuti barangnya: "selesai" kalau SEMUA barang sudah dicek, dan dibuka kembali
     * kalau ada yang dibatalkan. Ini hanya penanda; stok sudah bergerak per barang (PurchaseItem).
     *
     * @return string|null 'completed' (baru selesai), 'reopened' (dibuka kembali), null (tidak berubah)
     */
    public function refreshCompletion(): ?string
    {
        $items       = $this->items()->get();
        $allReceived = $items->isNotEmpty() && $items->whereNull('received_at')->isEmpty();

        if ($allReceived && ! $this->completed_at) {
            $this->update(['completed_at' => now()]);

            return 'completed';
        }

        if (! $allReceived && $this->completed_at) {
            $this->update(['completed_at' => null]);

            return 'reopened';
        }

        return null;
    }

    /**
     * Samakan daftar barang dengan hasil form (tambah / ubah / hapus).
     * Barang yang SUDAH DICEK (sudah masuk stok) dikunci: isinya tidak diubah dan tidak boleh hilang.
     *
     * @param  array<int, array<string, mixed>>  $rows  baris hasil validatePayload() (kunci 'id' = id item lama atau null)
     */
    public function syncItems(array $rows): void
    {
        $existing   = $this->items()->get()->keyBy('id');
        $payloadIds = collect($rows)->pluck('id')->filter()->map(fn ($id) => (int) $id)->all();

        $missing = $existing->filter(fn ($item) => $item->received_at && ! in_array($item->id, $payloadIds, true));

        if ($missing->isNotEmpty()) {
            abort(422, 'Barang yang sudah dicek (sudah masuk stok) tidak bisa dihapus dari pembelian. Batalkan centangnya dulu di halaman detail.');
        }

        $keep = [];

        foreach ($rows as $row) {
            $id = $row['id'];
            unset($row['id']);

            if ($id && $existing->has($id)) {
                if (! $existing[$id]->received_at) {
                    $existing[$id]->update($row);       // barang yang sudah dicek: isinya tidak diubah
                }
                $keep[] = $id;
            } else {
                $keep[] = $this->items()->create($row)->id;
            }
        }

        $this->items()->whereNotIn('id', $keep)->delete();
    }
}
