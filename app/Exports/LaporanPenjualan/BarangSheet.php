<?php

namespace App\Exports\LaporanPenjualan;

use App\Services\Laporan\LaporanPenjualanService;
use App\Support\Laporan\Format;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

/** Sheet "Per Barang": terbesar penjualannya dulu. Laba dan margin kosong kalau modal barang itu tidak diketahui. */
class BarangSheet implements FromArray, WithTitle, WithEvents, WithColumnFormatting, WithColumnWidths
{
    /** @var list<array<int, mixed>> */
    private array $rows = [];

    private int $headingRow = 0;
    private int $totalRow   = 0;

    public function __construct(private readonly LaporanPenjualanService $service)
    {
        $this->build();
    }

    private function build(): void
    {
        $meta = $this->service->meta('Laporan Penjualan - Per Barang');
        $r    = $this->service->ringkasan();

        $this->rows = [
            [$meta['toko']],
            [$meta['judul']],
            ['Periode: ' . $meta['periode']],
            ['Dicetak: ' . $meta['dicetak'] . ' oleh ' . Format::amanExcel($meta['oleh'])],
            ['Filter: ' . $meta['filter']],
            ['Catatan: penjualan setelah diskon (diskon dibagi ke tiap barang sesuai nilainya). Laba hanya dari baris yang modalnya diketahui.'],
            [],
            ['Barang', 'Varian', 'Qty terjual', 'Total penjualan', 'Total modal', 'Laba', 'Margin', 'Catatan'],
        ];
        $this->headingRow = count($this->rows);

        $qty = 0; $penjualan = 0; $modal = 0; $laba = 0; $ada = false;

        foreach ($this->service->semuaBarang() as $b) {
            $ada = true;

            $this->rows[] = [
                Format::amanExcel($b->nama),
                Format::amanExcel($b->varian ?? ''),
                $b->qty,
                $b->penjualan,
                $b->laba === null ? null : $b->modal_int,
                $b->laba,
                $b->margin === null ? '-' : Format::porsi($b->margin),
                $b->laba === null ? 'Modal tidak diketahui' : ($b->sebagian ? 'Sebagian transaksi tanpa modal' : ''),
            ];

            $qty       += $b->qty;
            $penjualan += $b->penjualan;
            if ($b->laba !== null) {
                $modal += $b->modal_int;
                $laba  += $b->laba;
            }
        }

        if (! $ada) {
            $this->rows[] = ['Tidak ada penjualan pada periode ini'];
        }

        $this->rows[]   = ['TOTAL', '', $qty, $penjualan, $modal, $laba, $r['margin'] === null ? '-' : Format::porsi($r['margin']), ''];
        $this->totalRow = count($this->rows);
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function title(): string
    {
        return 'Per Barang';
    }

    public function columnFormats(): array
    {
        return [
            'C' => '#,##0',
            'D' => '#,##0',
            'E' => '#,##0',
            'F' => '#,##0',
        ];
    }

    public function columnWidths(): array
    {
        return ['A' => 40, 'B' => 22, 'C' => 13, 'D' => 17, 'E' => 15, 'F' => 15, 'G' => 10, 'H' => 32];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->getStyle('A1:A2')->getFont()->setBold(true);

                $sheet->getStyle("A{$this->headingRow}:H{$this->headingRow}")->getFont()->setBold(true);
                $sheet->getStyle("A{$this->headingRow}:H{$this->headingRow}")->getFill()
                    ->setFillType('solid')
                    ->getStartColor()->setRGB('E9ECEF');

                $sheet->getStyle("A{$this->totalRow}:H{$this->totalRow}")->getFont()->setBold(true);
                $sheet->freezePane('A' . ($this->headingRow + 1));
            },
        ];
    }
}
