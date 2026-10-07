<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('terjual_items', function (Blueprint $table) {
            // Harga modal PER PCS saat transaksi, disalin dari products.last_cost (sama seperti unit_price
            // yang disalin dari Profit Harga). Null = modal tidak diketahui; laporan menampilkannya sebagai "-".
            $table->decimal('harga_modal', 15, 2)->nullable()->after('unit_price');
        });

        $this->isiDataLama();
    }

    /**
     * Isi baris Terjual lama dengan harga beli per pcs terakhir yang sudah dicentang datang
     * SEBELUM atau tepat saat transaksi itu (itu persis nilai yang akan ada di products.last_cost
     * pada waktu itu). Kalau belum ada pembelian yang bisa dipakai, harga_modal dibiarkan null.
     *
     * Aman dijalankan ulang: hanya baris yang masih null yang diisi.
     */
    private function isiDataLama(): void
    {
        DB::table('terjual_items as ti')
            ->join('terjuals as t', 't.id', '=', 'ti.terjual_id')
            ->whereNotNull('ti.product_id')
            ->whereNull('ti.harga_modal')
            ->select('ti.id as id', 'ti.product_id as product_id', 't.sold_at as sold_at')
            ->orderBy('ti.id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    $beli = DB::table('purchase_items')
                        ->where('product_id', $row->product_id)
                        ->whereNotNull('received_at')
                        ->where('received_at', '<=', $row->sold_at)
                        ->where('pcs_per_unit', '>', 0)
                        ->orderByDesc('received_at')
                        ->orderByDesc('id')
                        ->first(['unit_price', 'pcs_per_unit']);

                    if ($beli) {
                        DB::table('terjual_items')
                            ->where('id', $row->id)
                            ->update(['harga_modal' => round($beli->unit_price / $beli->pcs_per_unit, 2)]);
                    }
                }
            }, 'ti.id', 'id');
    }

    public function down(): void
    {
        Schema::table('terjual_items', function (Blueprint $table) {
            $table->dropColumn('harga_modal');
        });
    }
};
