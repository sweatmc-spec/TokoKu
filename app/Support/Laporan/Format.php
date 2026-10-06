<?php

namespace App\Support\Laporan;

use Carbon\CarbonInterface;

/**
 * Format tampilan yang sama untuk layar, Excel, dan PDF di semua laporan:
 * "Rp 1.250.000", "05 Okt 2026", "+12,5%".
 * Nama bulan ditulis sendiri supaya tidak bergantung pada locale Carbon / ekstensi intl di server.
 */
final class Format
{
    private const BULAN_PENDEK = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    private const SUMBER = [
        'penjualan'   => 'Penjualan',
        'manual'      => 'Manual',
        'pembelian'   => 'Pembelian',
        'operasional' => 'Operasional',
    ];

    /** "Rp 1.250.000"; angka negatif ditulis "-Rp 1.250.000". */
    public static function rupiah(int|float|string|null $nominal): string
    {
        $n = (int) round((float) $nominal);

        return ($n < 0 ? '-' : '') . 'Rp ' . number_format(abs($n), 0, ',', '.');
    }

    /** "05 Okt 2026" */
    public static function tanggal(CarbonInterface|string|null $tanggal): string
    {
        if ($tanggal === null || $tanggal === '') {
            return '-';
        }

        $d = $tanggal instanceof CarbonInterface ? $tanggal : \Carbon\Carbon::parse($tanggal);

        return sprintf('%02d %s %d', $d->day, self::bulanPendek($d->month), $d->year);
    }

    /** "05 Okt 2026 14:30" */
    public static function waktu(CarbonInterface $waktu): string
    {
        return self::tanggal($waktu) . ' ' . $waktu->format('H:i');
    }

    public static function bulanPendek(int $bulan): string
    {
        return self::BULAN_PENDEK[$bulan - 1] ?? '-';
    }

    /** "+12,5%", "-4,0%", atau "-" kalau tidak bisa dihitung (null). */
    public static function persen(?float $persen): string
    {
        if ($persen === null) {
            return '-';
        }

        return ($persen > 0 ? '+' : '') . number_format($persen, 1, ',', '.') . '%';
    }

    /** Porsi (tanpa tanda plus), mis. "45,5%". */
    public static function porsi(float $persen): string
    {
        return number_format($persen, 1, ',', '.') . '%';
    }

    public static function sumber(?string $kode): string
    {
        return self::SUMBER[$kode] ?? ($kode ?: '-');
    }

    /**
     * Teks bebas dari user (keterangan, nama customer) yang masuk ke Excel: awalan = + - @ dianggap
     * rumus oleh Excel. Diberi tanda petik supaya tampil sebagai teks biasa (mencegah formula injection).
     */
    public static function amanExcel(?string $teks): string
    {
        $teks = (string) $teks;

        return preg_match('/^[=+\-@\t\r]/', $teks) ? "'" . $teks : $teks;
    }
}
