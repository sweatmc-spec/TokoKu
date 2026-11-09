<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Aturan lama: stok baru bertambah saat SEMUA barang paket dicentang.
     * Aturan baru: stok bertambah per barang, saat dicentang.
     *
     * Paket yang sudah selesai: stoknya sudah dihitung, tidak diapa-apakan.
     * Paket yang BELUM selesai tapi sebagian barangnya sudah dicentang: barang-barang itu belum
     * pernah masuk stok, jadi dimasukkan di sini (sekali saja) supaya cocok dengan aturan baru.
     */
    public function up(): void
    {
        $items = DB::table('purchase_items as pi')
            ->join('purchases as p', 'p.id', '=', 'pi.purchase_id')
            ->whereNotNull('pi.received_at')
            ->whereNull('p.completed_at')
            ->whereNotNull('pi.product_id')
            ->orderBy('pi.received_at')
            ->orderBy('pi.id')
            ->select('pi.*')
            ->get();

        foreach ($items as $item) {
            $qty = (int) $item->qty_pcs;

            $product = [
                'stock'            => DB::raw('stock + ' . $qty),
                'last_cost'        => $item->pcs_per_unit > 0 ? round($item->unit_price / $item->pcs_per_unit, 2) : null,
                'last_received_at' => $item->received_at,
                'updated_at'       => now(),
            ];

            if ($item->profit_harga_id) {
                $product['profit_harga_id'] = $item->profit_harga_id;
            }

            DB::table('products')->where('id', $item->product_id)->update($product);

            if ($item->product_variant_id) {
                DB::table('product_variants')->where('id', $item->product_variant_id)->update([
                    'stock'      => DB::raw('stock + ' . $qty),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // data stok hasil penyesuaian dibiarkan
    }
};
