<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Sales;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class SalesController extends Controller
{
    public function index()
    {
        $sales = Sales::with('categories:id,name')->latest()->paginate(10);
        $categories = Category::orderBy('name')->get(['id', 'name']);

        return view('master-data.sales.index', compact('sales', 'categories'));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($data) {
            $sales = Sales::create(Arr::only($data, ['name', 'phone', 'address']));
            $sales->categories()->sync($data['category_ids']);
        });

        return $this->success('Sales berhasil ditambahkan.');
    }

    public function update(Request $request, Sales $sale): JsonResponse
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($data, $sale) {
            $sale->update(Arr::only($data, ['name', 'phone', 'address']));
            $sale->categories()->sync($data['category_ids']);
        });

        return $this->success('Sales berhasil diperbarui.');
    }

    public function destroy(Sales $sale): JsonResponse
    {
        if ($sale->purchases()->exists()) {
            return response()->json([
                'message' => 'Sales tidak bisa dihapus karena sudah memiliki riwayat pembelian.',
            ], 422);
        }

        $sale->delete(); // pivot ikut terhapus (cascadeOnDelete)

        return $this->success('Sales berhasil dihapus.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name'           => ['required', 'string', 'max:100'],
            'phone'          => ['nullable', 'string', 'max:20'],
            'address'        => ['nullable', 'string', 'max:500'],
            'category_ids'   => ['required', 'array', 'min:1'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
        ], [
            'name.required'         => 'Nama sales wajib diisi.',
            'name.max'              => 'Nama sales maksimal 100 karakter.',
            'phone.max'             => 'No. telepon maksimal 20 karakter.',
            'category_ids.required' => 'Pilih minimal satu kategori.',
            'category_ids.min'      => 'Pilih minimal satu kategori.',
            'category_ids.*.exists' => 'Kategori yang dipilih tidak valid.',
        ]);
    }

    /**
     * Flash pesan ke session supaya tampil setelah halaman di-reload oleh JS.
     */
    private function success(string $message): JsonResponse
    {
        session()->flash('success', $message);

        return response()->json(['message' => $message]);
    }
}
