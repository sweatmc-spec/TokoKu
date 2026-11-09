<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kategori_pengeluarans', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100)->unique();
            // true = kategori bawaan sistem ("Kulakan"): tidak bisa diubah / dihapus,
            // dan tidak muncul di form Pengeluaran manual (diisi otomatis dari Cek Paket).
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        // Dibuat di migration (bukan seeder) karena "Kulakan" WAJIB ada supaya Cek Paket bisa mencatat pengeluaran.
        $now = now();

        DB::table('kategori_pengeluarans')->insert([
            ['nama' => 'Kulakan', 'is_system' => true,  'created_at' => $now, 'updated_at' => $now],
            ['nama' => 'Listrik', 'is_system' => false, 'created_at' => $now, 'updated_at' => $now],
            ['nama' => 'Gaji',    'is_system' => false, 'created_at' => $now, 'updated_at' => $now],
            ['nama' => 'Plastik', 'is_system' => false, 'created_at' => $now, 'updated_at' => $now],
            ['nama' => 'Ongkir',  'is_system' => false, 'created_at' => $now, 'updated_at' => $now],
            ['nama' => 'Lainnya', 'is_system' => false, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('kategori_pengeluarans');
    }
};
