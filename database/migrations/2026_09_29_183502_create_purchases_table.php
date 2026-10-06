<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable()->unique();
            // restrict: sales yang sudah punya riwayat pembelian tidak boleh terhapus
            $table->foreignId('sales_id')->constrained('sales')->restrictOnDelete();
            $table->date('purchase_date');
            $table->text('note')->nullable();
            $table->unsignedBigInteger('total_amount')->default(0);
            $table->timestamp('completed_at')->nullable();   // terisi saat semua barang sudah datang
            $table->timestamps();

            $table->index(['completed_at', 'purchase_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
