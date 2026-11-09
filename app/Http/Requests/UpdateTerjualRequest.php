<?php

namespace App\Http\Requests;

/**
 * Aturan sama dengan Pembayaran baru, ditambah tanggal transaksi yang boleh diubah saat edit.
 */
class UpdateTerjualRequest extends StorePembayaranRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'sold_at' => ['required', 'date'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'sold_at.required' => 'Isi tanggal transaksi.',
            'sold_at.date'     => 'Tanggal transaksi tidak valid.',
        ]);
    }
}
