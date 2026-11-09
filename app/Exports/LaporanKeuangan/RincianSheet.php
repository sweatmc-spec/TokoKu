<?php

namespace App\Exports\LaporanKeuangan;

use App\Services\Laporan\LaporanKeuanganService;
use App\Support\Laporan\Format;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

/** Sheet "Rincian Transaksi": semua baris pada periode terpilih (tidak dipaginasi), urut tanggal terbaru. */
class RincianSheet implements FromArray, WithTitle, WithEvents, WithColumnFormatting, WithColumnWidths
{
    /** @var list<array<int, mixed>> */
    private array $rows = [];

    private int $headingRow = 0;
    private int $totalRow   = 0;

    public function __construct(private readonly LaporanKeuanganService $service)
    {
        $this->build();
    }

    private function build(): void
    {
        $meta = $this->service->meta('Laporan Keuangan - Rincian Transaksi');

        $this->rows = [
            [$meta['toko']],
            [$meta['judul']],
            ['Periode: ' . $meta['periode']],
            ['Dicetak: ' . $meta['dicetak'] . ' oleh ' . Format::amanExcel($meta['oleh'])],
            ['Filter: ' . $meta['filter']],
            [],
            ['Tanggal', 'Jenis', 'Sumber', 'Kategori', 'Keterangan', 'Referensi', 'Masuk (Rp)', 'Keluar (Rp)'],
        ];
        $this->headingRow = count($this->rows);

        $totalMasuk  = 0;
        $totalKeluar = 0;
        $ada         = false;

        foreach ($this->service->rincianQuery()->get() as $r) {
            $ada     = true;
            $isMasuk = $r->jenis === 'masuk';

            $this->rows[] = [
                Format::tanggal($r->tanggal),
                $isMasuk ? 'Pemasukan' : 'Pengeluaran',
                Format::sumber($r->sumber),
                Format::amanExcel($r->kategori ?? '-'),
                Format::amanExcel($r->keterangan),
                $r->ref_kode ?? '',
                $isMasuk ? (int) $r->masuk : null,
                $isMasuk ? null : (int) $r->keluar,
            ];

            $totalMasuk  += (int) $r->masuk;
            $totalKeluar += (int) $r->keluar;
        }

        if (! $ada) {
            $this->rows[] = ['Tidak ada transaksi pada periode ini'];
        }

        $this->rows[]   = ['TOTAL', '', '', '', '', '', $totalMasuk, $totalKeluar];
        $this->totalRow = count($this->rows);
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function title(): string
    {
        return 'Rincian Transaksi';
    }

    public function columnFormats(): array
    {
        return [
            'G' => '#,##0',
            'H' => '#,##0',
        ];
    }

    public function columnWidths(): array
    {
        return ['A' => 14, 'B' => 13, 'C' => 14, 'D' => 18, 'E' => 46, 'F' => 24, 'G' => 16, 'H' => 16];
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

                // judul kolom tetap terlihat saat menggulir daftar yang panjang
                $sheet->freezePane('A' . ($this->headingRow + 1));
            },
        ];
    }
}
