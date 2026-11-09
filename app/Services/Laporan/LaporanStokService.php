<?php

namespace App\Services\Laporan;

use App\Http\Requests\LaporanStokRequest;
use App\Models\Category;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\Terjual;
use App\Services\StockLedger;
use App\Support\Laporan\Format;
use App\Support\Laporan\PeriodeLaporan;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Semua angka Laporan Stok (read-only). Dipakai bersama oleh tampilan, Excel, PDF, dan kartu stok.
 * Semua penjumlahan dihitung database; PHP hanya menyusun hasilnya.
 *
 * "Barang" = satuan yang dijual, sama seperti form Pembayaran:
 *   - kategori yang punya varian (Pakaian) -> satu baris per VARIAN (stok = product_variants.stock)
 *   - kategori lain (ATK, Misc)            -> satu baris per PRODUK (stok = products.stock)
 * Kunci barang: "p:{id produk}" atau "v:{id varian}". Di SQL: product_id + vid (vid = 0 untuk produk).
 *
 * Tab Stok Saat Ini membaca products / product_variants. Tab Mutasi membaca stock_movements (buku mutasi).
 * Saldo awal = jumlah seluruh mutasi sebelum periode. Dua angka itu independen, jadi kalau saldo seluruh mutasi
 * berbeda dengan stok aktual, barangnya ditandai: stok berubah di luar Cek Paket dan Terjual.
 *
 * Query memakai CASE WHEN, COALESCE, dan fungsi window standar, jadi jalan di MySQL 8+ maupun PostgreSQL.
 */
class LaporanStokService
{
    /** 0 = habis, 1 = menipis, 2 = aman (u = daftar barang). */
    private const STATUS = 'CASE WHEN u.stock <= 0 THEN 0 WHEN u.stock <= u.stok_minimum THEN 1 ELSE 2 END';

    private const STATUS_KODE  = ['habis' => 0, 'menipis' => 1, 'aman' => 2];
    private const STATUS_LABEL = [0 => 'habis', 1 => 'menipis', 2 => 'aman'];

    /** @var list<int>|null */
    private ?array $kategoriVarian = null;

    private ?int $barangProduct = null;   // dari kunci "p:ID"
    private int $barangVid = 0;           // dari kunci "v:ID"

    public function __construct(
        private readonly PeriodeLaporan $periode,
        private readonly ?int $kategoriId = null,
        private readonly ?string $barangKey = null,
        private readonly ?string $status = null,
        private readonly ?string $cari = null,
    ) {
        if ($barangKey !== null && preg_match('/^([pv]):(\d+)$/', $barangKey, $m)) {
            if ($m[1] === 'v') {
                $this->barangVid = (int) $m[2];
            } else {
                $this->barangProduct = (int) $m[2];
            }
        }
    }

    public static function fromRequest(LaporanStokRequest $request): self
    {
        return new self(
            $request->periode(),
            $request->filled('kategori') ? (int) $request->input('kategori') : null,
            $request->filled('barang') ? (string) $request->input('barang') : null,
            $request->filled('status') ? (string) $request->input('status') : null,
            $request->filled('q') ? trim((string) $request->input('q')) : null,
        );
    }

    public function periode(): PeriodeLaporan
    {
        return $this->periode;
    }

    /** Barang dipilih: tab Mutasi menampilkan kartu stok barang itu. */
    public function adaKartu(): bool
    {
        return $this->barangVid > 0 || $this->barangProduct !== null;
    }

    private function awal(): string
    {
        return $this->periode->from->startOfDay()->toDateTimeString();
    }

    /** Batas atas periode (eksklusif): awal hari berikutnya, supaya index tanggal terpakai. */
    private function batas(): string
    {
        return $this->periode->to->addDay()->startOfDay()->toDateTimeString();
    }

    // ------------------------------------------------------------------ daftar barang (dasar semua query)

    /** @return list<int> id kategori yang stoknya dicatat per varian */
    private function kategoriVarian(): array
    {
        return $this->kategoriVarian ??= Category::query()->get()
            ->filter(fn ($c) => $c->supportsVariants())
            ->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
    }

