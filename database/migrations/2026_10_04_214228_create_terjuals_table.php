<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('terjuals', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();                    // TRX-20261004-0001
            $table->dateTime('sold_at')->index();
            $table->string('customer_name')->default('Umum');

            // kasir yang menginput
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // metode pembayaran: relasi ke Master Data + snapshot nama
            // (nullOnDelete supaya menghapus metode di Master Data tidak error)
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();
            $table->string('payment_method_name')->nullable();

            // semua nilai uang = rupiah utuh (integer)
            $table->unsignedBigInteger('subtotal');
            $table->string('discount_type', 10)->nullable();     // nominal | persen
            $table->unsignedBigInteger('discount_value')->default(0);   // angka yang diinput (Rp atau %)
            $table->unsignedBigInteger('discount_amount')->default(0);  // hasil hitung dalam Rp
            $table->unsignedBigInteger('total');
            $table->unsignedBigInteger('paid_amount');
            $table->unsignedBigInteger('change_amount')->default(0);

            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('terjuals');
    }
};
