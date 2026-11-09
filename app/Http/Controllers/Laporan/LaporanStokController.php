<?php

namespace App\Http\Controllers\Laporan;

use App\Exports\KartuStokExport;
use App\Exports\LaporanStokExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\LaporanStokRequest;
use App\Services\Laporan\LaporanStokService;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Laporan Stok (read-only). Tampilan, Excel, PDF, dan kartu stok memakai LaporanStokService
 * dan LaporanStokRequest yang sama, jadi angkanya pasti identik.
 */
class LaporanStokController extends Controller
{
    public function index(LaporanStokRequest $request)
    {
        $service = LaporanStokService::fromRequest($request);

        // kartu stok hanya dihitung kalau satu barang dipilih
        $kartu = null;
        if ($service->adaKartu() && ($info = $service->kartuInfo())) {
            $kartu = [
                'info'      => $info,
                'ringkasan' => $service->kartuRingkasan($info),
                'baris'     => $service->kartu(25),
            ];
        }

        return view('Laporan.Stok.index', [
            'periode'        => $service->periode(),
            'ringkasan'      => $service->ringkasan(),
            'stok'           => $service->stok(20),
            'stokTotal'      => $service->stokTotal(),
            'mutasi'         => $service->mutasi(20),
            'kartu'          => $kartu,

            // nilai filter untuk mengisi ulang form
            'kategoriOptions' => $service->kategoriOptions(),
            'barangOptions'   => $service->barangOptions()->groupBy('kategori'),
            'kategoriId'      => $request->filled('kategori') ? (int) $request->input('kategori') : null,
            'barangKey'       => $request->input('barang'),
            'status'          => $request->input('status'),
            'cari'            => $request->input('q'),
            'tab'             => $request->input('tab', 'stok'),
        ]);
    }

    /** Excel: sheet "Stok Saat Ini" dan sheet "Mutasi Stok". */
    public function excel(LaporanStokRequest $request)
    {
        $service = LaporanStokService::fromRequest($request);

        return Excel::download(new LaporanStokExport($service), 'laporan-stok_' . now()->format('Y-m-d') . '.xlsx');
    }

    /** PDF dibuka di tab baru: kartu ringkasan + tabel Stok Saat Ini. */
    public function pdf(LaporanStokRequest $request)
    {
        $service = LaporanStokService::fromRequest($request);

        $pdf = Pdf::loadView('Laporan.Stok.pdf', [
            'meta'      => $service->meta('Laporan Stok - Stok Saat Ini', false),
            'ringkasan' => $service->ringkasan(),
            'stok'      => $service->semuaStok(),
            'stokTotal' => $service->stokTotal(),
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('laporan-stok_' . now()->format('Y-m-d') . '.pdf');
    }

    /** Kartu stok SATU barang (Excel), memakai periode dan barang yang sedang dipilih. */
    public function kartu(LaporanStokRequest $request)
    {
        $service = LaporanStokService::fromRequest($request);

        abort_unless($service->adaKartu() && ($info = $service->kartuInfo()), 404, 'Pilih barang terlebih dahulu.');

        $p = $service->periode();

        return Excel::download(
            new KartuStokExport($service, $info),
            'kartu-stok_' . str_replace(':', '-', $info->key) . "_{$p->fromDate()}_{$p->toDate()}.xlsx"   // ":" tidak boleh di nama file
        );
    }
}