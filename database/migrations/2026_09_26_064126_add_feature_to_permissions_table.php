<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan kolom `feature` pada tabel permissions bawaan
 * Spatie Laravel-Permission, supaya permission bisa dikelompokkan
 * per modul (Produk, Penjualan, dll) di halaman Role & Permission.
 *
 * Jalankan migration ini SETELAH migration bawaan package
 * spatie/laravel-permission sudah dijalankan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->string('feature')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropColumn('feature');
        });
    }
};