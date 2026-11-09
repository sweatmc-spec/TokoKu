<?php

namespace App\Services\Laporan;

use App\Http\Requests\LaporanPenjualanRequest;
use App\Models\Terjual;
use App\Models\TerjualItem;
use App\Models\User;
use App\Support\Laporan\Format;
use App\Support\Laporan\PeriodeLaporan;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Semua angka Laporan Penjualan. Dipakai bersama oleh tampilan, Excel, dan PDF.
 * Semua penjumlahan dihitung database (SUM / COUNT / GROUP BY); PHP hanya menyusun hasilnya.
 *
 * ATURAN LABA
 *  - Penjualan = nilai SETELAH diskon. Diskon transaksi dibagi ke tiap barang sesuai nilainya
 *    (NET di bawah), jadi total per barang, total per transaksi, dan kartu ringkasan saling cocok.
 *  - Modal = qty x terjual_items.harga_modal (harga beli per pcs yang disalin saat transaksi).
 *  - Baris yang harga_modal-nya null TIDAK ikut dihitung dalam laba (tampil "-"), tetapi tetap
 *    ikut dalam total penjualan. Jumlahnya ditampilkan di halaman supaya jelas.
 *
 * FILTER BARANG
 *  Kartu, grafik, dan tab Per Barang hanya menghitung barang itu (nilai setelah pembagian diskon).
 *  Tab Per Transaksi menampilkan transaksi UTUH yang memuat barang tersebut.
 *
 * Query memakai CAST / EXTRACT / CASE WHEN standar, jadi jalan di MySQL maupun PostgreSQL.
 */
class LaporanPenjualanService
{
    /** Nilai satu baris barang setelah bagian diskonnya (t = terjuals, ti = terjual_items). */
    private const NET = '(CASE WHEN t.subtotal > 0 THEN ti.total_price * 1.0 * t.total / t.subtotal ELSE 0 END)';

    /** Modal satu baris. Hanya bermakna kalau ti.harga_modal IS NOT NULL. */
    private const MODAL = '(ti.qty * ti.harga_modal)';

    public function __construct(
        private readonly PeriodeLaporan $periode,
        private readonly ?int $barangId = null,
        private readonly ?int $kasirId = null,
    ) {
    }

    public static function fromRequest(LaporanPenjualanRequest $request): self
    {
        return new self(
            $request->periode(),
            $request->filled('barang') ? (int) $request->input('barang') : null,
            $request->filled('kasir') ? (int) $request->input('kasir') : null,
        );
    }

    public function periode(): PeriodeLaporan
    {
        return $this->periode;
    }

    // ------------------------------------------------------------------ batas waktu

    /** sold_at adalah datetime: pakai >= awal hari dan < awal hari berikutnya, supaya index sold_at terpakai. */
    private function awal(CarbonImmutable $hari): string
    {
        return $hari->startOfDay()->toDateTimeString();
    }

    private function batas(CarbonImmutable $hari): string
    {
        return $hari->addDay()->startOfDay()->toDateTimeString();
    }

    // ------------------------------------------------------------------ query dasar

    /** terjual_items + terjuals, sudah disaring kasir dan barang (belum disaring periode). */
    private function itemBase(): QueryBuilder
    {
        return DB::table('terjual_items as ti')
            ->join('terjuals as t', 't.id', '=', 'ti.terjual_id')
            ->when($this->kasirId, fn ($q, $id) => $q->where('t.user_id', $id))
            ->when($this->barangId, fn ($q, $id) => $q->where('ti.product_id', $id));
    }

    private function itemPeriode(): QueryBuilder
    {
        return $this->itemBase()
            ->where('t.sold_at', '>=', $this->awal($this->periode->from))
            ->where('t.sold_at', '<', $this->batas($this->periode->to));
    }

    // ------------------------------------------------------------------ kartu ringkasan

