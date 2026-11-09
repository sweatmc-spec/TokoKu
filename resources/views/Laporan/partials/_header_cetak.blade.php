{{--
    Header PDF bersama untuk semua laporan: nama toko, judul, periode, tanggal cetak, nama pencetak.
    Variabel: $meta (dari LaporanXxxService::meta())
    Memakai <table> dan CSS sederhana karena dompdf tidak mendukung flexbox.
--}}
<table width="100%" cellspacing="0" cellpadding="0" style="border-bottom: 2px solid #1f2937; margin-bottom: 8px;">
    <tr>
        <td valign="bottom" style="padding-bottom: 6px;">
            <div style="font-size: 17px; font-weight: bold;">{{ $meta['toko'] }}</div>
            <div style="font-size: 13px; margin-top: 2px;">{{ $meta['judul'] }}</div>
        </td>
        <td valign="bottom" align="right" style="padding-bottom: 6px; font-size: 9px; color: #4b5563;">
            Dicetak: {{ $meta['dicetak'] }}<br>
            Oleh: {{ $meta['oleh'] }}
        </td>
    </tr>
</table>

<div style="font-size: 10px; margin-bottom: 2px;"><strong>Periode:</strong> {{ $meta['periode'] }}</div>
<div style="font-size: 9px; color: #4b5563; margin-bottom: 10px;"><strong>Filter:</strong> {{ $meta['filter'] }}</div>
