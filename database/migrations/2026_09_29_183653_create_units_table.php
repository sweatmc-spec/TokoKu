<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            // isi per unit dalam pcs. NULL = isi berbeda tiap produk (diisi saat input pembelian, mis. Box)
            $table->unsignedInteger('pcs_per_unit')->nullable();
            $table->timestamps();
        });

        // Unit mana saja yang muncul untuk tiap kategori
        Schema::create('category_unit', function (Blueprint $table) {
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->primary(['category_id', 'unit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_unit');
        Schema::dropIfExists('units');
    }
};