    /** @return array<string, mixed> */
    public function ringkasan(): array
    {
        $p = $this->periode;

        // Literal tanggal di bawah dibuat Carbon dari tanggal yang sudah divalidasi (format Y-m-d H:i:s),
        // bukan dari input user mentah.
        $cur  = [$this->awal($p->from), $this->batas($p->to)];
        $prev = [$this->awal($p->prevFrom), $this->batas($p->prevTo)];
        $kini = "t.sold_at >= '{$cur[0]}' AND t.sold_at < '{$cur[1]}'";
        $lalu = "t.sold_at >= '{$prev[0]}' AND t.sold_at < '{$prev[1]}'";
        $min  = min($cur[0], $prev[0]);
        $max  = max($cur[1], $prev[1]);

        $net   = self::NET;
        $modal = self::MODAL;

        // Satu scan untuk periode ini DAN pembanding (agregat bersyarat).
        $i = $this->itemBase()
            ->where('t.sold_at', '>=', $min)->where('t.sold_at', '<', $max)
            ->selectRaw(implode(', ', [
                "COALESCE(SUM(CASE WHEN {$kini} THEN {$net} ELSE 0 END), 0) AS cur_net",
                "COALESCE(SUM(CASE WHEN {$kini} THEN ti.qty ELSE 0 END), 0) AS cur_qty",
                "COUNT(DISTINCT CASE WHEN {$kini} THEN ti.terjual_id END) AS cur_trx",
                "COALESCE(SUM(CASE WHEN {$kini} AND ti.harga_modal IS NOT NULL THEN {$net} ELSE 0 END), 0) AS cur_net_known",
                "COALESCE(SUM(CASE WHEN {$kini} AND ti.harga_modal IS NOT NULL THEN {$modal} ELSE 0 END), 0) AS cur_modal",
                "COUNT(CASE WHEN {$kini} AND ti.harga_modal IS NULL THEN 1 END) AS cur_unknown",
                "COALESCE(SUM(CASE WHEN {$lalu} THEN {$net} ELSE 0 END), 0) AS prev_net",
                "COALESCE(SUM(CASE WHEN {$lalu} THEN ti.qty ELSE 0 END), 0) AS prev_qty",
                "COUNT(DISTINCT CASE WHEN {$lalu} THEN ti.terjual_id END) AS prev_trx",
                "COALESCE(SUM(CASE WHEN {$lalu} AND ti.harga_modal IS NOT NULL THEN {$net} ELSE 0 END), 0) AS prev_net_known",
                "COALESCE(SUM(CASE WHEN {$lalu} AND ti.harga_modal IS NOT NULL THEN {$modal} ELSE 0 END), 0) AS prev_modal",
            ]))
            ->first();

        if ($this->barangId === null) {
            // tanpa filter barang: total persis dari kolom terjuals.total (setelah diskon)
            $t = DB::table('terjuals as t')
                ->when($this->kasirId, fn ($q, $id) => $q->where('t.user_id', $id))
                ->where('t.sold_at', '>=', $min)->where('t.sold_at', '<', $max)
                ->selectRaw(implode(', ', [
                    "COALESCE(SUM(CASE WHEN {$kini} THEN t.total ELSE 0 END), 0) AS cur_total",
                    "COUNT(CASE WHEN {$kini} THEN 1 END) AS cur_count",
                    "COALESCE(SUM(CASE WHEN {$lalu} THEN t.total ELSE 0 END), 0) AS prev_total",
                    "COUNT(CASE WHEN {$lalu} THEN 1 END) AS prev_count",
                ]))
                ->first();

            $penjualan = (int) $t->cur_total;
            $lalu_pj   = (int) $t->prev_total;
            $trx       = (int) $t->cur_count;
            $trxLalu   = (int) $t->prev_count;
        } else {
            $penjualan = (int) round((float) $i->cur_net);
            $lalu_pj   = (int) round((float) $i->prev_net);
            $trx       = (int) $i->cur_trx;
            $trxLalu   = (int) $i->prev_trx;
        }

        $qty      = (int) $i->cur_qty;
        $qtyLalu  = (int) $i->prev_qty;
        $rata     = $trx > 0 ? (int) round($penjualan / $trx) : 0;
        $rataLalu = $trxLalu > 0 ? (int) round($lalu_pj / $trxLalu) : 0;

        $netKnown = (float) $i->cur_net_known;
        $laba     = (int) round($netKnown - (float) $i->cur_modal);
        $labaLalu = (int) round((float) $i->prev_net_known - (float) $i->prev_modal);

        return [
            'penjualan'       => $penjualan,
            'penjualan_lalu'  => $lalu_pj,
            'pct_penjualan'   => self::persen($penjualan, $lalu_pj),

            'transaksi'       => $trx,
            'transaksi_lalu'  => $trxLalu,
            'pct_transaksi'   => self::persen($trx, $trxLalu),

            'rata'            => $rata,
            'rata_lalu'       => $rataLalu,
            'pct_rata'        => self::persen($rata, $rataLalu),

            'qty'             => $qty,
            'qty_lalu'        => $qtyLalu,
            'pct_qty'         => self::persen($qty, $qtyLalu),

            'laba'            => $laba,
            'laba_lalu'       => $labaLalu,
            'pct_laba'        => self::persen($laba, $labaLalu),
            'modal'           => (int) round((float) $i->cur_modal),
            'margin'          => $netKnown > 0 ? round($laba / $netKnown * 100, 1) : null,
            'margin_lalu'     => (float) $i->prev_net_known > 0 ? round($labaLalu / (float) $i->prev_net_known * 100, 1) : null,

            // baris barang tanpa harga modal: tetap ikut penjualan, tidak ikut laba
            'baris_tanpa_modal'     => (int) $i->cur_unknown,
            'penjualan_tanpa_modal' => (int) round((float) $i->cur_net - $netKnown),

            'ada_data'        => $trx > 0,
            'label'           => $p->label(),
            'label_lalu'      => $p->prevLabel(),
        ];
    }