    /**
     * Gabungan produk (kategori tanpa varian) dan varian (kategori pakaian). Kolom:
     * product_id, vid, kategori_id, kategori, nama, stock, stok_minimum, last_cost.
     *
     * @param  bool  $hanyaDiterima  true = hanya yang pernah dicentang datang (aturan halaman Stok);
     *                               false = semua (dipakai tab Mutasi, supaya riwayat barang tidak hilang)
     */
    private function unitUnion(bool $hanyaDiterima): QueryBuilder
    {
        $varCats = $this->kategoriVarian();

        $produk = DB::table('products as p')
            ->join('categories as c', 'c.id', '=', 'p.category_id')
            ->when($varCats, fn ($q, $ids) => $q->whereNotIn('p.category_id', $ids))
            ->when($hanyaDiterima, fn ($q) => $q->whereExists(fn ($e) => $e->selectRaw('1')
                ->from('purchase_items as pi')
                ->whereColumn('pi.product_id', 'p.id')
                ->whereNotNull('pi.received_at')))
            ->selectRaw('p.id AS product_id, 0 AS vid, c.id AS kategori_id, c.name AS kategori, p.name AS nama,'
                . ' p.stock AS stock, p.stok_minimum AS stok_minimum, p.last_cost AS last_cost');

        if (! $varCats) {
            return $produk;
        }

        $varian = DB::table('product_variants as v')
            ->join('products as p', 'p.id', '=', 'v.product_id')
            ->join('categories as c', 'c.id', '=', 'p.category_id')
            ->whereIn('p.category_id', $varCats)
            ->when($hanyaDiterima, fn ($q) => $q->whereExists(fn ($e) => $e->selectRaw('1')
                ->from('purchase_items as pi')
                ->whereColumn('pi.product_variant_id', 'v.id')
                ->whereNotNull('pi.received_at')))
            ->selectRaw('p.id AS product_id, v.id AS vid, c.id AS kategori_id, c.name AS kategori, p.name AS nama,'
                . ' v.stock AS stock, p.stok_minimum AS stok_minimum, p.last_cost AS last_cost');

        return $produk->unionAll($varian);
    }

    private function unitBase(bool $hanyaDiterima = true): QueryBuilder
    {
        return DB::query()->fromSub($this->unitUnion($hanyaDiterima), 'u');
    }

    private function filterBarang(QueryBuilder $q): QueryBuilder
    {
        if ($this->barangVid > 0) {
            $q->where('u.vid', $this->barangVid);
        } elseif ($this->barangProduct !== null) {
            $q->where('u.product_id', $this->barangProduct)->where('u.vid', 0);
        }

        return $q;
    }

    /** Filter yang berlaku di semua bagian: kategori dan barang. */
    private function filterDasar(QueryBuilder $q): QueryBuilder
    {
        $q->when($this->kategoriId, fn ($x, $id) => $x->where('u.kategori_id', $id));

        return $this->filterBarang($q);
    }

