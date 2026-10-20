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

/** Sheet "Mutasi Stok": semua barang pada periode terpilih. Kolom Catatan menandai barang yang saldonya tidak cocok. */
class MutasiSheet implements FromArray, WithTitle, WithEvents, WithColumnFormatting, WithColumnWidths
{
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
        $meta = $this->service->meta('Laporan Stok - Mutasi Stok');
        $tidakCocok = $this->service->tidakCocok();

        $this->rows = [
            [$meta['toko']],
            [$meta['judul']],
            ['Periode: ' . $meta['periode']],
            ['Dicetak: ' . $meta['dicetak'] . ' oleh ' . Format::amanExcel($meta['oleh'])],
            ['Filter: ' . $meta['filter']],
            ['Catatan: saldo awal = seluruh mutasi sebelum periode. Masuk = barang datang di Cek Paket; keluar = penjualan. Koreksi / pembatalan penjualan dihitung sebagai masuk.'],
            $tidakCocok > 0
                ? ["PERHATIAN: {$tidakCocok} barang punya saldo mutasi yang berbeda dengan stok aktual (lihat kolom Catatan)."]
                : [],
            [],
            ['Barang', 'Varian', 'Kategori', 'Saldo awal', 'Masuk', 'Keluar', 'Saldo akhir', 'Stok aktual', 'Catatan'],
        ];
        $this->headingRow = count($this->rows);

        $awal = 0; $masuk = 0; $keluar = 0; $akhir = 0; $aktual = 0; $ada = false;

        foreach ($this->service->semuaMutasi() as $m) {
            $ada = true;

            $this->rows[] = [
                Format::amanExcel($m->nama),
                Format::amanExcel($m->varian ?? ''),
                Format::amanExcel($m->kategori),
                $m->saldo_awal,
                $m->masuk,
                $m->keluar,
                $m->saldo_akhir,
                $m->stok_aktual,
                $m->tidak_cocok
                    ? 'Saldo mutasi ' . $m->saldo_total . ' berbeda dengan stok aktual ' . $m->stok_aktual
                    : '',
            ];

            $awal   += $m->saldo_awal;
            $masuk  += $m->masuk;
            $keluar += $m->keluar;
            $akhir  += $m->saldo_akhir;
            $aktual += $m->stok_aktual;
        }

        if (! $ada) {
            $this->rows[] = ['Tidak ada mutasi stok pada periode ini'];
        }

        $this->rows[]   = ['TOTAL', '', '', $awal, $masuk, $keluar, $akhir, $aktual, ''];
        $this->totalRow = count($this->rows);
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function title(): string
    {
        return 'Mutasi Stok';
    }

    public function columnFormats(): array
    {
        return [
            'D' => '#,##0',
            'E' => '#,##0',
            'F' => '#,##0',
            'G' => '#,##0',
            'H' => '#,##0',
        ];
    }

    public function columnWidths(): array
    {
        return ['A' => 40, 'B' => 24, 'C' => 18, 'D' => 12, 'E' => 10, 'F' => 10, 'G' => 12, 'H' => 12, 'I' => 46];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->getStyle('A1:A2')->getFont()->setBold(true);
                $sheet->getStyle('A7')->getFont()->setBold(true);

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