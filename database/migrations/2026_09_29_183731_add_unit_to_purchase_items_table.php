<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Item pembelian sekarang menunjuk ke tabel units (unit_id) dan menyimpan
     * NAMA unit sebagai snapshot (unit_name). Jadi kalau unit diganti nama /
     * dihapus di Master Data, riwayat pembelian tetap tampil benar.
     * Kolom lama "unit" (kode teks dari versi config) dihapus.
     */
    public function up(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->foreignId('unit_id')->nullable()->after('product_id')->constrained('units')->nullOnDelete();
            $table->string('unit_name', 50)->nullable()->after('unit_id');
        });

        // Salin data lama (pcs / lusin / kodi / box) ke unit_name
        DB::table('purchase_items')->select('unit')->distinct()->pluck('unit')->each(function ($old) {
            DB::table('purchase_items')->where('unit', $old)->update(['unit_name' => ucfirst((string) $old)]);
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropColumn('unit');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->string('unit', 20)->default('pcs')->after('product_name');
        });

        DB::table('purchase_items')->update(['unit' => DB::raw('LOWER(unit_name)')]);

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('unit_id');
            $table->dropColumn('unit_name');
        });
    }
};
