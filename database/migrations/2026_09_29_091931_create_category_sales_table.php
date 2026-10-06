<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pivot: satu sales bisa menjual banyak kategori,
     * satu kategori bisa dijual banyak sales.
     */
    public function up(): void
    {
        Schema::create('category_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['sales_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_sales');
    }
};
