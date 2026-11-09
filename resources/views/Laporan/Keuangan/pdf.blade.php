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
        .masuk { color: #15803d; }
        .keluar { color: #b91c1c; }
        .muted { color: #6b7280; }
        .note { margin-top: 14px; padding: 6px 8px; border: 1px dashed #9ca3af; font-size: 8.5px; color: #4b5563; }
        .kartu td { width: 25%; padding: 8px; border: 1px solid #d1d5db; vertical-align: top; }
        .kartu .nilai { font-size: 13px; font-weight: bold; margin: 3px 0; }
        .kartu .label { font-size: 8.5px; color: #6b7280; }
    </style>
</head>
<body>
    @include('Laporan.partials._header_cetak', ['meta' => $meta])

    @php
        $rp   = fn ($n) => \App\Support\Laporan\Format::rupiah($n);
        $r    = $ringkasan;
        $laba = $r['selisih'] >= 0;
    @endphp

    {{-- ===== Ringkasan ===== --}}
    <h3>Ringkasan</h3>
    <table class="kartu" width="100%" cellspacing="0" cellpadding="0" style="border-collapse: collapse;">
        <tr>
            <td>
                <div class="label">Total Pemasukan</div>
                <div class="nilai masuk">{{ $rp($r['pemasukan']) }}</div>
                <div class="label">{{ \App\Support\Laporan\Format::persen($r['pct_pemasukan']) }} vs periode lalu</div>
            </td>
            <td>
                <div class="label">Total Pengeluaran</div>
                <div class="nilai keluar">{{ $rp($r['pengeluaran']) }}</div>
                <div class="label">{{ \App\Support\Laporan\Format::persen($r['pct_pengeluaran']) }} vs periode lalu</div>
            </td>
            <td>
                <div class="label">Selisih ({{ $laba ? 'Laba' : 'Rugi' }})</div>
                <div class="nilai {{ $laba ? 'masuk' : 'keluar' }}">{{ $rp($r['selisih']) }}</div>
                <div class="label">{{ $laba ? 'Uang masuk lebih besar' : 'Uang keluar lebih besar' }}</div>
            </td>
            <td>
                <div class="label">Perubahan selisih</div>
                <div class="nilai">{{ \App\Support\Laporan\Format::persen($r['pct_selisih']) }}</div>
                <div class="label">Sebelumnya {{ $rp($r['selisih_lalu']) }}<br>({{ $r['label_lalu'] }})</div>
            </td>
        </tr>
    </table>

    {{-- ===== Pemasukan per sumber ===== --}}
    <h3>Pemasukan per sumber</h3>
    <table class="data">
        <thead>
            <tr><th>Sumber</th><th class="r">Transaksi</th><th class="r">Total</th><th class="r">Persentase</th></tr>
        </thead>
        <tbody>
            @foreach ($perSumber as $s)
                <tr>
                    <td>{{ $s['label'] }}</td>
                    <td class="r">{{ number_format($s['jumlah'], 0, ',', '.') }}</td>
                    <td class="r masuk">{{ $rp($s['total']) }}</td>
                    <td class="r">{{ \App\Support\Laporan\Format::porsi($s['persen']) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr><td>Total</td><td class="r"></td><td class="r masuk">{{ $rp($r['pemasukan']) }}</td><td class="r">{{ $r['pemasukan'] > 0 ? '100%' : '-' }}</td></tr>
        </tfoot>
    </table>

    {{-- ===== Pengeluaran per kategori ===== --}}
    <h3>Pengeluaran per kategori</h3>
    <table class="data">
        <thead>
            <tr><th>Kategori</th><th class="r">Transaksi</th><th class="r">Total</th><th class="r">Persentase</th></tr>
        </thead>
        <tbody>
            @forelse ($perKategori as $k)
                <tr>
                    <td>{{ $k['nama'] }}</td>
                    <td class="r">{{ number_format($k['jumlah'], 0, ',', '.') }}</td>
                    <td class="r keluar">{{ $rp($k['total']) }}</td>
                    <td class="r">{{ \App\Support\Laporan\Format::porsi($k['persen']) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="muted" align="center">Tidak ada pengeluaran pada periode ini.</td></tr>
            @endforelse
        </tbody>
        @if ($perKategori)
            <tfoot>
                <tr><td>Total</td><td class="r"></td><td class="r keluar">{{ $rp($r['pengeluaran']) }}</td><td class="r">100%</td></tr>
            </tfoot>
        @endif
    </table>

    {{-- ===== Per bulan ===== --}}
    <h3>Ringkasan per bulan, tahun {{ $perBulan['tahun'] }}</h3>
    <table class="data">
        <thead>
            <tr><th>Bulan</th><th class="r">Pemasukan</th><th class="r">Pengeluaran</th><th class="r">Selisih</th></tr>
        </thead>
        <tbody>
            @foreach ($perBulan['rows'] as $b)
                @php $kosong = $b['pemasukan'] === 0 && $b['pengeluaran'] === 0; @endphp
                <tr class="{{ $kosong ? 'muted' : '' }}">
                    <td>{{ $b['label'] }}</td>
                    <td class="r">{{ $kosong ? '-' : $rp($b['pemasukan']) }}</td>
                    <td class="r">{{ $kosong ? '-' : $rp($b['pengeluaran']) }}</td>
                    <td class="r {{ $kosong ? '' : ($b['selisih'] >= 0 ? 'masuk' : 'keluar') }}">{{ $kosong ? '-' : $rp($b['selisih']) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td>Total {{ $perBulan['tahun'] }}</td>
                <td class="r masuk">{{ $rp($perBulan['total']['pemasukan']) }}</td>
                <td class="r keluar">{{ $rp($perBulan['total']['pengeluaran']) }}</td>
                <td class="r {{ $perBulan['total']['selisih'] >= 0 ? 'masuk' : 'keluar' }}">{{ $rp($perBulan['total']['selisih']) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="note">
        Laporan ini adalah laba kas sederhana (selisih uang masuk dan uang keluar), bukan laporan akuntansi lengkap.
        Bulan ketika kulakan besar bisa tampak rugi walau barangnya masih ada di stok.
    </div>
</body>
</html>
