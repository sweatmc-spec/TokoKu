<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $meta['judul'] }} - {{ $meta['periode'] }}</title>
    <style>
        /* dompdf: CSS 2.1 saja (tanpa flexbox/grid). DejaVu Sans mendukung karakter Unicode, mis. tanda "–". */
        @page { margin: 28px 32px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #111827; }
        h3 { font-size: 11px; margin: 16px 0 6px 0; padding-bottom: 3px; border-bottom: 1px solid #d1d5db; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th { background: #e5e7eb; text-align: left; padding: 5px 6px; font-size: 9px; border: 1px solid #d1d5db; }
        table.data td { padding: 4px 6px; border: 1px solid #e5e7eb; }
        table.data tfoot td { font-weight: bold; background: #f3f4f6; }
        .r { text-align: right; }
        .pos { color: #15803d; }
        .neg { color: #b91c1c; }
        .muted { color: #6b7280; }
        .note { margin-top: 12px; padding: 6px 8px; border: 1px dashed #9ca3af; font-size: 8.5px; color: #4b5563; }
        .kartu td { width: 20%; padding: 7px; border: 1px solid #d1d5db; vertical-align: top; }
        .kartu .nilai { font-size: 11.5px; font-weight: bold; margin: 3px 0; }
        .kartu .label { font-size: 8px; color: #6b7280; }
    </style>
</head>
<body>
    @include('Laporan.partials._header_cetak', ['meta' => $meta])

    @php
        $rp      = fn ($n) => \App\Support\Laporan\Format::rupiah($n);
        $pct     = fn ($p) => \App\Support\Laporan\Format::persen($p);
        $r       = $ringkasan;
        $labaAda = $r['margin'] !== null;
    @endphp

    {{-- ===== Ringkasan ===== --}}
    <h3>Ringkasan</h3>
    <table class="kartu" width="100%" cellspacing="0" cellpadding="0" style="border-collapse: collapse;">
        <tr>
            <td>
                <div class="label">Total Penjualan</div>
                <div class="nilai">{{ $rp($r['penjualan']) }}</div>
                <div class="label">{{ $pct($r['pct_penjualan']) }} vs periode lalu</div>
            </td>
            <td>
                <div class="label">Jumlah Transaksi</div>
                <div class="nilai">{{ number_format($r['transaksi'], 0, ',', '.') }}</div>
                <div class="label">{{ $pct($r['pct_transaksi']) }} vs periode lalu</div>
            </td>
            <td>
                <div class="label">Rata-rata per Transaksi</div>
                <div class="nilai">{{ $rp($r['rata']) }}</div>
                <div class="label">{{ $pct($r['pct_rata']) }} vs periode lalu</div>
            </td>
            <td>
                <div class="label">Total Qty Terjual</div>
                <div class="nilai">{{ number_format($r['qty'], 0, ',', '.') }} pcs</div>
                <div class="label">{{ $pct($r['pct_qty']) }} vs periode lalu</div>
            </td>
            <td>
                <div class="label">Laba Kotor</div>
                @if ($labaAda)
                    <div class="nilai {{ $r['laba'] >= 0 ? 'pos' : 'neg' }}">{{ $rp($r['laba']) }}</div>
                    <div class="label">Margin {{ \App\Support\Laporan\Format::porsi($r['margin']) }}<br>{{ $pct($r['pct_laba']) }} vs periode lalu</div>
                @else
                    <div class="nilai muted">-</div>
                    <div class="label">Modal belum tercatat</div>
                @endif
            </td>
        </tr>
    </table>

    @if ($r['baris_tanpa_modal'] > 0)
        <div class="note">
            {{ number_format($r['baris_tanpa_modal'], 0, ',', '.') }} baris barang belum punya harga modal
            (nilai penjualan {{ $rp($r['penjualan_tanpa_modal']) }}). Penjualannya tetap dihitung, tetapi tidak ikut dalam laba.
        </div>
    @endif

    {{-- ===== Per Barang ===== --}}
    <h3>Penjualan per barang</h3>
    <table class="data">
        <thead>
            <tr>
                <th style="width:24px">No</th>
                <th>Barang</th>
                <th class="r">Qty</th>
                <th class="r">Penjualan</th>
                <th class="r">Modal</th>
                <th class="r">Laba</th>
                <th class="r">Margin</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($barang as $b)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $b->nama }}@if ($b->varian)<br><span class="muted">{{ $b->varian }}</span>@endif</td>
                    <td class="r">{{ number_format($b->qty, 0, ',', '.') }}</td>
                    <td class="r">{{ $rp($b->penjualan) }}</td>
                    <td class="r">{{ $b->laba === null ? '-' : $rp($b->modal_int) }}</td>
                    <td class="r {{ $b->laba === null ? 'muted' : ($b->laba >= 0 ? 'pos' : 'neg') }}">
                        {{ $b->laba === null ? '-' : $rp($b->laba) }}@if ($b->sebagian)<br><span class="muted">sebagian</span>@endif
                    </td>
                    <td class="r">{{ $b->margin === null ? '-' : \App\Support\Laporan\Format::porsi($b->margin) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="muted" align="center">Tidak ada penjualan pada periode ini.</td></tr>
            @endforelse
        </tbody>
        @if ($barang->count() > 0)
            <tfoot>
                <tr>
                    <td colspan="2">Total semua barang</td>
                    <td class="r">{{ number_format($r['qty'], 0, ',', '.') }}</td>
                    <td class="r">{{ $rp($r['penjualan']) }}</td>
                    <td class="r">{{ $labaAda ? $rp($r['modal']) : '-' }}</td>
                    <td class="r">{{ $labaAda ? $rp($r['laba']) : '-' }}</td>
                    <td class="r">{{ $labaAda ? \App\Support\Laporan\Format::porsi($r['margin']) : '-' }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

    <div class="note">
        Penjualan dihitung setelah diskon; diskon transaksi dibagi ke tiap barang sesuai nilainya.
        Laba kotor = penjualan dikurangi harga modal (harga beli per pcs saat transaksi).
        Hanya baris barang yang harga modalnya diketahui yang ikut dalam laba.
    </div>
</body>
</html>
