<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\KategoriPengeluaranRequest;
use App\Models\KategoriPengeluaran;
use Illuminate\Http\JsonResponse;

class KategoriPengeluaranController extends Controller
{
    public function index()
    {
        $kategoris = KategoriPengeluaran::query()
            ->withCount('pengeluarans')
            ->orderByDesc('is_system')     // "Kulakan" (bawaan) selalu di atas
            ->orderBy('nama')
            ->get();

        return view('master-data.expense-categories.index', compact('kategoris'));
    }

    public function store(KategoriPengeluaranRequest $request): JsonResponse
    {
        KategoriPengeluaran::create($request->validated());

        return $this->success('Kategori pengeluaran berhasil ditambahkan.');
    }

    public function update(KategoriPengeluaranRequest $request, KategoriPengeluaran $kategoriPengeluaran): JsonResponse
    {
        abort_if($kategoriPengeluaran->is_system, 422, 'Kategori bawaan tidak bisa diubah.');

        $kategoriPengeluaran->update($request->validated());

        return $this->success('Kategori pengeluaran berhasil diperbarui.');
    }

    public function destroy(KategoriPengeluaran $kategoriPengeluaran): JsonResponse
    {
        abort_if($kategoriPengeluaran->is_system, 422, 'Kategori bawaan tidak bisa dihapus.');

        $dipakai = $kategoriPengeluaran->pengeluarans()->count();

        abort_if(
            $dipakai > 0,
            422,
            "Kategori \"{$kategoriPengeluaran->nama}\" sudah dipakai {$dipakai} pengeluaran, jadi tidak bisa dihapus."
        );

        $kategoriPengeluaran->delete();

        return $this->success('Kategori pengeluaran berhasil dihapus.');
    }

    private function success(string $message): JsonResponse
    {
        session()->flash('success', $message);

        return response()->json(['message' => $message]);
    }
}
