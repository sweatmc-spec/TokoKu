<?php

namespace App\Http\Controllers;

use App\Http\Requests\PengeluaranRequest;
use App\Models\KategoriPengeluaran;
use App\Models\Pengeluaran;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PengeluaranController extends Controller
{
    /**
     * Disk penyimpanan foto nota (privat, disajikan lewat route ber-permission).
     * 'local' = storage/app/private. Mau pakai MinIO seperti foto Pengajuan? Buat disk baru
     * di config/filesystems.php (mis. 'supabase_pengeluaran') lalu ganti nilai ini.
     */
    private const DISK = 'local';

    public function index(Request $request)
    {
        $request->validate([
            'dari'     => ['nullable', 'date'],
            'sampai'   => ['nullable', 'date'],
            'kategori' => ['nullable', 'integer', 'exists:kategori_pengeluarans,id'],
            'sumber'   => ['nullable', Rule::in([Pengeluaran::SUMBER_PEMBELIAN, Pengeluaran::SUMBER_OPERASIONAL])],
        ]);

        $dari     = $request->query('dari');
        $sampai   = $request->query('sampai');
        $kategori = $request->query('kategori');
        $sumber   = $request->query('sumber');

        $filtered = Pengeluaran::query()
            ->between($dari, $sampai)
            ->when($kategori, fn ($q) => $q->where('kategori_pengeluaran_id', $kategori))
            ->when($sumber, fn ($q) => $q->where('sumber', $sumber));

        $filteredTotal = (int) (clone $filtered)->sum('nominal');

        $pengeluarans = $filtered
            ->with(['kategori:id,nama', 'purchase:id,code,sales_id', 'purchase.sales:id,name'])
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        // kartu ringkasan tidak ikut filter tabel
        $summary = Pengeluaran::ringkasan();

        $bulanIni = [today()->startOfMonth()->toDateString(), today()->endOfMonth()->toDateString()];

        $kategoris = KategoriPengeluaran::query()
            ->withSum(['pengeluarans as total_bulan_ini' => fn ($q) => $q->whereBetween('tanggal', $bulanIni)], 'nominal')
            ->orderBy('nama')
            ->get();

        $perKategori = $kategoris->filter(fn ($k) => (int) $k->total_bulan_ini > 0)
            ->sortByDesc('total_bulan_ini')
            ->values();

        $kategoriManual = $kategoris->where('is_system', false)->values();   // pilihan di form tambah / edit

        return view('Keuangan.Pengeluaran.index', compact(
            'pengeluarans', 'summary', 'filteredTotal', 'kategoris', 'kategoriManual', 'perKategori',
            'dari', 'sampai', 'kategori', 'sumber'
        ));
    }

    public function store(PengeluaranRequest $request): JsonResponse
    {
        $data = $request->safe()->except(['bukti', 'hapus_bukti']);

        if ($request->hasFile('bukti')) {
            $data['bukti'] = $request->file('bukti')->store('pengeluaran/' . now()->format('Y/m'), self::DISK);
        }

        Pengeluaran::create($data + [
            'sumber'  => Pengeluaran::SUMBER_OPERASIONAL,
            'user_id' => $request->user()->id,
        ]);

        return $this->success('Pengeluaran berhasil ditambahkan.');
    }

    public function update(PengeluaranRequest $request, Pengeluaran $pengeluaran): JsonResponse
    {
        $this->ensureOperasional($pengeluaran);

        $data = $request->safe()->except(['bukti', 'hapus_bukti']);

        if ($request->hasFile('bukti')) {
            $this->deleteBukti($pengeluaran);
            $data['bukti'] = $request->file('bukti')->store('pengeluaran/' . now()->format('Y/m'), self::DISK);
        } elseif ($request->boolean('hapus_bukti')) {
            $this->deleteBukti($pengeluaran);
            $data['bukti'] = null;
        }

        $pengeluaran->update($data);

        return $this->success('Pengeluaran berhasil diperbarui.');
    }

    public function destroy(Pengeluaran $pengeluaran): JsonResponse
    {
        $this->ensureOperasional($pengeluaran);

        $this->deleteBukti($pengeluaran);
        $pengeluaran->delete();

        return $this->success('Pengeluaran berhasil dihapus.');
    }

    /**
     * Tampilkan foto nota. File disimpan privat, jadi dialirkan lewat Laravel
     * (bukan link langsung) dan hanya untuk yang punya izin lihat Pengeluaran.
     */
    public function bukti(Pengeluaran $pengeluaran): StreamedResponse
    {
        abort_if(! $pengeluaran->bukti || ! Storage::disk(self::DISK)->exists($pengeluaran->bukti), 404);

        return Storage::disk(self::DISK)->response($pengeluaran->bukti);
    }

    /** Pengeluaran dari Pembelian dikelola lewat Cek Paket (dicatat otomatis saat paket selesai). */
    private function ensureOperasional(Pengeluaran $pengeluaran): void
    {
        abort_unless(
            $pengeluaran->isOperasional(),
            422,
            'Pengeluaran dari pembelian tidak bisa diubah atau dihapus di sini. Nominalnya mengikuti Cek Paket.'
        );
    }

    private function deleteBukti(Pengeluaran $pengeluaran): void
    {
        if ($pengeluaran->bukti) {
            Storage::disk(self::DISK)->delete($pengeluaran->bukti);
        }
    }

    private function success(string $message): JsonResponse
    {
        session()->flash('success', $message);

        return response()->json(['message' => $message]);
    }
}