    /** Persen perubahan terhadap NILAI MUTLAK periode lalu; null kalau periode lalu 0. */
    private static function persen(int $sekarang, int $lalu): ?float
    {
        return $lalu === 0 ? null : round(($sekarang - $lalu) / abs($lalu) * 100, 1);
    }

    // ------------------------------------------------------------------ grafik

    /**
     * Penjualan dan jumlah transaksi: per hari kalau periode 31 hari atau kurang, selain itu per bulan.
     * Hari / bulan tanpa transaksi diisi 0 supaya sumbu grafik tidak melompat.
     *
     * @return array{mode:string, rows:list<array{label:string,total:int,transaksi:int}>, ada_data:bool}
     */
    public function seri(): array
    {
        $p        = $this->periode;
        $perBulan = $p->days() > 31;

        $bucket = $perBulan
            ? 'EXTRACT(YEAR FROM t.sold_at) * 100 + EXTRACT(MONTH FROM t.sold_at)'
            : 'CAST(t.sold_at AS DATE)';

        if ($this->barangId === null) {
            $rows = DB::table('terjuals as t')
                ->when($this->kasirId, fn ($q, $id) => $q->where('t.user_id', $id))
                ->where('t.sold_at', '>=', $this->awal($p->from))
                ->where('t.sold_at', '<', $this->batas($p->to))
                ->selectRaw("{$bucket} AS bucket, SUM(t.total) AS total, COUNT(*) AS trx")
                ->groupByRaw($bucket)
                ->get();
        } else {
            $rows = $this->itemPeriode()
                ->selectRaw("{$bucket} AS bucket, SUM(" . self::NET . ') AS total, COUNT(DISTINCT ti.terjual_id) AS trx')
                ->groupByRaw($bucket)
                ->get();
        }

        $peta = $rows->mapWithKeys(fn ($r) => [
            ($perBulan ? (string) (int) $r->bucket : substr((string) $r->bucket, 0, 10)) => [
                'total'     => (int) round((float) $r->total),
                'transaksi' => (int) $r->trx,
            ],
        ]);

        $baris = [];

        if ($perBulan) {
            for ($d = $p->from->startOfMonth(); $d->lte($p->to); $d = $d->addMonthNoOverflow()) {
                $v = $peta[$d->format('Ym')] ?? ['total' => 0, 'transaksi' => 0];
                $baris[] = ['label' => Format::bulanPendek($d->month) . ' ' . $d->year] + $v;
            }
        } else {
            for ($d = $p->from; $d->lte($p->to); $d = $d->addDay()) {
                $v = $peta[$d->toDateString()] ?? ['total' => 0, 'transaksi' => 0];
                $baris[] = ['label' => sprintf('%02d %s', $d->day, Format::bulanPendek($d->month))] + $v;
            }
        }

        return [
            'mode'     => $perBulan ? 'bulan' : 'hari',
            'rows'     => $baris,
            'ada_data' => $peta->contains(fn ($v) => $v['total'] > 0 || $v['transaksi'] > 0),
        ];
    }

    // ------------------------------------------------------------------ tab Per Transaksi

    private function transaksiQuery(): Builder
    {
        $p = $this->periode;

        return Terjual::query()
            ->with('user:id,name')
            ->withCount('items')
            ->withSum('items as qty_total', 'qty')
            ->addSelect([
                // modal baris yang diketahui, dan jumlah baris yang modalnya null (untuk menentukan laba "-")
                'modal_total'  => TerjualItem::query()
                    ->selectRaw('COALESCE(SUM(qty * harga_modal), 0)')
                    ->whereColumn('terjual_id', 'terjuals.id'),
                'modal_kosong' => TerjualItem::query()
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('terjual_id', 'terjuals.id')
                    ->whereNull('harga_modal'),
            ])
            ->where('sold_at', '>=', $this->awal($p->from))
            ->where('sold_at', '<', $this->batas($p->to))
            ->when($this->kasirId, fn ($q, $id) => $q->where('user_id', $id))
            ->when($this->barangId, fn ($q, $id) => $q->whereHas('items', fn ($i) => $i->where('product_id', $id)))
            ->orderByDesc('sold_at')
            ->orderByDesc('id');
    }

