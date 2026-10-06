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

/**
 * Sheet "Ringkasan". Angka ditulis sebagai ANGKA (bukan teks "Rp ...") supaya bisa dijumlah di Excel;
 * tampilan ribuan diatur lewat format kolom.
 */
class RingkasanSheet implements FromArray, WithTitle, WithEvents, WithColumnFormatting, WithColumnWidths
{
    /** @var list<array<int, mixed>> */
    private array $rows = [];

    /** @var list<int> nomor baris yang ditebalkan */
    private array $bold = [];

    /** @var list<int> nomor baris judul tabel (tebal + latar abu-abu) */
    private array $heading = [];

    public function __construct(private readonly LaporanKeuanganService $service)
    {
        $this->build();
    }

    private function add(array $row, ?string $gaya = null): void
    {
        $this->rows[] = $row;
        $n = count($this->rows);

        if ($gaya === 'bold') {
            $this->bold[] = $n;
        } elseif ($gaya === 'heading') {
            $this->heading[] = $n;
        }
    }

    private function build(): void
    {
        $meta = $this->service->meta('Laporan Keuangan');
        $r    = $this->service->ringkasan();
        $p    = $this->service->periode();

        // ---- header file
        $this->add([$meta['toko']], 'bold');
        $this->add([$meta['judul']], 'bold');
        $this->add(['Periode: ' . $meta['periode']]);
        $this->add(['Dicetak: ' . $meta['dicetak'] . ' oleh ' . Format::amanExcel($meta['oleh'])]);
        $this->add(['Filter: ' . $meta['filter']]);
        $this->add(['Catatan: laba kas sederhana (selisih uang masuk dan uang keluar), bukan laporan akuntansi lengkap.']);
        $this->add([]);

        // ---- ringkasan
        $this->add(['RINGKASAN', 'Periode ini', 'Periode sebelumnya', 'Perubahan'], 'heading');
        $this->add(['', $p->label(), $p->prevLabel(), '']);
        $this->add(['Total Pemasukan', $r['pemasukan'], $r['pemasukan_lalu'], Format::persen($r['pct_pemasukan'])]);
        $this->add(['Total Pengeluaran', $r['pengeluaran'], $r['pengeluaran_lalu'], Format::persen($r['pct_pengeluaran'])]);
        $this->add(['Selisih (' . ($r['selisih'] >= 0 ? 'Laba' : 'Rugi') . ')', $r['selisih'], $r['selisih_lalu'], Format::persen($r['pct_selisih'])], 'bold');
        $this->add([]);

        // ---- pemasukan per sumber
        $this->add(['PEMASUKAN PER SUMBER', 'Total', 'Jumlah transaksi', 'Persentase'], 'heading');
        foreach ($this->service->pemasukanPerSumber() as $s) {
            $this->add([$s['label'], $s['total'], $s['jumlah'], Format::porsi($s['persen'])]);
        }
        $this->add([]);

        // ---- pengeluaran per kategori
        $this->add(['PENGELUARAN PER KATEGORI', 'Total', 'Jumlah transaksi', 'Persentase'], 'heading');
        $kategori = $this->service->pengeluaranPerKategori();
        if ($kategori === []) {
            $this->add(['Tidak ada pengeluaran pada periode ini']);
        }
        foreach ($kategori as $k) {
            $this->add([Format::amanExcel($k['nama']), $k['total'], $k['jumlah'], Format::porsi($k['persen'])]);
        }
        $this->add([]);

        // ---- per bulan
        $bulan = $this->service->perBulan();
        $this->add(['PER BULAN TAHUN ' . $bulan['tahun'], 'Pemasukan', 'Pengeluaran', 'Selisih'], 'heading');
        foreach ($bulan['rows'] as $b) {
            $this->add([$b['label'], $b['pemasukan'], $b['pengeluaran'], $b['selisih']]);
        }
        $this->add(['TOTAL', $bulan['total']['pemasukan'], $bulan['total']['pengeluaran'], $bulan['total']['selisih']], 'bold');
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function title(): string
    {
        return 'Ringkasan';
    }

    public function columnFormats(): array
    {
        return [
            'B' => '#,##0',
            'C' => '#,##0',
            'D' => '#,##0',
        ];
    }

    public function columnWidths(): array
    {
        return ['A' => 38, 'B' => 24, 'C' => 24, 'D' => 20];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                foreach (array_merge($this->bold, $this->heading) as $row) {
                    $sheet->getStyle("A{$row}:D{$row}")->getFont()->setBold(true);
                }

                foreach ($this->heading as $row) {
                    $sheet->getStyle("A{$row}:D{$row}")->getFill()
                        ->setFillType('solid')
                        ->getStartColor()->setRGB('E9ECEF');
                }
            },
        ];
    }
}
