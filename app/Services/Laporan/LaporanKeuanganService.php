<?php

namespace App\Services\Laporan;

use App\Http\Requests\LaporanKeuanganRequest;
use App\Models\KategoriPengeluaran;
use App\Models\Pemasukan;
use App\Models\Pengeluaran;
use App\Support\Laporan\Format;
use App\Support\Laporan\PeriodeLaporan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

/**
 * Semua angka Laporan Keuangan. Dipakai bersama oleh tampilan, Excel, dan PDF.
 *
 * Sumber data HANYA tabel pemasukans dan pengeluarans (sudah mencatat penjualan dan kulakan
 * secara otomatis), jadi tidak ada angka yang dihitung ulang dari Terjual atau Cek Paket.
 * Semua penjumlahan dilakukan database (SUM / COUNT / GROUP BY); PHP hanya menyusun hasilnya.
 *
 * Filter sumber dan kategori menyaring SISINYA SENDIRI:
 *   sumber_masuk  -> hanya pemasukan,   sumber_keluar + kategori -> hanya pengeluaran.
 * Dengan begitu selisih (laba/rugi) tetap bisa dibaca: bagian yang tidak difilter tetap utuh.
 *
 * Query memakai EXTRACT() dan CASE WHEN standar, jadi jalan di MySQL maupun PostgreSQL.
 */
class LaporanKeuanganService
{
    public function __construct(
        private readonly PeriodeLaporan $periode,
        private readonly ?string $sumberMasuk = null,
        private readonly ?string $sumberKeluar = null,
        private readonly ?int $kategoriId = null,
        private readonly ?int $tahun = null,
    ) {
    }

    public static function fromRequest(LaporanKeuanganRequest $request): self
    {
        $periode = $request->periode();

        return new self(
            $periode,
            $request->input('sumber_masuk') ?: null,
            $request->input('sumber_keluar') ?: null,
            $request->filled('kategori') ? (int) $request->input('kategori') : null,
            // grafik bulanan: tahun pilihan user, default tahun akhir periode
            $request->filled('tahun') ? (int) $request->input('tahun') : $periode->to->year,
        );
    }

    public function periode(): PeriodeLaporan
    {
        return $this->periode;
    }

    public function tahun(): int
    {
        return $this->tahun ?? $this->periode->to->year;
    }

    // ------------------------------------------------------------------ query dasar

    private function masuk(): Builder
    {
        return Pemasukan::query()
            ->when($this->sumberMasuk, fn ($q, $sumber) => $q->where('sumber', $sumber));
    }

    private function keluar(): Builder
    {
        return Pengeluaran::query()
            ->when($this->sumberKeluar, fn ($q, $sumber) => $q->where('sumber', $sumber))
            ->when($this->kategoriId, fn ($q, $id) => $q->where('kategori_pengeluaran_id', $id));
    }

    // ------------------------------------------------------------------ kartu ringkasan

    /**
     * Total periode ini dan periode pembanding, masing-masing dalam SATU query
     * (SUM / COUNT bersyarat), bukan dua kali scan.
     *
     * @return array<string, mixed>
     */
    public function ringkasan(): array
    {
        $p = $this->periode;

        [$masuk, $jumlahMasuk, $masukLalu]   = $this->agregat($this->masuk());
        [$keluar, $jumlahKeluar, $keluarLalu] = $this->agregat($this->keluar());

        $selisih     = $masuk - $keluar;
        $selisihLalu = $masukLalu - $keluarLalu;

        return [
            'pemasukan'        => $masuk,
            'pemasukan_lalu'   => $masukLalu,
            'pengeluaran'      => $keluar,
            'pengeluaran_lalu' => $keluarLalu,
            'selisih'          => $selisih,
            'selisih_lalu'     => $selisihLalu,

            'pct_pemasukan'    => self::persen($masuk, $masukLalu),
            'pct_pengeluaran'  => self::persen($keluar, $keluarLalu),
            'pct_selisih'      => self::persen($selisih, $selisihLalu),

            'jumlah_masuk'     => $jumlahMasuk,
            'jumlah_keluar'    => $jumlahKeluar,
            'ada_data'         => ($jumlahMasuk + $jumlahKeluar) > 0,

            'label'            => $p->label(),
            'label_lalu'       => $p->prevLabel(),
        ];
    }

