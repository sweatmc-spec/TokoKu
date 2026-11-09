<?php

namespace App\Support\Laporan;

use Carbon\CarbonImmutable;

/**
 * Periode laporan beserta periode pembandingnya. Satu class untuk ketiga laporan
 * (Keuangan, Penjualan, Stok), jadi aturan "bulan ini", "7 hari terakhir", dst. selalu sama.
 *
 * Periode pembanding dibuat adil (apple to apple):
 *  - Hari ini   : kemarin
 *  - 7 hari     : 7 hari tepat sebelumnya
 *  - Bulan ini  : tanggal yang sama bulan lalu (1-5 Okt dibanding 1-5 Sep), bukan seluruh bulan lalu
 *  - Bulan lalu : bulan sebelumnya (satu bulan penuh)
 *  - Tahun ini  : tanggal yang sama tahun lalu
 *  - Rentang    : rentang sama panjang yang tepat sebelumnya
 *
 * "Hari ini" mengikuti APP_TIMEZONE (set Asia/Jakarta di .env).
 */
final class PeriodeLaporan
{
    public const PRESETS = [
        'hari-ini'   => 'Hari ini',
        '7-hari'     => '7 hari terakhir',
        'bulan-ini'  => 'Bulan ini',
        'bulan-lalu' => 'Bulan lalu',
        'tahun-ini'  => 'Tahun ini',
        'rentang'    => 'Rentang tanggal',
    ];

    public const DEFAULT = 'bulan-ini';

    private function __construct(
        public readonly string $key,
        public readonly CarbonImmutable $from,
        public readonly CarbonImmutable $to,
        public readonly CarbonImmutable $prevFrom,
        public readonly CarbonImmutable $prevTo,
    ) {
    }

    /**
     * @param  string|null  $dari    Y-m-d, dipakai kalau $key = 'rentang'
     * @param  string|null  $sampai  Y-m-d, dipakai kalau $key = 'rentang'
     */
    public static function make(?string $key = null, ?string $dari = null, ?string $sampai = null): self
    {
        $key = array_key_exists((string) $key, self::PRESETS) ? $key : self::DEFAULT;

        // rentang tanpa tanggal lengkap: jatuh ke default, bukan error
        if ($key === 'rentang' && (! $dari || ! $sampai)) {
            $key = self::DEFAULT;
        }

        $today = CarbonImmutable::today();

        if ($key === 'hari-ini') {
            $from = $to = $today;
            $prevFrom = $prevTo = $today->subDay();
        } elseif ($key === '7-hari') {
            $from     = $today->subDays(6);
            $to       = $today;
            $prevTo   = $from->subDay();
            $prevFrom = $prevTo->subDays(6);
        } elseif ($key === 'bulan-lalu') {
            $from     = $today->subMonthNoOverflow()->startOfMonth();
            $to       = $from->endOfMonth()->startOfDay();
            $prevFrom = $from->subMonthNoOverflow()->startOfMonth();
            $prevTo   = $prevFrom->endOfMonth()->startOfDay();
        } elseif ($key === 'tahun-ini') {
            $from     = $today->startOfYear();
            $to       = $today;
            $prevFrom = $from->subYear();
            $prevTo   = $today->subYearNoOverflow();
        } elseif ($key === 'rentang') {
            $from = CarbonImmutable::parse($dari)->startOfDay();
            $to   = CarbonImmutable::parse($sampai)->startOfDay();

            if ($to->lt($from)) {
                [$from, $to] = [$to, $from];
            }

            $panjang  = abs((int) $from->diffInDays($to)) + 1;
            $prevTo   = $from->subDay();
            $prevFrom = $prevTo->subDays($panjang - 1);
        } else {                                       // bulan-ini
            $from     = $today->startOfMonth();
            $to       = $today;
            $prevFrom = $from->subMonthNoOverflow()->startOfMonth();
            $kandidat = $prevFrom->addDays($today->day - 1);        // tanggal yang sama bulan lalu
            $akhir    = $prevFrom->endOfMonth()->startOfDay();      // mis. 31 Okt vs Sep yang hanya 30 hari
            $prevTo   = $kandidat->gt($akhir) ? $akhir : $kandidat;
        }

        return new self($key, $from, $to, $prevFrom, $prevTo);
    }

    public function fromDate(): string
    {
        return $this->from->toDateString();
    }

    public function toDate(): string
    {
        return $this->to->toDateString();
    }

    public function prevFromDate(): string
    {
        return $this->prevFrom->toDateString();
    }

    public function prevToDate(): string
    {
        return $this->prevTo->toDateString();
    }

    public function presetLabel(): string
    {
        return self::PRESETS[$this->key];
    }

    /** "05 Okt 2026" atau "01 Okt 2026 – 05 Okt 2026" */
    public function label(): string
    {
        return self::rentang($this->from, $this->to);
    }

    public function prevLabel(): string
    {
        return self::rentang($this->prevFrom, $this->prevTo);
    }

    /** Jumlah hari dalam periode (termasuk hari pertama dan terakhir). */
    public function days(): int
    {
        return abs((int) $this->from->diffInDays($this->to)) + 1;
    }

    /** Parameter query string untuk membangun ulang periode ini (link, export). */
    public function queryParams(): array
    {
        return $this->key === 'rentang'
            ? ['periode' => $this->key, 'dari' => $this->fromDate(), 'sampai' => $this->toDate()]
            : ['periode' => $this->key];
    }

    private static function rentang(CarbonImmutable $a, CarbonImmutable $b): string
    {
        return $a->isSameDay($b)
            ? Format::tanggal($a)
            : Format::tanggal($a) . ' – ' . Format::tanggal($b);
    }
}
