<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\PurchaseItem;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UnitController extends Controller
{
    public function index()
    {
        $units = Unit::with('categories:id,name')
            ->get()
            ->sortBy(fn ($u) => [$u->pcs_per_unit ?? PHP_INT_MAX, $u->name])
            ->values();

        $categories = Category::orderBy('name')->get(['id', 'name']);

        return view('master-data.units.index', compact('units', 'categories'));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($data) {
            $unit = Unit::create([
                'name'         => $data['name'],
                'pcs_per_unit' => $data['pcs_per_unit'],
            ]);
            $unit->categories()->sync($data['category_ids']);
        });

        return $this->success('Unit berhasil ditambahkan.');
    }

    public function update(Request $request, Unit $unit): JsonResponse
    {
        $data = $this->validated($request, $unit);

        // Mengubah isi per unit saat unit dipakai pembelian yang belum selesai akan
        // mengubah jumlah stok yang akan masuk, jadi ditahan dulu.
        if ($data['pcs_per_unit'] !== $unit->pcs_per_unit && $this->usedInPendingPurchase($unit)) {
            return response()->json([
                'message' => 'Isi per unit tidak bisa diubah karena unit ini dipakai di pembelian yang belum selesai. Selesaikan atau ubah pembelian tersebut dulu.',
            ], 422);
        }

        DB::transaction(function () use ($data, $unit) {
            $unit->update([
                'name'         => $data['name'],
                'pcs_per_unit' => $data['pcs_per_unit'],
            ]);
            $unit->categories()->sync($data['category_ids']);
        });

        return $this->success('Unit berhasil diperbarui.');
    }

    public function destroy(Unit $unit): JsonResponse
    {
        if ($this->usedInPendingPurchase($unit)) {
            return response()->json([
                'message' => 'Unit tidak bisa dihapus karena dipakai di pembelian yang belum selesai.',
            ], 422);
        }

        // Riwayat pembelian yang sudah selesai tetap aman: nama unit tersimpan di item (unit_name).
        $unit->delete();

        return $this->success('Unit berhasil dihapus.');
    }

    private function usedInPendingPurchase(Unit $unit): bool
    {
        return PurchaseItem::where('unit_id', $unit->id)
            ->whereHas('purchase', fn ($q) => $q->whereNull('completed_at'))
            ->exists();
    }

    private function validated(Request $request, ?Unit $unit = null): array
    {
        $isManual = $request->boolean('is_manual');

        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:50',
                Rule::unique('units', 'name')->ignore($unit?->id),
            ],
            'pcs_per_unit'   => $isManual ? ['nullable'] : ['required', 'integer', 'min:1', 'max:10000'],
            'category_ids'   => ['required', 'array', 'min:1'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
        ], [
            'name.required'          => 'Nama unit wajib diisi.',
            'name.unique'            => 'Nama unit sudah ada.',
            'name.max'               => 'Nama unit maksimal 50 karakter.',
            'pcs_per_unit.required'  => 'Isi per unit wajib diisi, atau centang "isi berbeda tiap produk".',
            'pcs_per_unit.integer'   => 'Isi per unit harus berupa angka.',
            'pcs_per_unit.min'       => 'Isi per unit minimal 1 pcs.',
            'pcs_per_unit.max'       => 'Isi per unit maksimal 10.000 pcs.',
            'category_ids.required'  => 'Pilih minimal satu kategori.',
            'category_ids.min'       => 'Pilih minimal satu kategori.',
            'category_ids.*.exists'  => 'Kategori yang dipilih tidak valid.',
        ]);

        $data['pcs_per_unit'] = $isManual ? null : (int) $data['pcs_per_unit'];

        return $data;
    }

    private function success(string $message): JsonResponse
    {
        session()->flash('success', $message);

        return response()->json(['message' => $message]);
    }
}
