<?php

namespace App\Exports;

use App\Exports\LaporanStok\MutasiSheet;
use App\Exports\LaporanStok\StokSheet;
use App\Services\Laporan\LaporanStokService;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/** Excel Laporan Stok: sheet 1 "Stok Saat Ini", sheet 2 "Mutasi Stok". */
class LaporanStokExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(private readonly LaporanStokService $service)
    {
    }

    public function sheets(): array
    {
        return [
            new StokSheet($this->service),
            new MutasiSheet($this->service),
        ];
    }
}