<?php

namespace App\Http\Controllers\Laporan;

use App\Exports\LaporanKeuanganExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\LaporanKeuanganRequest;
use App\Models\KategoriPengeluaran;
use App\Services\Laporan\LaporanKeuanganService;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Laporan Keuangan (read-only). Tampilan, Excel, dan PDF memakai LaporanKeuanganService yang sama
 * dan LaporanKeuanganRequest yang sama, jadi angkanya pasti identik.
 */
class LaporanKeuanganController extends Controller
{
    public function index(LaporanKeuanganRequest $request)
    {
        $service = LaporanKeuanganService::fromRequest($request);

        return view('Laporan.Keuangan.index', [
            'periode'       => $service->periode(),
            'ringkasan'     => $service->ringkasan(),
            'perSumber'     => $service->pemasukanPerSumber(),
            'perKategori'   => $service->pengeluaranPerKategori(),
            'perBulan'      => $service->perBulan(),
            'rincian'       => $service->rincian(20),

            // nilai filter untuk mengisi ulang form
            'tahun'         => $service->tahun(),
            'tahunOptions'  => $service->tahunOptions(),
            'kategoris'     => KategoriPengeluaran::query()->orderBy('nama')->get(['id', 'nama']),
            'sumberMasuk'   => $request->input('sumber_masuk'),
            'sumberKeluar'  => $request->input('sumber_keluar'),
            'kategori'      => $request->filled('kategori') ? (int) $request->input('kategori') : null,
            'tab'           => $request->input('tab', 'ringkasan'),
        ]);
    }

    /** Excel: sheet "Ringkasan" dan sheet "Rincian Transaksi". */
    public function excel(LaporanKeuanganRequest $request)
    {
        $service = LaporanKeuanganService::fromRequest($request);

        return Excel::download(new LaporanKeuanganExport($service), $this->namaFile($service, 'xlsx'));
    }

    /** PDF dibuka di tab baru (bisa langsung dicetak atau diunduh dari penampil PDF browser). */
    public function pdf(LaporanKeuanganRequest $request)
    {
        $service = LaporanKeuanganService::fromRequest($request);

        $pdf = Pdf::loadView('Laporan.Keuangan.pdf', [
            'meta'        => $service->meta('Laporan Keuangan'),
            'periode'     => $service->periode(),
            'ringkasan'   => $service->ringkasan(),
            'perSumber'   => $service->pemasukanPerSumber(),
            'perKategori' => $service->pengeluaranPerKategori(),
            'perBulan'    => $service->perBulan(),
        ])->setPaper('a4', 'portrait');

        return $pdf->stream($this->namaFile($service, 'pdf'));
    }

    private function namaFile(LaporanKeuanganService $service, string $ext): string
    {
        $p = $service->periode();

        return "laporan-keuangan_{$p->fromDate()}_{$p->toDate()}.{$ext}";
    }
}
