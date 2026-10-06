<?php

namespace App\Models\Concerns;

/**
 * Angka untuk kartu ringkasan di halaman Pemasukan dan Pengeluaran.
 * Dipakai oleh model yang punya kolom "tanggal" dan "nominal".
 * "Hari ini" mengikuti APP_TIMEZONE (set ke Asia/Jakarta di .env).
 */
trait RingkasanKeuangan
{
    /**
     * @return array{today:int, month:int, last_month:int, change:?float, days_elapsed:int}
     *         change = persen perubahan bulan ini dibanding bulan lalu (null kalau bulan lalu 0)
     */
    public static function ringkasan(): array
    {
        $today      = today();
        $monthStart = $today->copy()->startOfMonth();
        $monthEnd   = $today->copy()->endOfMonth();
        $lastStart  = $monthStart->copy()->subMonthNoOverflow();
        $lastEnd    = $lastStart->copy()->endOfMonth();

        $sum = fn ($from, $to) => (int) static::query()
            ->whereBetween('tanggal', [$from->toDateString(), $to->toDateString()])
            ->sum('nominal');

        $month = $sum($monthStart, $monthEnd);
        $last  = $sum($lastStart, $lastEnd);

        return [
            'today'        => $sum($today, $today),
            'month'        => $month,
            'last_month'   => $last,
            'change'       => $last > 0 ? round(($month - $last) / $last * 100, 1) : null,
            'days_elapsed' => $today->day,
        ];
    }
}