    /** Filter khusus tabel Stok Saat Ini: status dan pencarian nama. */
    private function filterTabel(QueryBuilder $q): QueryBuilder
    {
        if ($this->status !== null) {
            $q->whereRaw(self::STATUS . ' = ?', [self::STATUS_KODE[$this->status]]);
        }

        if ($this->cari !== null && $this->cari !== '') {
            $kata = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], mb_strtolower($this->cari));
            $q->whereRaw('LOWER(u.nama) LIKE ?', ['%' . $kata . '%']);
        }

        return $q;
    }

    // ------------------------------------------------------------------ kartu ringkasan

    /**
     * Kartu ringkasan: ikut filter kategori dan barang (bukan status / pencarian, supaya kartu tidak ikut nol).
     *
     * @return array<string, mixed>
     */
    public function ringkasan(): array
    {
        $q = $this->filterDasar($this->unitBase());

        $r = $q->selectRaw(implode(', ', [
            'COUNT(*) AS jenis',
            'COUNT(CASE WHEN ' . self::STATUS . ' = 1 THEN 1 END) AS menipis',
            'COUNT(CASE WHEN ' . self::STATUS . ' = 0 THEN 1 END) AS habis',
            'COALESCE(SUM(CASE WHEN u.last_cost IS NOT NULL AND u.stock > 0 THEN u.stock * u.last_cost ELSE 0 END), 0) AS nilai',
            'COUNT(CASE WHEN u.last_cost IS NULL AND u.stock > 0 THEN 1 END) AS tanpa_harga',
            'COALESCE(SUM(CASE WHEN u.stock > 0 THEN u.stock ELSE 0 END), 0) AS total_pcs',
        ]))->first();

        return [
            'jenis'        => (int) $r->jenis,
            'menipis'      => (int) $r->menipis,
            'habis'        => (int) $r->habis,
            'nilai'        => (int) round((float) $r->nilai),
            'tanpa_harga'  => (int) $r->tanpa_harga,
            'total_pcs'    => (int) $r->total_pcs,
            'tidak_cocok'  => $this->tidakCocok(),
            'ada_data'     => (int) $r->jenis > 0,
        ];
    }

    /** Jumlah barang yang saldo seluruh mutasinya berbeda dengan stok aktual (tidak bergantung periode). */
    public function tidakCocok(): int
    {
        $q = DB::query()
            ->fromSub($this->mutasiGroup(), 'g')
            ->joinSub($this->unitUnion(false), 'u', fn ($j) => $j->on('u.product_id', '=', 'g.product_id')->on('u.vid', '=', 'g.vid'));

        $this->filterDasar($q);

        return (int) $q->selectRaw('COUNT(CASE WHEN g.saldo_total <> u.stock THEN 1 END) AS n')->value('n');
    }

    // ------------------------------------------------------------------ tab 1: Stok Saat Ini

    /** Habis dan Menipis paling atas supaya langsung terlihat apa yang perlu dipesan ke sales. */
    private function stokQuery(): QueryBuilder
    {
        $q = $this->filterTabel($this->filterDasar($this->unitBase()));

        return $q
            ->selectRaw('u.*, ' . self::STATUS . ' AS status_rank,'
                . ' CASE WHEN u.last_cost IS NULL THEN NULL ELSE u.stock * u.last_cost END AS nilai')
            ->orderByRaw(self::STATUS)
            ->orderBy('u.stock')
            ->orderBy('u.nama')
            ->orderBy('u.vid');
    }

    public function stok(int $perPage = 20)
    {
        $paginator = $this->stokQuery()
            ->paginate($perPage, ['*'], 'halaman_stok')
            ->withQueryString()
            ->appends(['tab' => 'stok']);

        $this->lengkapi($paginator->getCollection());

        return $paginator;
    }

    /** Untuk export: semua barang (tidak dipaginasi). */
    public function semuaStok(): Collection
    {
        return $this->lengkapi($this->stokQuery()->get());
    }

    /**
     * Total untuk baris footer tabel (ikut SEMUA filter tabel, termasuk status dan pencarian).
     *
     * @return array{jumlah:int, pcs:int, nilai:int, tanpa_harga:int}
     */
    public function stokTotal(): array
    {
        $r = $this->filterTabel($this->filterDasar($this->unitBase()))
            ->selectRaw(implode(', ', [
                'COUNT(*) AS jumlah',
                'COALESCE(SUM(CASE WHEN u.stock > 0 THEN u.stock ELSE 0 END), 0) AS pcs',
                'COALESCE(SUM(CASE WHEN u.last_cost IS NOT NULL AND u.stock > 0 THEN u.stock * u.last_cost ELSE 0 END), 0) AS nilai',
                'COUNT(CASE WHEN u.last_cost IS NULL AND u.stock > 0 THEN 1 END) AS tanpa_harga',
            ]))->first();

        return [
            'jumlah'      => (int) $r->jumlah,
            'pcs'         => (int) $r->pcs,
            'nilai'       => (int) round((float) $r->nilai),
            'tanpa_harga' => (int) $r->tanpa_harga,
        ];
    }

    // ------------------------------------------------------------------ tab 2: Mutasi Stok

    /** Mutasi per barang dari buku mutasi: saldo awal periode, masuk, keluar periode, dan saldo seluruh waktu. */
    private function mutasiGroup(): QueryBuilder
    {
        $awal  = $this->awal();
        $batas = $this->batas();

        // awal / batas dibuat Carbon dari tanggal yang sudah divalidasi (Y-m-d H:i:s), bukan dari input user mentah.
        return DB::table('stock_movements as m')
            ->selectRaw(implode(', ', [
                'm.product_id AS product_id',
                'COALESCE(m.product_variant_id, 0) AS vid',
                "SUM(CASE WHEN m.tanggal < '{$awal}' THEN m.qty_masuk - m.qty_keluar ELSE 0 END) AS saldo_awal",
                "SUM(CASE WHEN m.tanggal >= '{$awal}' AND m.tanggal < '{$batas}' THEN m.qty_masuk ELSE 0 END) AS masuk",
                "SUM(CASE WHEN m.tanggal >= '{$awal}' AND m.tanggal < '{$batas}' THEN m.qty_keluar ELSE 0 END) AS keluar",
                'SUM(m.qty_masuk - m.qty_keluar) AS saldo_total',
            ]))
            ->groupByRaw('m.product_id, COALESCE(m.product_variant_id, 0)');
    }

    private function mutasiQuery(): QueryBuilder
    {
        $q = DB::query()
            ->fromSub($this->mutasiGroup(), 'g')
            ->joinSub($this->unitUnion(false), 'u', fn ($j) => $j->on('u.product_id', '=', 'g.product_id')->on('u.vid', '=', 'g.vid'));

        $this->filterDasar($q);

        return $q
            // barang yang tidak pernah bergerak dan stoknya 0 tidak perlu memenuhi tabel
            ->whereRaw('(g.saldo_awal <> 0 OR g.masuk <> 0 OR g.keluar <> 0 OR g.saldo_total <> 0 OR u.stock <> 0)')
            ->selectRaw(implode(', ', [
                'u.product_id', 'u.vid', 'u.kategori', 'u.nama', 'u.stock AS stok_aktual',
                'g.saldo_awal', 'g.masuk', 'g.keluar',
                '(g.saldo_awal + g.masuk - g.keluar) AS saldo_akhir',
                'g.saldo_total',
                'CASE WHEN g.saldo_total <> u.stock THEN 1 ELSE 0 END AS tidak_cocok',
            ]))
            ->orderBy('u.nama')
            ->orderBy('u.vid');
    }

    public function mutasi(int $perPage = 20)
    {
        $paginator = $this->mutasiQuery()
            ->paginate($perPage, ['*'], 'halaman_mutasi')
            ->withQueryString()
            ->appends(['tab' => 'mutasi']);

        $this->lengkapi($paginator->getCollection());

        return $paginator;
    }

    /** Untuk export: semua barang pada periode. */
    public function semuaMutasi(): Collection
    {
        return $this->lengkapi($this->mutasiQuery()->get());
    }

    // ------------------------------------------------------------------ kartu stok per barang

    private function kartuFilter(QueryBuilder $q): QueryBuilder
    {
        return $this->barangVid > 0
            ? $q->where('m.product_variant_id', $this->barangVid)
            : $q->where('m.product_id', $this->barangProduct)->whereNull('m.product_variant_id');
    }

    /** Identitas dan stok aktual barang yang dipilih; null kalau barangnya sudah tidak ada. */
    public function kartuInfo(): ?object
    {
        if (! $this->adaKartu()) {
            return null;
        }

        $row = $this->filterBarang($this->unitBase(false))
            ->select('u.product_id', 'u.vid', 'u.kategori', 'u.nama', 'u.stock', 'u.stok_minimum', 'u.last_cost')
            ->first();

        return $row ? $this->lengkapi(collect([$row]))->first() : null;
    }

    /**
     * Angka ringkas kartu: saldo awal periode, masuk, keluar, saldo akhir periode, saldo seluruh mutasi,
     * dan apakah saldo itu cocok dengan stok aktual.
     *
     * @return array<string, int|bool>
     */
    public function kartuRingkasan(object $info): array
    {
        $awal  = (int) $this->kartuFilter(DB::table('stock_movements as m'))
            ->where('m.tanggal', '<', $this->awal())
            ->selectRaw('COALESCE(SUM(m.qty_masuk - m.qty_keluar), 0) AS s')->value('s');

        $periode = $this->kartuFilter(DB::table('stock_movements as m'))
            ->where('m.tanggal', '>=', $this->awal())
            ->where('m.tanggal', '<', $this->batas())
            ->selectRaw('COALESCE(SUM(m.qty_masuk), 0) AS masuk, COALESCE(SUM(m.qty_keluar), 0) AS keluar')
            ->first();

        $total = (int) $this->kartuFilter(DB::table('stock_movements as m'))
            ->selectRaw('COALESCE(SUM(m.qty_masuk - m.qty_keluar), 0) AS s')->value('s');

        $masuk  = (int) $periode->masuk;
        $keluar = (int) $periode->keluar;

        return [
            'saldo_awal'  => $awal,
            'masuk'       => $masuk,
            'keluar'      => $keluar,
            'saldo_akhir' => $awal + $masuk - $keluar,
            'saldo_total' => $total,
            'stok_aktual' => (int) $info->stock,
            'selisih'     => (int) $info->stock - $total,
            'cocok'       => $total === (int) $info->stock,
        ];
    }

    /** Baris kronologis dengan saldo berjalan (fungsi window dihitung dari mutasi pertama, bukan dari awal periode). */
    private function kartuQuery(): QueryBuilder
    {
        $semua = $this->kartuFilter(DB::table('stock_movements as m')->leftJoin('users as us', 'us.id', '=', 'm.user_id'))
            ->selectRaw('m.id AS id, m.tanggal AS tanggal, m.tipe AS tipe, m.keterangan AS keterangan,'
                . ' m.ref_type AS ref_type, m.ref_id AS ref_id, m.ref_code AS ref_code,'
                . ' m.qty_masuk AS masuk, m.qty_keluar AS keluar, us.name AS oleh,'
                . ' SUM(m.qty_masuk - m.qty_keluar) OVER (ORDER BY m.tanggal, m.id) AS saldo');

        return DB::query()
            ->fromSub($semua, 'k')
            ->where('k.tanggal', '>=', $this->awal())
            ->where('k.tanggal', '<', $this->batas())
            ->orderBy('k.tanggal')
            ->orderBy('k.id');
    }

    public function kartu(int $perPage = 25)
    {
        $paginator = $this->kartuQuery()
            ->paginate($perPage, ['*'], 'halaman_kartu')
            ->withQueryString()
            ->appends(['tab' => 'mutasi']);

        $this->lengkapiKartu($paginator->getCollection());

        return $paginator;
    }

    /** Untuk export kartu: semua baris pada periode. */
    public function kartuSemua(): Collection
    {
        return $this->lengkapiKartu($this->kartuQuery()->get());
    }

    // ------------------------------------------------------------------ pelengkap hasil

    /** Tambah kunci barang, label varian (dari ProductVariant::label()), dan ubah angka ke tipe yang benar. */
    private function lengkapi(Collection $rows): Collection
    {
        $ids = $rows->filter(fn ($r) => (int) $r->vid > 0)->pluck('vid')->unique()->values()->all();

        $label = $ids
            ? ProductVariant::query()->whereIn('id', $ids)->get()->mapWithKeys(fn ($v) => [$v->id => $v->label()])
            : collect();

        return $rows->each(function ($r) use ($label) {
            $r->vid    = (int) $r->vid;
            $r->key    = $r->vid > 0 ? 'v:' . $r->vid : 'p:' . $r->product_id;
            $r->varian = $r->vid > 0 ? ($label[$r->vid] ?? null) : null;

            foreach (['stock', 'stok_minimum', 'stok_aktual', 'saldo_awal', 'masuk', 'keluar', 'saldo_akhir', 'saldo_total', 'tidak_cocok'] as $k) {
                if (isset($r->{$k})) {
                    $r->{$k} = (int) $r->{$k};
                }
            }

            if (property_exists($r, 'last_cost')) {
                $r->last_cost = $r->last_cost === null ? null : (float) $r->last_cost;
            }
            if (property_exists($r, 'nilai')) {
                $r->nilai = $r->nilai === null ? null : (int) round((float) $r->nilai);
            }
            if (isset($r->status_rank)) {
                $r->status = self::STATUS_LABEL[(int) $r->status_rank];
            }
        });
    }

    /** Tambah label jenis mutasi dan penanda apakah dokumen referensinya masih ada (untuk link). */
    private function lengkapiKartu(Collection $rows): Collection
    {
        $idTerjual   = $rows->where('ref_type', 'terjual')->pluck('ref_id')->filter()->unique()->values()->all();
        $idPurchase  = $rows->where('ref_type', 'purchase')->pluck('ref_id')->filter()->unique()->values()->all();

        $adaTerjual  = $idTerjual ? Terjual::query()->whereIn('id', $idTerjual)->pluck('id')->flip() : collect();
        $adaPurchase = $idPurchase ? Purchase::query()->whereIn('id', $idPurchase)->pluck('id')->flip() : collect();

        return $rows->each(function ($r) use ($adaTerjual, $adaPurchase) {
            $r->masuk       = (int) $r->masuk;
            $r->keluar      = (int) $r->keluar;
            $r->saldo       = (int) $r->saldo;
            $r->tipe_label  = StockLedger::TIPE_LABEL[$r->tipe] ?? $r->tipe;
            $r->ref_ada     = $r->ref_id && (
                ($r->ref_type === 'terjual' && $adaTerjual->has($r->ref_id))
                || ($r->ref_type === 'purchase' && $adaPurchase->has($r->ref_id))
            );
        });
    }

    // ------------------------------------------------------------------ pilihan filter

    public function kategoriOptions(): Collection
    {
        return Category::query()->orderBy('name')->get(['id', 'name']);
    }

    /**
     * Barang yang punya riwayat mutasi, dikelompokkan per kategori di dropdown.
     *
     * @return Collection<int, array{key:string, label:string, kategori:string}>
     */
    public function barangOptions(): Collection
    {
        $rows = $this->unitBase(false)
            ->whereExists(fn ($e) => $e->selectRaw('1')
                ->from('stock_movements as m')
                ->whereColumn('m.product_id', 'u.product_id')
                ->whereRaw('COALESCE(m.product_variant_id, 0) = u.vid'))
            ->select('u.product_id', 'u.vid', 'u.nama', 'u.kategori')
            ->orderBy('u.kategori')->orderBy('u.nama')->orderBy('u.vid')
            ->get();

        return $this->lengkapi($rows)->map(fn ($r) => [
            'key'      => $r->key,
            'label'    => $r->nama . ($r->varian ? ' — ' . $r->varian : ''),
            'kategori' => $r->kategori,
        ]);
    }

    // ------------------------------------------------------------------ header file

    /** @return array{toko:string, judul:string, periode:string, dicetak:string, oleh:string, filter:string} */
    public function meta(string $judul, bool $denganPeriode = true): array
    {
        return [
            'toko'    => (string) config('app.name'),
            'judul'   => $judul,
            'periode' => $denganPeriode ? $this->periode->label() : 'Kondisi stok per ' . Format::tanggal(now()),
            'dicetak' => Format::waktu(now()),
            'oleh'    => auth()->user()?->name ?? '-',
            'filter'  => $this->filterTeks(),
        ];
    }

    public function filterTeks(): string
    {
        $bagian = [];

        if ($this->kategoriId) {
            $bagian[] = 'Kategori: ' . (Category::query()->whereKey($this->kategoriId)->value('name') ?? '-');
        }
        if ($this->adaKartu()) {
            $info     = $this->kartuInfo();
            $bagian[] = 'Barang: ' . ($info ? $info->nama . ($info->varian ? ' — ' . $info->varian : '') : '-');
        }
        if ($this->status !== null) {
            $bagian[] = 'Status: ' . ucfirst($this->status);
        }
        if ($this->cari !== null && $this->cari !== '') {
            $bagian[] = 'Cari: "' . $this->cari . '"';
        }

        return $bagian ? implode('; ', $bagian) : 'Tanpa filter tambahan';
    }
}