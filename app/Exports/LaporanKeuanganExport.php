<?php

namespace App\Exports;

use App\Exports\LaporanKeuangan\RincianSheet;
use App\Exports\LaporanKeuangan\RingkasanSheet;
use App\Services\Laporan\LaporanKeuanganService;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/** Excel Laporan Keuangan: sheet 1 "Ringkasan", sheet 2 "Rincian Transaksi". */
class LaporanKeuanganExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(private readonly LaporanKeuanganService $service)
    {
    }

    public function sheets(): array
    {
        return [
            new RingkasanSheet($this->service),
            new RincianSheet($this->service),
        ];
    }
}
