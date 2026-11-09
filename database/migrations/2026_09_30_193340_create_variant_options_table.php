<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Satu tabel untuk semua pilihan variasi pakaian.
     * type: color | size | material | style  (sama dengan nama kolom di product_variants)
     */
    public function up(): void
    {
        Schema::create('variant_options', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20);
            $table->string('name', 50);
            $table->string('hex', 7)->nullable();                 // kode warna, hanya untuk type = color
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['type', 'name']);
            $table->index(['type', 'sort_order']);
        });

        // Selamatkan nilai yang sudah terlanjur dipakai varian (dari versi sebelumnya yang masih ketik bebas)
        if (! Schema::hasTable('product_variants')) {
            return;
        }

        foreach (['size', 'color', 'material', 'style'] as $type) {
            $seen = [];

            $names = DB::table('product_variants')
                ->whereNotNull($type)
                ->where($type, '!=', '')
                ->distinct()
                ->pluck($type);

            foreach ($names as $name) {
                $key = mb_strtolower($name);
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;

                DB::table('variant_options')->insert([
                    'type'       => $type,
                    'name'       => $name,
                    'hex'        => null,
                    'sort_order' => count($seen),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('variant_options');
    }
};
