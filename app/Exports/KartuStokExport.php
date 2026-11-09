<?php

namespace App\Exports;

use App\Services\Laporan\LaporanStokService;
use App\Support\Laporan\Format;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

/**
 * Kartu stok SATU barang: saldo awal periode, setiap mutasi dengan saldo berjalan, dan saldo akhir.
 * Diekspor terpisah dari Laporan Stok.
 */
class KartuStokExport implements FromArray, WithTitle, WithEvents, WithColumnFormatting, WithColumnWidths
{
    use Exportable;

    /** @var list<array<int, mixed>> */
    private array $rows = [];

    private int $headingRow = 0;
    private int $awalRow    = 0;
    private int $akhirRow   = 0;

    public function __construct(private readonly LaporanStokService $service, private readonly object $info)
    {
        $this->build();
    }

    private function build(): void
    {
        $meta = $this->service->meta('Kartu Stok');
        $r    = $this->service->kartuRingkasan($this->info);
        $nama = $this->info->nama . ($this->info->varian ? ' — ' . $this->info->varian : '');

        $this->rows = [
            [$meta['toko']],
            [$meta['judul']],
            ['Barang: ' . Format::amanExcel($nama)],
            ['Kategori: ' . Format::amanExcel($this->info->kategori)],
            ['Periode: ' . $meta['periode']],
            ['Dicetak: ' . $meta['dicetak'] . ' oleh ' . Format::amanExcel($meta['oleh'])],
            $r['cocok']
                ? []
                : ['PERHATIAN: saldo seluruh mutasi (' . $r['saldo_total'] . ') berbeda dengan stok aktual (' . $r['stok_aktual'] . '). Kemungkinan stok diubah di luar Cek Paket dan Terjual.'],
            [],
            ['Tanggal', 'Keterangan', 'Referensi', 'Oleh', 'Masuk', 'Keluar', 'Saldo'],
        ];
        $this->headingRow = count($this->rows);

        $this->rows[]   = ['', 'Saldo awal periode', '', '', null, null, $r['saldo_awal']];
        $this->awalRow  = count($this->rows);

        $ada = false;
        foreach ($this->service->kartuSemua() as $k) {
            $ada = true;
            $ket = $k->tipe_label . ($k->keterangan ? ': ' . $k->keterangan : '');

            $this->rows[] = [
                Format::tanggal($k->tanggal) . ' ' . substr((string) $k->tanggal, 11, 5),
                Format::amanExcel($ket),
                $k->ref_code ?? '',
                Format::amanExcel($k->oleh ?? ''),
                $k->masuk ?: null,
                $k->keluar ?: null,
                $k->saldo,
            ];
        }

        if (! $ada) {
            $this->rows[] = ['', 'Tidak ada mutasi pada periode ini'];
        }

        $this->rows[]   = ['', 'Saldo akhir periode', '', '', $r['masuk'], $r['keluar'], $r['saldo_akhir']];
        $this->akhirRow = count($this->rows);
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function title(): string
    {
        return 'Kartu Stok';
    }

    public function columnFormats(): array
    {
        return ['E' => '#,##0', 'F' => '#,##0', 'G' => '#,##0'];
    }

    public function columnWidths(): array
    {
        return ['A' => 22, 'B' => 52, 'C' => 22, 'D' => 18, 'E' => 10, 'F' => 10, 'G' => 12];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->getStyle('A1:A3')->getFont()->setBold(true);

                $sheet->getStyle("A{$this->headingRow}:G{$this->headingRow}")->getFont()->setBold(true);
                $sheet->getStyle("A{$this->headingRow}:G{$this->headingRow}")->getFill()
                    ->setFillType('solid')
                    ->getStartColor()->setRGB('E9ECEF');

                $sheet->getStyle("A{$this->awalRow}:G{$this->awalRow}")->getFont()->setBold(true);
                $sheet->getStyle("A{$this->akhirRow}:G{$this->akhirRow}")->getFont()->setBold(true);
                $sheet->freezePane('A' . ($this->headingRow + 1));
            },
        ];
    }
}