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

/**
 * Sheet "Per Transaksi". Angka ditulis sebagai ANGKA (bukan teks "Rp ...") supaya bisa dijumlah di Excel.
 * Laba dikosongkan kalau ada barang di transaksi itu yang modalnya tidak diketahui.
 */
class TransaksiSheet implements FromArray, WithTitle, WithEvents, WithColumnFormatting, WithColumnWidths
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
        $meta = $this->service->meta('Laporan Penjualan - Per Transaksi');
        $r    = $this->service->ringkasan();

        $this->rows = [
            [$meta['toko']],
            [$meta['judul']],
            ['Periode: ' . $meta['periode']],
            ['Dicetak: ' . $meta['dicetak'] . ' oleh ' . Format::amanExcel($meta['oleh'])],
            ['Filter: ' . $meta['filter']],
            ['Catatan: penjualan setelah diskon. Laba = penjualan dikurangi modal (harga beli per pcs saat transaksi). Laba kosong kalau ada barang yang modalnya tidak diketahui.'],
            [],
            ['Total penjualan', $r['penjualan']],
            ['Jumlah transaksi', $r['transaksi']],
            ['Rata-rata per transaksi', $r['rata']],
            ['Total qty terjual', $r['qty']],
            ['Laba kotor (barang yang modalnya diketahui)', $r['laba']],
            ['Margin', $r['margin'] === null ? '-' : Format::porsi($r['margin'])],
            [],
            ['Kode', 'Tanggal', 'Customer', 'Kasir', 'Metode', 'Item (macam)', 'Qty', 'Total', 'Bayar', 'Kembalian', 'Modal', 'Laba'],
        ];
        $this->headingRow = count($this->rows);

        $total = 0; $bayar = 0; $kembali = 0; $modal = 0; $laba = 0; $ada = false;

        foreach ($this->service->semuaTransaksi() as $t) {
            $ada = true;
            $l   = LaporanPenjualanService::labaTransaksi($t);
            $kosong = (int) $t->modal_kosong > 0;

            $this->rows[] = [
                $t->code,
                Format::tanggal($t->sold_at) . ' ' . $t->sold_at->format('H:i'),
                Format::amanExcel($t->customer_name),
                Format::amanExcel($t->user?->name ?? '-'),
                Format::amanExcel($t->payment_method_text),
                (int) $t->items_count,
                (int) $t->qty_total,
                (int) $t->total,
                (int) $t->paid_amount,
                (int) $t->change_amount,
                $kosong ? null : (int) round((float) $t->modal_total),
                $l,
            ];

            $total   += (int) $t->total;
            $bayar   += (int) $t->paid_amount;
            $kembali += (int) $t->change_amount;
            if (! $kosong) {
                $modal += (int) round((float) $t->modal_total);
            }
            $laba += $l ?? 0;
        }

        if (! $ada) {
            $this->rows[] = ['Tidak ada transaksi pada periode ini'];
        }

        $this->rows[]   = ['TOTAL', '', '', '', '', '', '', $total, $bayar, $kembali, $modal, $laba];
        $this->totalRow = count($this->rows);
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function title(): string
    {
        return 'Per Transaksi';
    }

    public function columnFormats(): array
    {
        return [
            'B' => '#,##0',   // angka di blok ringkasan; teks tanggal di bawahnya tidak terpengaruh
            'H' => '#,##0',
            'I' => '#,##0',
            'J' => '#,##0',
            'K' => '#,##0',
            'L' => '#,##0',
        ];
    }

    public function columnWidths(): array
    {
        return ['A' => 40, 'B' => 20, 'C' => 22, 'D' => 18, 'E' => 18, 'F' => 13, 'G' => 8, 'H' => 15, 'I' => 15, 'J' => 13, 'K' => 15, 'L' => 15];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->getStyle('A1:A2')->getFont()->setBold(true);
                $sheet->getStyle('A8:A13')->getFont()->setBold(true);

                $sheet->getStyle("A{$this->headingRow}:L{$this->headingRow}")->getFont()->setBold(true);
                $sheet->getStyle("A{$this->headingRow}:L{$this->headingRow}")->getFill()
                    ->setFillType('solid')
                    ->getStartColor()->setRGB('E9ECEF');

                $sheet->getStyle("A{$this->totalRow}:L{$this->totalRow}")->getFont()->setBold(true);
                $sheet->freezePane('A' . ($this->headingRow + 1));
            },
        ];
    }
}
