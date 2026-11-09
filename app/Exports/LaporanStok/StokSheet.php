<?php

namespace App\Exports\LaporanStok;

use App\Services\Laporan\LaporanStokService;
use App\Support\Laporan\Format;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

/**
 * Sheet "Stok Saat Ini": Habis dan Menipis paling atas. Angka ditulis sebagai ANGKA supaya bisa dijumlah di Excel.
 * Nilai stok kosong kalau barang belum punya harga beli.
 */
class StokSheet implements FromArray, WithTitle, WithEvents, WithColumnFormatting, WithColumnWidths
{
    private const STATUS = ['habis' => 'Habis', 'menipis' => 'Menipis', 'aman' => 'Aman'];

    /** @var list<array<int, mixed>> */
    private array $rows = [];

    private int $headingRow = 0;
    private int $totalRow   = 0;

    public function __construct(private readonly LaporanStokService $service)
    {
        $this->build();
    }

    private function build(): void
    {
        $meta = $this->service->meta('Laporan Stok - Stok Saat Ini', false);
        $r    = $this->service->ringkasan();
        $tot  = $this->service->stokTotal();

        $this->rows = [
            [$meta['toko']],
            [$meta['judul']],
            [$meta['periode']],
            ['Dicetak: ' . $meta['dicetak'] . ' oleh ' . Format::amanExcel($meta['oleh'])],
            ['Filter: ' . $meta['filter']],
            ['Catatan: stok dalam pcs; nilai stok = stok x harga beli terakhir per pcs. Barang tanpa harga beli tidak dihitung dalam nilai stok.'],
            [],
            ['Jumlah jenis barang', $r['jenis']],
            ['Barang menipis', $r['menipis']],
            ['Barang habis', $r['habis']],
            ['Total nilai stok', $r['nilai']],
            [],
            ['Barang', 'Varian', 'Kategori', 'Stok', 'Satuan', 'Stok minimum', 'Harga beli', 'Nilai stok', 'Status'],
        ];
        $this->headingRow = count($this->rows);

        $ada = false;

        foreach ($this->service->semuaStok() as $b) {
            $ada = true;

            $this->rows[] = [
                Format::amanExcel($b->nama),
                Format::amanExcel($b->varian ?? ''),
                Format::amanExcel($b->kategori),
                $b->stock,
                'pcs',
                $b->stok_minimum,
                $b->last_cost === null ? null : round($b->last_cost, 2),
                $b->nilai,
                self::STATUS[$b->status] ?? $b->status,
            ];
        }

        if (! $ada) {
            $this->rows[] = ['Tidak ada barang yang cocok dengan filter'];
        }

        $this->rows[]   = ['TOTAL', '', '', $tot['pcs'], 'pcs', '', '', $tot['nilai'], ''];
        $this->totalRow = count($this->rows);
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function title(): string
    {
        return 'Stok Saat Ini';
    }

    public function columnFormats(): array
    {
        return [
            'B' => '#,##0',          // angka di blok ringkasan; teks varian di bawahnya tidak terpengaruh
            'D' => '#,##0',
            'F' => '#,##0',
            'G' => '#,##0.00',
            'H' => '#,##0',
        ];
    }

    public function columnWidths(): array
    {
        return ['A' => 40, 'B' => 24, 'C' => 18, 'D' => 10, 'E' => 9, 'F' => 14, 'G' => 14, 'H' => 16, 'I' => 11];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->getStyle('A1:A2')->getFont()->setBold(true);
                $sheet->getStyle('A8:A11')->getFont()->setBold(true);

                $sheet->getStyle("A{$this->headingRow}:I{$this->headingRow}")->getFont()->setBold(true);
                $sheet->getStyle("A{$this->headingRow}:I{$this->headingRow}")->getFill()
                    ->setFillType('solid')
                    ->getStartColor()->setRGB('E9ECEF');

                $sheet->getStyle("A{$this->totalRow}:I{$this->totalRow}")->getFont()->setBold(true);
                $sheet->freezePane('A' . ($this->headingRow + 1));
            },
        ];
    }
}