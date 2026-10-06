<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pemasukans', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal')->index();
            $table->string('sumber', 20)->index();           // penjualan | manual

            // Satu transaksi Terjual = satu baris Pemasukan (unique).
            // cascadeOnDelete: transaksi Terjual dihapus -> Pemasukannya ikut terhapus.
            $table->foreignId('terjual_id')->nullable()->unique()
                ->constrained('terjuals')->cascadeOnDelete();

            $table->string('keterangan')->nullable();
            $table->unsignedBigInteger('nominal');            // rupiah utuh, sama seperti total_price / total_amount
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pemasukans');
    }
};
