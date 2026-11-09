<?php

namespace App\Exports;

use App\Exports\LaporanPenjualan\BarangSheet;
use App\Exports\LaporanPenjualan\TransaksiSheet;
use App\Services\Laporan\LaporanPenjualanService;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/** Excel Laporan Penjualan: sheet 1 "Per Transaksi", sheet 2 "Per Barang". */
class LaporanPenjualanExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(private readonly LaporanPenjualanService $service)
    {
    }

    public function sheets(): array
    {
        return [
            new TransaksiSheet($this->service),
            new BarangSheet($this->service),
        ];
    }
}
