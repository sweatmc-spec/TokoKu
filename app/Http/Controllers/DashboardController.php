<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\Pengajuan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    /* ------------------------------------------------------------------
     | PENGATURAN — sesuaikan dengan data Anda
     | ------------------------------------------------------------------ */

    /** Nilai kolom `type` di tabel absensis. Cek isi tabel Anda! */
    private const TYPE_MASUK  = 'masuk';
    private const TYPE_PULANG = 'pulang';

    /** true = hanya absensi dengan is_valid = 1 yang dihitung hadir. */
    private const HANYA_ABSENSI_VALID = true;

    /** Role yang melihat dashboard versi admin (tidak peka huruf besar/kecil). */
    private const ROLE_ADMIN = ['admin', 'super-admin', 'super admin'];

    /** Urutan status (juga urutan legend grafik). Nama harus sama dengan key `warna` di JS view. */
    private const STATUS_ORDER = [
        'Hadir', 'Lupa absen pulang', 'Sakit / Izin', 'Menunggu',
        'Tanpa keterangan', 'Libur', 'Belum aktif',
    ];

    private const BADGE = [
        'Hadir'             => 'badge-light-success',
        'Lupa absen pulang' => 'badge-light-warning',
        'Sakit / Izin'      => 'badge-light-info',
        'Menunggu'          => 'badge-light-warning',
        'Belum Absen'       => 'badge-light-danger',
        'Tanpa keterangan'  => 'badge-light-danger',
        'Libur'             => 'badge-light',
        'Belum aktif'       => 'badge-light',
    ];

    public function index(Request $request)
    {
        $user  = $request->user();
        $today = now()->startOfDay();

        $roles   = method_exists($user, 'getRoleNames')
            ? $user->getRoleNames()->map(fn ($r) => strtolower($r))
            : collect();
        $isAdmin = $roles->intersect(self::ROLE_ADMIN)->isNotEmpty();

        // ---- Data pribadi (dipakai admin maupun karyawan) ----
        $days = $this->personalDays($user, $today);
        $monthKey = $today->copy()->startOfMonth()->toDateString();

        $monthDays = $days->filter(fn ($r, $k) => $k >= $monthKey);
        // Hari yang dihitung: bukan libur / belum aktif, dan bukan "hari ini yang belum absen".
        $counted = $monthDays->reject(fn ($r) => in_array($r['status'], ['Libur', 'Belum aktif'], true)
            || ($r['status'] === 'Menunggu' && $r['is_today']));

        $hadir     = $counted->whereIn('status', ['Hadir', 'Lupa absen pulang'])->count();
        $hariKerja = $counted->count();

        $myStats = [
            'hadir'            => $hadir,
            'hari_kerja'       => $hariKerja,
            'sakit'            => $counted->where('kind', 'sakit')->count(),
            'izin'             => $counted->where('kind', 'izin')->count(),
            'tanpa_keterangan' => $counted->where('status', 'Tanpa keterangan')->count(),
            'lupa_pulang'      => $counted->where('status', 'Lupa absen pulang')->count(),
            'jam_kerja'        => $this->formatDurasi((int) $monthDays->sum('menit')),
            'persen'           => $hariKerja > 0 ? $hadir / $hariKerja * 100 : 0,
        ];

        $todayRow = $days[$today->toDateString()];
        $todayLabel = $this->label($todayRow);
        $attendanceToday = [
            'label'  => $todayLabel,
            'badge'  => $this->badge($todayLabel),
            'masuk'  => $todayRow['masuk']?->format('H:i'),
            'pulang' => $todayRow['pulang']?->format('H:i'),
        ];

        $attendanceChart = $this->buildWeekChart($days, $today);

        $statusCounts = $counted->groupBy('status')->map->count();
        $statusLabels = collect(self::STATUS_ORDER)
            ->reject(fn ($s) => in_array($s, ['Libur', 'Belum aktif'], true))
            ->filter(fn ($s) => ($statusCounts[$s] ?? 0) > 0)
            ->values();
        $statusChart = [
            'labels' => $statusLabels->all(),
            'values' => $statusLabels->map(fn ($s) => (int) $statusCounts[$s])->all(),
        ];

        // ---- Riwayat 10 hari terakhir (karyawan) ----
        $myRecent = $days
            ->reject(fn ($r) => in_array($r['status'], ['Libur', 'Belum aktif'], true))
            ->sortKeysDesc()
            ->take(10)
            ->map(function ($r) {
                $label = $this->label($r);

                return [
                    'tanggal'   => $r['date']->translatedFormat('l, d M Y'),
                    'masuk'     => $r['masuk']?->format('H:i'),
                    'pulang'    => $r['pulang']?->format('H:i'),
                    'jam_kerja' => $r['menit'] !== null ? $this->formatDurasi($r['menit']) : null,
                    'status'    => $label,
                    'badge'     => $this->badge($label),
                ];
            })
            ->values()
            ->all();

        // ---- Data admin ----
        $stats = [];
        $todayAttendance = [];
        if ($isAdmin) {
            [$stats, $todayAttendance] = $this->adminToday($today);
        }

        return view('dashboard', compact(
            'isAdmin', 'stats', 'myStats', 'attendanceToday',
            'attendanceChart', 'statusChart', 'todayAttendance', 'myRecent'
        ));
    }

    /* ------------------------------------------------------------------
     | Data per hari untuk satu user: [Y-m-d => hasil resolveDay()]
     | Mencakup awal bulan s/d hari ini (minimal 10 hari ke belakang).
     | ------------------------------------------------------------------ */
    private function personalDays(User $user, Carbon $today): Collection
    {
        $from = $today->copy()->startOfMonth()->min($today->copy()->subDays(9));

        $absensiQuery = Absensi::where('user_id', $user->id)
            ->whereBetween('recorded_at', [$from->copy()->startOfDay(), $today->copy()->endOfDay()])
            ->orderBy('recorded_at');
        if (self::HANYA_ABSENSI_VALID) {
            $absensiQuery->where('is_valid', true);
        }
        $absensi = $absensiQuery->get()->groupBy(fn ($a) => $a->recorded_at->toDateString());

        $pengajuan = Pengajuan::where('user_id', $user->id)
            ->whereIn('status', ['disetujui', 'menunggu'])
            ->whereDate('tanggal_mulai', '<=', $today)
            ->whereDate('tanggal_selesai', '>=', $from)
            ->get();

        $days = collect();
        for ($d = $from->copy(); $d->lte($today); $d->addDay()) {
            $key = $d->toDateString();
            $days[$key] = $this->resolveDay($user, $d->copy(), $today, $absensi->get($key, collect()), $pengajuan);
        }

        return $days;
    }

    /**
     * Tentukan status satu hari. Urutan prioritas:
     * absen > belum aktif > libur (Sabtu/Minggu) > pengajuan disetujui > menunggu > tanpa keterangan.
     */
    private function resolveDay(User $user, Carbon $date, Carbon $today, Collection $absensi, Collection $pengajuan): array
    {
        $masuk   = $absensi->firstWhere('type', self::TYPE_MASUK)?->recorded_at;
        $pulang  = $absensi->where('type', self::TYPE_PULANG)->last()?->recorded_at;
        $isToday = $date->isSameDay($today);
        $kind    = null;
        $catatan = null;

        $p = $pengajuan
            ->filter(fn ($x) => $date->between($x->tanggal_mulai, $x->tanggal_selesai))
            ->sortBy(fn ($x) => $x->status === 'disetujui' ? 0 : 1)
            ->first();

        $aktifSejak = ($user->created_at ?? $date)->copy()->startOfDay();

        if ($masuk) {
            $status = ($pulang || $isToday) ? 'Hadir' : 'Lupa absen pulang';
        } elseif ($date->lt($aktifSejak)) {
            $status = 'Belum aktif';
        } elseif ($date->isWeekend()) {
            $status = 'Libur';
        } elseif ($p && $p->status === 'disetujui') {
            $status  = 'Sakit / Izin';
            $kind    = $p->type === 'sakit' ? 'sakit' : 'izin'; // cuti dihitung izin
            $catatan = $p->type_label . ($p->alasan ? ': ' . $p->alasan : '');
        } elseif ($p || $isToday) {
            $status  = 'Menunggu';
            $catatan = $p ? 'Pengajuan ' . $p->type_label . ' menunggu persetujuan' : null;
        } else {
            $status = 'Tanpa keterangan';
        }

        return [
            'date'     => $date,
            'status'   => $status,
            'kind'     => $kind,
            'masuk'    => $masuk,
            'pulang'   => $pulang,
            'menit'    => ($masuk && $pulang) ? (int) $masuk->diffInMinutes($pulang, true) : null,
            'catatan'  => $catatan,
            'is_today' => $isToday,
        ];
    }

    /* ------------------------------------------------------------------
     | Grafik 7 hari terakhir: tiap hari = 1 blok berwarna
     | ------------------------------------------------------------------ */
    private function buildWeekChart(Collection $days, Carbon $today): array
    {
        $series  = array_fill_keys(self::STATUS_ORDER, []);
        $labels  = [];
        $details = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = $today->copy()->subDays($i);
            $row  = $days[$date->toDateString()];

            $labels[] = $date->translatedFormat('D d');
            foreach (self::STATUS_ORDER as $s) {
                $series[$s][] = $row['status'] === $s ? 1 : 0;
            }

            $details[] = [
                'tanggal' => $date->translatedFormat('l, d F Y'),
                'status'  => $this->label($row),
                'masuk'   => $row['masuk']?->format('H:i'),
                'pulang'  => $row['pulang']?->format('H:i'),
                'durasi'  => $row['menit'] !== null ? $this->formatDurasi($row['menit']) : null,
                'catatan' => $row['catatan'],
            ];
        }

        return [
            'labels'  => $labels,
            'series'  => collect($series)->map(fn ($data, $name) => ['name' => $name, 'data' => $data])->values()->all(),
            'details' => $details,
        ];
    }

    /* ------------------------------------------------------------------
     | Ringkasan seluruh karyawan hari ini (khusus admin)
     | Catatan: semua user dianggap karyawan. Kalau perlu dibatasi
     | (mis. tanpa role admin), ubah query User di bawah.
     | ------------------------------------------------------------------ */
    private function adminToday(Carbon $today): array
    {
        $users = User::select('id', 'name', 'created_at')->orderBy('name')->get();

        $absensiQuery = Absensi::whereBetween('recorded_at', [$today->copy()->startOfDay(), $today->copy()->endOfDay()])
            ->orderBy('recorded_at');
        if (self::HANYA_ABSENSI_VALID) {
            $absensiQuery->where('is_valid', true);
        }
        $absensi = $absensiQuery->get()->groupBy('user_id');

        $pengajuan = Pengajuan::whereIn('status', ['disetujui', 'menunggu'])
            ->whereDate('tanggal_mulai', '<=', $today)
            ->whereDate('tanggal_selesai', '>=', $today)
            ->get()
            ->groupBy('user_id');

        $rows = $users->map(function (User $u) use ($today, $absensi, $pengajuan) {
            $r = $this->resolveDay($u, $today->copy(), $today, $absensi->get($u->id, collect()), $pengajuan->get($u->id, collect()));
            $label = $this->label($r);

            return [
                'name'      => $u->name,
                'status'    => $label,
                'badge'     => $this->badge($label),
                'masuk'     => $r['masuk']?->format('H:i'),
                'pulang'    => $r['pulang']?->format('H:i'),
                'jam_kerja' => $r['menit'] !== null ? $this->formatDurasi($r['menit']) : null,
            ];
        });

        $stats = [
            'karyawan'    => $rows->count(),
            'hadir'       => $rows->where('status', 'Hadir')->count(),
            'izin'        => $rows->where('status', 'Sakit / Izin')->count(),
            'belum_absen' => $rows->where('status', 'Belum Absen')->count(),
        ];

        return [$stats, $rows->all()];
    }

    /* ---------------------------- Helper ---------------------------- */

    /** "Menunggu" pada hari ini ditampilkan sebagai "Belum Absen". */
    private function label(array $row): string
    {
        return ($row['status'] === 'Menunggu' && $row['is_today']) ? 'Belum Absen' : $row['status'];
    }

    private function badge(string $label): string
    {
        return self::BADGE[$label] ?? 'badge-light';
    }

    private function formatDurasi(int $menit): string
    {
        return intdiv($menit, 60) . 'j ' . ($menit % 60) . 'm';
    }
}