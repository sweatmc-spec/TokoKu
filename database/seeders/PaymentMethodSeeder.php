<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            [
                'name'        => 'Tunai',
                'description' => 'Pembayaran langsung secara tunai di tempat.',
                'is_active'   => true,
            ],
            [
                'name'        => 'Transfer Bank',
                'description' => 'Pembayaran melalui transfer antar bank (BCA, Mandiri, BNI, BRI).',
                'is_active'   => true,
            ],
            [
                'name'        => 'Virtual Account',
                'description' => 'Pembayaran melalui nomor virtual account bank.',
                'is_active'   => true,
            ],
            [
                'name'        => 'QRIS',
                'description' => 'Pembayaran dengan scan QR code melalui aplikasi dompet digital atau mobile banking.',
                'is_active'   => true,
            ],
            [
                'name'        => 'GoPay',
                'description' => 'Pembayaran menggunakan dompet digital GoPay.',
                'is_active'   => true,
            ],
            [
                'name'        => 'OVO',
                'description' => 'Pembayaran menggunakan dompet digital OVO.',
                'is_active'   => true,
            ],
            [
                'name'        => 'DANA',
                'description' => 'Pembayaran menggunakan dompet digital DANA.',
                'is_active'   => true,
            ],
            [
                'name'        => 'Kartu Kredit',
                'description' => 'Pembayaran menggunakan kartu kredit Visa atau Mastercard.',
                'is_active'   => true,
            ],
            [
                'name'        => 'Kartu Debit',
                'description' => 'Pembayaran menggunakan kartu debit.',
                'is_active'   => true,
            ],
            [
                'name'        => 'COD (Bayar di Tempat)',
                'description' => 'Pembayaran saat barang diterima. Sementara dinonaktifkan.',
                'is_active'   => false,
            ],
        ];

        foreach ($methods as $method) {
            PaymentMethod::updateOrCreate(
                ['name' => $method['name']],
                $method
            );
        }
    }
}