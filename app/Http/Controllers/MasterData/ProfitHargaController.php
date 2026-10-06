<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\ProfitHarga;
use App\Models\PurchaseItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProfitHargaController extends Controller
{
    public function index()
    {
        $items = ProfitHarga::withCount('products')
            ->orderBy('harga')
            ->orderBy('code')
            ->paginate(15);

        return view('master-data.profit-harga.index', compact('items'));
    }

    public function store(Request $request): JsonResponse
    {
        ProfitHarga::create($this->validated($request));

        return $this->success('Profit Harga berhasil ditambahkan.');
    }

    public function update(Request $request, ProfitHarga $profitHarga): JsonResponse
    {
        $profitHarga->update($this->validated($request, $profitHarga));

        return $this->success('Profit Harga berhasil diperbarui.');
    }

    public function destroy(ProfitHarga $profitHarga): JsonResponse
    {
        $products = $profitHarga->products()->count();

        if ($products > 0) {
            return response()->json([
                'message' => "Profit Harga tidak bisa dihapus karena dipakai {$products} stok (produk). Ganti dulu Profit Harga produk tersebut.",
            ], 422);
        }

        $inPending = PurchaseItem::where('profit_harga_id', $profitHarga->id)
            ->whereHas('purchase', fn ($q) => $q->whereNull('completed_at'))
            ->exists();

        if ($inPending) {
            return response()->json([
                'message' => 'Profit Harga tidak bisa dihapus karena dipakai di pembelian yang belum selesai (Cek Paket).',
            ], 422);
        }

        // Riwayat pembelian yang sudah selesai tidak terpengaruh (kolomnya otomatis dikosongkan).
        $profitHarga->delete();

        return $this->success('Profit Harga berhasil dihapus.');
    }

    private function validated(Request $request, ?ProfitHarga $profit = null): array
    {
        // Input boleh berformat ("120.000" atau "120.000,00"): ambil angka sebelum koma, buang titik.
        $raw = explode(',', (string) $request->input('harga'))[0];
        $request->merge(['harga' => preg_replace('/\D/', '', $raw)]);

        $data = $request->validate([
            'harga' => ['required', 'integer', 'min:1', 'max:1000000000'],
            'code'  => ['nullable', 'string', 'max:50'],
        ], [
            'harga.required' => 'Harga wajib diisi.',
            'harga.integer'  => 'Harga harus berupa angka.',
            'harga.min'      => 'Harga minimal Rp 1.',
            'harga.max'      => 'Harga terlalu besar.',
            'code.max'       => 'Code maksimal 50 karakter.',
        ]);

        $code = trim((string) preg_replace('/\s+/u', ' ', (string) ($data['code'] ?? '')));
        $data['code']  = $code === '' ? null : $code;
        $data['harga'] = (int) $data['harga'];

        // harga + code yang sama persis tidak perlu dobel
        $duplicate = ProfitHarga::where('harga', $data['harga'])
            ->when(
                $data['code'] === null,
                fn ($q) => $q->whereNull('code'),
                fn ($q) => $q->whereRaw('LOWER(code) = ?', [mb_strtolower($data['code'])])
            )
            ->when($profit, fn ($q) => $q->where('id', '!=', $profit->id))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'harga' => 'Profit Harga dengan harga dan code yang sama sudah ada.',
            ]);
        }

        return $data;
    }

    private function success(string $message): JsonResponse
    {
        session()->flash('success', $message);

        return response()->json(['message' => $message]);
    }
}
