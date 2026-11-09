<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi BENTUK data. Pengecekan yang bergantung pada stok & harga
 * (qty > stok, bayar < total, diskon > subtotal) ada di TerjualService, karena harus
 * dicek di dalam DB::transaction setelah baris stok dikunci supaya aman dari kasir lain.
 * Pesannya tetap muncul di field yang sama (items.N.qty, paid, discount_value).
 */
class StorePembayaranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;   // izin dijaga middleware permission di route
    }

    protected function prepareForValidation(): void
    {
        // input uang di browser diformat "1.500.000"; ambil angkanya saja
        $this->merge([
            'paid'           => preg_replace('/\D/', '', (string) $this->input('paid')),
            'discount_value' => preg_replace('/\D/', '', (string) $this->input('discount_value')) ?: '0',
        ]);
    }

    public function rules(): array
    {
        return [
            'customer_name'     => ['nullable', 'string', 'max:100'],
            'payment_method_id' => ['required', 'integer', Rule::exists('payment_methods', 'id')],
            'discount_type'     => ['nullable', Rule::in(['nominal', 'persen'])],
            'discount_value'    => ['nullable', 'integer', 'min:0', Rule::when($this->input('discount_type') === 'persen', ['max:100'])],
            'paid'              => ['required', 'integer', 'min:0'],
            'note'              => ['nullable', 'string', 'max:500'],

            'items'             => ['required', 'array', 'min:1'],
            'items.*.item'      => ['required', 'distinct', 'regex:/^[pv]:\d+$/'],
            'items.*.qty'       => ['required', 'integer', 'min:1', 'max:100000'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required'            => 'Tambahkan minimal satu barang.',
            'items.min'                 => 'Tambahkan minimal satu barang.',
            'items.*.item.required'     => 'Pilih barang untuk setiap baris.',
            'items.*.item.distinct'     => 'Barang yang sama tidak boleh dipilih dua kali. Gabungkan jumlahnya di satu baris.',
            'items.*.item.regex'        => 'Barang tidak valid.',
            'items.*.qty.required'      => 'Isi jumlah barang.',
            'items.*.qty.min'           => 'Jumlah minimal 1.',
            'payment_method_id.required' => 'Pilih metode pembayaran.',
            'payment_method_id.exists'  => 'Metode pembayaran tidak ditemukan.',
            'paid.required'             => 'Isi jumlah bayar.',
            'discount_value.max'        => 'Diskon persen maksimal 100.',
        ];
    }
}
