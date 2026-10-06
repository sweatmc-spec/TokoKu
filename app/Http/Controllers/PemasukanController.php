<?php

namespace App\Http\Controllers;

use App\Http\Requests\PemasukanRequest;
use App\Models\Pemasukan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PemasukanController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'dari'   => ['nullable', 'date'],
            'sampai' => ['nullable', 'date'],
            'sumber' => ['nullable', Rule::in([Pemasukan::SUMBER_PENJUALAN, Pemasukan::SUMBER_MANUAL])],
        ]);

        $dari   = $request->query('dari');
        $sampai = $request->query('sampai');
        $sumber = $request->query('sumber');

        $filtered = Pemasukan::query()
            ->between($dari, $sampai)
            ->when($sumber, fn ($q) => $q->where('sumber', $sumber));

        $filteredTotal = (int) (clone $filtered)->sum('nominal');

        $pemasukans = $filtered
            ->with('terjual:id,code,customer_name')
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        // kartu ringkasan tidak ikut filter tabel
        $summary = Pemasukan::ringkasan();

        return view('Keuangan.Pemasukan.index', compact(
            'pemasukans', 'summary', 'filteredTotal', 'dari', 'sampai', 'sumber'
        ));
    }

    public function store(PemasukanRequest $request): JsonResponse
    {
        Pemasukan::create($request->validated() + [
            'sumber'  => Pemasukan::SUMBER_MANUAL,
            'user_id' => $request->user()->id,
        ]);

        return $this->success('Pemasukan berhasil ditambahkan.');
    }

    public function update(PemasukanRequest $request, Pemasukan $pemasukan): JsonResponse
    {
        $this->ensureManual($pemasukan);

        $pemasukan->update($request->validated());

        return $this->success('Pemasukan berhasil diperbarui.');
    }

    public function destroy(Pemasukan $pemasukan): JsonResponse
    {
        $this->ensureManual($pemasukan);

        $pemasukan->delete();

        return $this->success('Pemasukan berhasil dihapus.');
    }

    /** Pemasukan dari Penjualan dikelola lewat halaman Terjual (diedit / dihapus di sana, ini ikut otomatis). */
    private function ensureManual(Pemasukan $pemasukan): void
    {
        abort_unless(
            $pemasukan->isManual(),
            422,
            'Pemasukan dari penjualan tidak bisa diubah atau dihapus di sini. Ubah lewat halaman Terjual.'
        );
    }

    private function success(string $message): JsonResponse
    {
        session()->flash('success', $message);

        return response()->json(['message' => $message]);
    }
}
