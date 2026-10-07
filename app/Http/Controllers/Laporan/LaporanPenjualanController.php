<?php

namespace App\Http\Controllers\Laporan;

use App\Exports\LaporanPenjualanExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\LaporanPenjualanRequest;
use App\Services\Laporan\LaporanPenjualanService;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Laporan Penjualan (read-only). Tampilan, Excel, dan PDF memakai LaporanPenjualanService
 * dan LaporanPenjualanRequest yang sama, jadi angkanya pasti identik.
 */
class LaporanPenjualanController extends Controller
{
    public function index(LaporanPenjualanRequest $request)
    {
        $service = LaporanPenjualanService::fromRequest($request);

        return view('Laporan.Penjualan.index', [
            'periode'      => $service->periode(),
            'ringkasan'    => $service->ringkasan(),
            'seri'         => $service->seri(),
            'transaksi'    => $service->transaksi(15),
            'barang'       => $service->barang(20),

            // nilai filter untuk mengisi ulang form
            'barangOptions' => $service->barangOptions(),
            'kasirOptions'  => $service->kasirOptions(),
            'barangId'      => $request->filled('barang') ? (int) $request->input('barang') : null,
            'kasirId'       => $request->filled('kasir') ? (int) $request->input('kasir') : null,
            'tab'           => $request->input('tab', 'transaksi'),
        ]);
    }

    /** Excel: sheet "Per Transaksi" dan sheet "Per Barang". */
    public function excel(LaporanPenjualanRequest $request)
    {
        $service = LaporanPenjualanService::fromRequest($request);

        return Excel::download(new LaporanPenjualanExport($service), $this->namaFile($service, 'xlsx'));
    }

    /** PDF dibuka di tab baru: kartu ringkasan + tabel Per Barang. */
    public function pdf(LaporanPenjualanRequest $request)
    {
        $service = LaporanPenjualanService::fromRequest($request);

        $pdf = Pdf::loadView('Laporan.Penjualan.pdf', [
            'meta'      => $service->meta('Laporan Penjualan'),
            'periode'   => $service->periode(),
            'ringkasan' => $service->ringkasan(),
            'barang'    => $service->semuaBarang(),
        ])->setPaper('a4', 'portrait');

        return $pdf->stream($this->namaFile($service, 'pdf'));
    }

    private function namaFile(LaporanPenjualanService $service, string $ext): string
    {
        $p = $service->periode();

        return "laporan-penjualan_{$p->fromDate()}_{$p->toDate()}.{$ext}";
    }
}