    /** Laba satu transaksi (setelah diskon); null kalau ada barang yang modalnya tidak diketahui. */
    public static function labaTransaksi(Terjual $t): ?int
    {
        if ((int) $t->modal_kosong > 0) {
            return null;
        }

        return (int) round((float) $t->total - (float) $t->modal_total);
    }

    public function transaksi(int $perPage = 15)
    {
        return $this->transaksiQuery()
            ->paginate($perPage, ['*'], 'halaman_transaksi')
            ->withQueryString()
            ->appends(['tab' => 'transaksi']);
    }

    /** Untuk export: semua transaksi pada periode (tidak dipaginasi). */
    public function semuaTransaksi(): Collection
    {
        return $this->transaksiQuery()->get();
    }

    // ------------------------------------------------------------------ tab Per Barang

    /** Satu baris = satu barang (produk, atau varian untuk pakaian), terbesar penjualannya dulu. */
    private function barangQuery(): QueryBuilder
    {
        $net   = self::NET;
        $modal = self::MODAL;

        return $this->itemPeriode()
            ->selectRaw(implode(', ', [
                'ti.product_id AS product_id',
                'ti.product_variant_id AS variant_id',
                'ti.product_name AS nama',
                'ti.variant_label AS varian',
                'SUM(ti.qty) AS qty',
                "SUM({$net}) AS penjualan",
                "COALESCE(SUM(CASE WHEN ti.harga_modal IS NOT NULL THEN {$modal} END), 0) AS modal",
                "COALESCE(SUM(CASE WHEN ti.harga_modal IS NOT NULL THEN {$net} END), 0) AS net_known",
                'COUNT(CASE WHEN ti.harga_modal IS NULL THEN 1 END) AS tanpa_modal',
                'COUNT(*) AS baris',
            ]))
            ->groupBy('ti.product_id', 'ti.product_variant_id', 'ti.product_name', 'ti.variant_label')
            ->orderByDesc('penjualan')
            ->orderBy('nama');
    }

    /** Tambahkan laba dan margin ke satu baris hasil barangQuery() (hitungan kecil per baris, bukan agregat). */
    private static function hitungBarang(object $r): object
    {
        $netKnown = (float) $r->net_known;
        $semuaKosong = (int) $r->tanpa_modal >= (int) $r->baris;

        $r->qty       = (int) $r->qty;
        $r->penjualan = (int) round((float) $r->penjualan);
        $r->modal_int = (int) round((float) $r->modal);
        $r->laba      = $semuaKosong ? null : (int) round($netKnown - (float) $r->modal);
        $r->margin    = ($semuaKosong || $netKnown <= 0) ? null : round(($netKnown - (float) $r->modal) / $netKnown * 100, 1);
        $r->sebagian  = (int) $r->tanpa_modal > 0 && ! $semuaKosong;   // sebagian barisnya tanpa modal

        return $r;
    }

    public function barang(int $perPage = 20)
    {
        return $this->barangQuery()
            ->paginate($perPage, ['*'], 'halaman_barang')
            ->withQueryString()
            ->appends(['tab' => 'barang'])
            ->through(fn ($r) => self::hitungBarang($r));
    }

    /** Untuk export: semua barang pada periode. */
    public function semuaBarang(): Collection
    {
        return $this->barangQuery()->get()->map(fn ($r) => self::hitungBarang($r));
    }

    // ------------------------------------------------------------------ pilihan filter

    /** Barang yang pernah terjual (satu baris per produk; varian pakaian digabung di produknya). */
    public function barangOptions(): Collection
    {
        return DB::table('terjual_items')
            ->whereNotNull('product_id')
            ->selectRaw('product_id AS id, MAX(product_name) AS nama')
            ->groupBy('product_id')
            ->orderBy('nama')
            ->get();
    }

    /** User yang pernah mencatat transaksi. */
    public function kasirOptions(): Collection
    {
        return User::query()
            ->whereIn('id', Terjual::query()->select('user_id')->whereNotNull('user_id'))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    // ------------------------------------------------------------------ header file

    /** @return array{toko:string, judul:string, periode:string, dicetak:string, oleh:string, filter:string} */
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

        if ($this->barangId) {
            $bagian[] = 'Barang: ' . (DB::table('products')->where('id', $this->barangId)->value('name') ?? '-');
        }
        if ($this->kasirId) {
            $bagian[] = 'Kasir: ' . (User::query()->whereKey($this->kasirId)->value('name') ?? '-');
        }

        return $bagian ? implode('; ', $bagian) : 'Tanpa filter tambahan';
    }
}
