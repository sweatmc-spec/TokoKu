<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KategoriPengeluaranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;   // izin dijaga middleware permission di route
    }

    public function rules(): array
    {
        // saat update, route punya parameter {kategori_pengeluaran}; saat store tidak ada
        $kategori = $this->route('kategori_pengeluaran');

        return [
            'nama' => [
                'required', 'string', 'max:100',
                Rule::unique('kategori_pengeluarans', 'nama')->ignore($kategori?->id),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'Nama kategori wajib diisi.',
            'nama.unique'   => 'Nama kategori sudah ada.',
            'nama.max'      => 'Nama kategori maksimal 100 karakter.',
        ];
    }
}
