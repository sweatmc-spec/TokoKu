<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $meta['judul'] }}</title>
    <style>
        /* dompdf: CSS 2.1 saja (tanpa flexbox/grid). DejaVu Sans mendukung karakter Unicode, mis. tanda "—". */
        @page { margin: 28px 32px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #111827; }
        h3 { font-size: 11px; margin: 16px 0 6px 0; padding-bottom: 3px; border-bottom: 1px solid #d1d5db; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th { background: #e5e7eb; text-align: left; padding: 5px 6px; font-size: 9px; border: 1px solid #d1d5db; }
        table.data td { padding: 4px 6px; border: 1px solid #e5e7eb; }
        table.data tfoot td { font-weight: bold; background: #f3f4f6; }
        .r { text-align: right; }
        .c { text-align: center; }
        .pos { color: #15803d; }
        .warn { color: #b45309; }
        .neg { color: #b91c1c; }
        .muted { color: #6b7280; }
        .note { margin-top: 12px; padding: 6px 8px; border: 1px dashed #9ca3af; font-size: 8.5px; color: #4b5563; }
        .kartu td { width: 25%; padding: 8px; border: 1px solid #d1d5db; vertical-align: top; }
        .kartu .nilai { font-size: 13px; font-weight: bold; margin: 3px 0; }
        .kartu .label { font-size: 8.5px; color: #6b7280; }
        .lencana { font-size: 8px; font-weight: bold; padding: 1px 5px; }
    </style>
</head>
<body>
    @include('Laporan.partials._header_cetak', ['meta' => $meta])

    @php
        $rp = fn ($n) => \App\Support\Laporan\Format::rupiah($n);
        $r  = $ringkasan;
        $nf = fn ($n) => number_format($n, 0, ',', '.');
    @endphp

    {{-- ===== Ringkasan ===== --}}
    <h3>Ringkasan</h3>
    <table class="kartu" width="100%" cellspacing="0" cellpadding="0" style="border-collapse: collapse;">
        <tr>
            <td>
                <div class="label">Jumlah Jenis Barang</div>
                <div class="nilai">{{ $nf($r['jenis']) }}</div>
                <div class="label">Total stok {{ $nf($r['total_pcs']) }} pcs</div>
            </td>
            <td>
                <div class="label">Barang Menipis</div>
                <div class="nilai {{ $r['menipis'] > 0 ? 'warn' : '' }}">{{ $nf($r['menipis']) }}</div>
                <div class="label">Stok di atas 0 sampai batas minimum</div>
            </td>
            <td>
                <div class="label">Barang Habis</div>
                <div class="nilai {{ $r['habis'] > 0 ? 'neg' : '' }}">{{ $nf($r['habis']) }}</div>
                <div class="label">Stok 0</div>
            </td>
            <td>
                <div class="label">Total Nilai Stok</div>
                <div class="nilai pos">{{ $rp($r['nilai']) }}</div>
                <div class="label">
                    Stok x harga beli terakhir
                    @if ($r['tanpa_harga'] > 0)<br>{{ $r['tanpa_harga'] }} barang tanpa harga beli@endif
                </div>
            </td>
        </tr>
    </table>

    @if ($r['tidak_cocok'] > 0)
        <div class="note">
            {{ $nf($r['tidak_cocok']) }} barang punya saldo mutasi yang berbeda dengan stok aktual
            (stok diubah di luar Cek Paket dan Terjual). Rinciannya ada di tab Mutasi Stok pada halaman laporan.
        </div>
    @endif

    {{-- ===== Stok Saat Ini ===== --}}
    <h3>Stok saat ini (habis dan menipis di atas)</h3>
    <table class="data">
        <thead>
            <tr>
                <th style="width:24px">No</th>
                <th>Barang</th>
                <th>Kategori</th>
                <th class="r">Stok</th>
                <th class="r">Min</th>
                <th class="r">Harga beli</th>
                <th class="r">Nilai stok</th>
                <th class="c">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($stok as $b)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $b->nama }}@if ($b->varian)<br><span class="muted">{{ $b->varian }}</span>@endif</td>
                    <td>{{ $b->kategori }}</td>
                    <td class="r {{ $b->status === 'habis' ? 'neg' : ($b->status === 'menipis' ? 'warn' : '') }}"><strong>{{ $nf($b->stock) }}</strong></td>
                    <td class="r muted">{{ $nf($b->stok_minimum) }}</td>
                    <td class="r">{{ $b->last_cost === null ? '-' : 'Rp ' . number_format($b->last_cost, 2, ',', '.') }}</td>
                    <td class="r">{{ $b->nilai === null ? '-' : $rp($b->nilai) }}</td>
                    <td class="c {{ $b->status === 'habis' ? 'neg' : ($b->status === 'menipis' ? 'warn' : 'pos') }}">
                        <strong>{{ ucfirst($b->status) }}</strong>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="muted" align="center">Tidak ada barang yang cocok dengan filter.</td></tr>
            @endforelse
        </tbody>
        @if ($stok->count() > 0)
            <tfoot>
                <tr>
                    <td colspan="3">Total ({{ $nf($stokTotal['jumlah']) }} jenis)</td>
                    <td class="r">{{ $nf($stokTotal['pcs']) }}</td>
                    <td></td>
                    <td></td>
                    <td class="r">{{ $rp($stokTotal['nilai']) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        @endif
    </table>

    <div class="note">
        Stok dalam pcs. Nilai stok = stok x harga beli terakhir per pcs; barang tanpa harga beli tidak dihitung.
        Menipis = stok di atas 0 dan sampai batas minimum barang; Habis = stok 0.
    </div>
</body>
</html>