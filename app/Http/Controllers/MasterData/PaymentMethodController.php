<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentMethodController extends Controller
{
    public function index()
    {
        $paymentMethods = PaymentMethod::latest()->paginate(10);

        return view('master-data.payment-methods.index', compact('paymentMethods'));
    }

    public function store(Request $request): JsonResponse
    {
        PaymentMethod::create($this->validated($request));

        return $this->success('Metode pembayaran berhasil ditambahkan.');
    }

    public function update(Request $request, PaymentMethod $paymentMethod): JsonResponse
    {
        $paymentMethod->update($this->validated($request, $paymentMethod));

        return $this->success('Metode pembayaran berhasil diperbarui.');
    }

    public function destroy(PaymentMethod $paymentMethod): JsonResponse
    {
        $paymentMethod->delete();

        return $this->success('Metode pembayaran berhasil dihapus.');
    }

    private function validated(Request $request, ?PaymentMethod $paymentMethod = null): array
    {
        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('payment_methods', 'name')->ignore($paymentMethod?->id),
            ],
            'description' => ['nullable', 'string', 'max:255'],
        ], [
            'name.required' => 'Nama metode pembayaran wajib diisi.',
            'name.unique'   => 'Nama metode pembayaran sudah ada.',
            'name.max'      => 'Nama metode pembayaran maksimal 100 karakter.',
        ]);

        // Checkbox yang tidak dicentang tidak ikut terkirim
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    private function success(string $message): JsonResponse
    {
        session()->flash('success', $message);

        return response()->json(['message' => $message]);
    }
}