    /** @return array{0:int, 1:int, 2:int} [total periode ini, jumlah baris periode ini, total periode pembanding] */
    private function agregat(Builder $query): array
    {
        $p = $this->periode;

        $cur  = [$p->fromDate(), $p->toDate()];
        $prev = [$p->prevFromDate(), $p->prevToDate()];

        // periode pembanding selalu sebelum periode ini; satu scan dari yang terawal sampai yang terakhir
        $row = $query
            ->whereBetween('tanggal', [min($cur[0], $prev[0]), max($cur[1], $prev[1])])
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN tanggal BETWEEN ? AND ? THEN nominal ELSE 0 END), 0) AS cur_total,'
                . ' COUNT(CASE WHEN tanggal BETWEEN ? AND ? THEN 1 END) AS cur_count,'
                . ' COALESCE(SUM(CASE WHEN tanggal BETWEEN ? AND ? THEN nominal ELSE 0 END), 0) AS prev_total',
                [$cur[0], $cur[1], $cur[0], $cur[1], $prev[0], $prev[1]]
            )
            ->first();

        return [(int) $row->cur_total, (int) $row->cur_count, (int) $row->prev_total];
    }

    /** Persen perubahan terhadap NILAI MUTLAK periode lalu (supaya rugi -> kurang rugi tetap terbaca naik). */
    private static function persen(int $sekarang, int $lalu): ?float
    {
        return $lalu === 0 ? null : round(($sekarang - $lalu) / abs($lalu) * 100, 1);
    }

    // ------------------------------------------------------------------ rincian per sumber / kategori

    /**
     * Pemasukan per sumber (Penjualan vs Manual).
     *
     * @return list<array{kode:string, label:string, total:int, jumlah:int, persen:float}>
     */
    public function pemasukanPerSumber(): array
    {
        $p = $this->periode;

        $rows = $this->masuk()
            ->whereBetween('tanggal', [$p->fromDate(), $p->toDate()])
            ->selectRaw('sumber, SUM(nominal) AS total, COUNT(*) AS jumlah')
            ->groupBy('sumber')
            ->get()
            ->keyBy('sumber');

        $kode  = $this->sumberMasuk ? [$this->sumberMasuk] : [Pemasukan::SUMBER_PENJUALAN, Pemasukan::SUMBER_MANUAL];
        $total = (int) $rows->sum('total');

        return collect($kode)->map(function (string $s) use ($rows, $total) {
            $t = (int) ($rows[$s]->total ?? 0);

            return [
                'kode'   => $s,
                'label'  => Format::sumber($s),
                'total'  => $t,
                'jumlah' => (int) ($rows[$s]->jumlah ?? 0),
                'persen' => $total > 0 ? round($t / $total * 100, 1) : 0.0,
            ];
        })->all();
    }

    /**
     * Pengeluaran per kategori, terbesar dulu.
     *
     * @return list<array{id:int, nama:string, total:int, jumlah:int, persen:float}>
     */
    public function pengeluaranPerKategori(): array
    {
        $p = $this->periode;

        $rows = $this->keluar()
            ->whereBetween('tanggal', [$p->fromDate(), $p->toDate()])
            ->selectRaw('kategori_pengeluaran_id AS kategori_id, SUM(nominal) AS total, COUNT(*) AS jumlah')
            ->groupBy('kategori_pengeluaran_id')
            ->orderByDesc('total')
            ->get();

        $nama  = KategoriPengeluaran::query()->pluck('nama', 'id');
        $total = (int) $rows->sum('total');

        return $rows->map(fn ($r) => [
            'id'     => (int) $r->kategori_id,
            'nama'   => $nama[$r->kategori_id] ?? '-',
            'total'  => (int) $r->total,
            'jumlah' => (int) $r->jumlah,
            'persen' => $total > 0 ? round($r->total / $total * 100, 1) : 0.0,
        ])->all();
    }

    // ------------------------------------------------------------------ per bulan dalam satu tahun

    /**
     * 12 bulan pada tahun pilihan (tidak bergantung pada periode di atas), plus baris total.
     *
     * @return array{tahun:int, rows:list<array<string,mixed>>, total:array<string,int>, ada_data:bool}
     */
    public function perBulan(): array
    {
        $tahun = $this->tahun();
        $batas = ["{$tahun}-01-01", "{$tahun}-12-31"];

        $perBulan = function (Builder $query) use ($batas) {
            return $query
                ->whereBetween('tanggal', $batas)
                ->selectRaw('EXTRACT(MONTH FROM tanggal) AS bulan, SUM(nominal) AS total')
                ->groupByRaw('EXTRACT(MONTH FROM tanggal)')
                ->get()
                ->mapWithKeys(fn ($r) => [(int) $r->bulan => (int) $r->total]);
        };

        $masuk  = $perBulan($this->masuk());
        $keluar = $perBulan($this->keluar());

        $rows = [];
        for ($b = 1; $b <= 12; $b++) {
            $m = $masuk[$b] ?? 0;
            $k = $keluar[$b] ?? 0;

            $rows[] = [
                'bulan'       => $b,
                'label'       => Format::bulanPendek($b),
                'pemasukan'   => $m,
                'pengeluaran' => $k,
                'selisih'     => $m - $k,
            ];
        }

        $totalMasuk  = (int) $masuk->sum();
        $totalKeluar = (int) $keluar->sum();

        return [
            'tahun'    => $tahun,
            'rows'     => $rows,
            'total'    => [
                'pemasukan'   => $totalMasuk,
                'pengeluaran' => $totalKeluar,
                'selisih'     => $totalMasuk - $totalKeluar,
            ],
            'ada_data' => ($totalMasuk + $totalKeluar) > 0,
        ];
    }

    /** Pilihan tahun untuk grafik: dari tahun data tertua sampai tahun ini (terbaru dulu). */
    public function tahunOptions(): array
    {
        $tertua = collect([Pemasukan::query()->min('tanggal'), Pengeluaran::query()->min('tanggal')])
            ->filter()
            ->map(fn ($t) => (int) substr((string) $t, 0, 4))
            ->min();

        $sekarang = (int) now()->format('Y');
        $awal     = min($tertua ?? $sekarang, $sekarang, $this->tahun());
        $akhir    = max($sekarang, $this->tahun());

        return range($akhir, $awal);
    }

    // ------------------------------------------------------------------ rincian transaksi (gabungan)

    /**
     * Pemasukan + pengeluaran dalam SATU daftar berurut tanggal (UNION ALL), dipaginasi di database.
     * Kolom: id, tanggal, jenis (masuk|keluar), sumber, kategori, keterangan,
     *        ref_kode, ref_tipe (terjual|purchase), ref_id, masuk, keluar, created_at.
     */
    public function rincianQuery(): QueryBuilder
    {
        $p = $this->periode;

        $masuk = DB::table('pemasukans as m')
            ->leftJoin('terjuals as t', 't.id', '=', 'm.terjual_id')
            ->whereBetween('m.tanggal', [$p->fromDate(), $p->toDate()])
            ->when($this->sumberMasuk, fn ($q, $s) => $q->where('m.sumber', $s))
            ->selectRaw(
                "m.id AS id, m.tanggal AS tanggal, 'masuk' AS jenis, m.sumber AS sumber, NULL AS kategori,"
                . " m.keterangan AS keterangan, t.code AS ref_kode, 'terjual' AS ref_tipe, m.terjual_id AS ref_id,"
                . ' m.nominal AS masuk, 0 AS keluar, m.created_at AS created_at'
            );

        $keluar = DB::table('pengeluarans as p')
            ->join('kategori_pengeluarans as k', 'k.id', '=', 'p.kategori_pengeluaran_id')
            ->leftJoin('purchases as pb', 'pb.id', '=', 'p.purchase_id')
            ->whereBetween('p.tanggal', [$p->fromDate(), $p->toDate()])
            ->when($this->sumberKeluar, fn ($q, $s) => $q->where('p.sumber', $s))
            ->when($this->kategoriId, fn ($q, $id) => $q->where('p.kategori_pengeluaran_id', $id))
            ->selectRaw(
                "p.id AS id, p.tanggal AS tanggal, 'keluar' AS jenis, p.sumber AS sumber, k.nama AS kategori,"
                . " p.keterangan AS keterangan, pb.code AS ref_kode, 'purchase' AS ref_tipe, p.purchase_id AS ref_id,"
                . ' 0 AS masuk, p.nominal AS keluar, p.created_at AS created_at'
            );

        return DB::query()
            ->fromSub($masuk->unionAll($keluar), 'r')
            ->orderByDesc('tanggal')
            ->orderByDesc('created_at')
            ->orderBy('jenis')
            ->orderByDesc('id');
    }

    /** Untuk tampilan: dipaginasi, query string (filter) dan tab dibawa di link halaman. */
    public function rincian(int $perPage = 20)
    {
        return $this->rincianQuery()
            ->paginate($perPage, ['*'], 'halaman')
            ->withQueryString()
            ->appends(['tab' => 'rincian']);
    }

    // ------------------------------------------------------------------ keterangan header (layar, Excel, PDF)

    /**
     * Identitas laporan untuk header file: toko, judul, periode, tanggal cetak, nama yang mencetak.
     * Nama toko = APP_NAME di .env.
     *
     * @return array{toko:string, judul:string, periode:string, dicetak:string, oleh:string, filter:string}
     */
    public function meta(string $judul): array
    {
        return [
            'toko'    => (string) config('app.name'),
            'judul'   => $judul,
            'periode' => $this->periode->label(),
            'dicetak' => Format::waktu(now()),
            'oleh'    => auth()->user()?->name ?? '-',
            'filter'  => $this->filterTeks(),
        ];
    }

    public function filterTeks(): string
    {
        $bagian = [];

        if ($this->sumberMasuk) {
            $bagian[] = 'Sumber pemasukan: ' . Format::sumber($this->sumberMasuk);
        }
        if ($this->sumberKeluar) {
            $bagian[] = 'Sumber pengeluaran: ' . Format::sumber($this->sumberKeluar);
        }
        if ($this->kategoriId) {
            $bagian[] = 'Kategori: ' . (KategoriPengeluaran::query()->whereKey($this->kategoriId)->value('nama') ?? '-');
        }

        return $bagian ? implode('; ', $bagian) : 'Tanpa filter tambahan';
    }
}
