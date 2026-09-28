<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\User;
use App\Services\GeolocationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AbsensiController extends Controller
{
    /** Permission Spatie: boleh melihat absensi SEMUA karyawan (diberikan ke admin). */
    private const PERMISSION_READ_ALL = 'absensi.read-all';

    /** Asumsi jam kerja per hari kerja (Senin-Jumat), dipakai untuk target jam kerja. */
    private const JAM_KERJA_PER_HARI = 8;

    public function __construct(private readonly GeolocationService $geolocationService)
    {
    }

    /**
     * Halaman utama absensi: status hari ini + form absen + log kehadiran hari ini.
     * Admin melihat log semua karyawan, karyawan hanya melihat log miliknya sendiri.
     */
    public function index(): View
    {
        $userId = auth()->id();
        $today = now()->toDateString();
        $canViewAll = auth()->user()->can(self::PERMISSION_READ_ALL);

        $absensiMasuk = Absensi::where('user_id', $userId)
            ->where('type', 'masuk')
            ->whereDate('recorded_at', $today)
            ->latest('recorded_at')
            ->first();

        $absensiPulang = Absensi::where('user_id', $userId)
            ->where('type', 'pulang')
            ->whereDate('recorded_at', $today)
            ->latest('recorded_at')
            ->first();

        $logHariIni = fn (string $type) => Absensi::with('user', 'workLocation')
            ->where('type', $type)
            ->whereDate('recorded_at', $today)
            ->when(! $canViewAll, fn ($query) => $query->where('user_id', $userId))
            ->latest('recorded_at')
            ->get();

        return view('absensi.index', [
            'absensiMasuk' => $absensiMasuk,
            'absensiPulang' => $absensiPulang,
            'logAbsensiMasuk' => $logHariIni('masuk'),
            'logAbsensiPulang' => $logHariIni('pulang'),
        ]);
    }

    /**
     * Riwayat absensi milik user yang login.
     */
    public function riwayat(): View
    {
        $absensis = Absensi::with('workLocation')
            ->where('user_id', auth()->id())
            ->latest('recorded_at')
            ->paginate(15);

        return view('absensi.riwayat', compact('absensis'));
    }

    /**
     * "Total Absensi".
     *  - Karyawan: selalu laporan miliknya sendiri.
     *  - Admin (punya absensi.read-all) tanpa ?user_id : rekap semua karyawan.
     *  - Admin dengan ?user_id : laporan detail karyawan tersebut.
     *
     * Pengecekan izin dilakukan di server, jadi karyawan tidak bisa mengintip orang
     * lain dengan mengubah ?user_id di URL — parameter itu diabaikan untuk mereka.
     */
    public function laporan(Request $request): View
    {
        $viewer = auth()->user();
        $canViewAll = $viewer->can(self::PERMISSION_READ_ALL);

        if ($canViewAll && ! $request->filled('user_id')) {
            return $this->rekap($request);
        }

        $user = $canViewAll ? User::findOrFail($request->query('user_id')) : $viewer;

        [
            'from' => $from,
            'to' => $to,
            'effectiveTo' => $effectiveTo,
            'quickFilters' => $quickFilters,
            'activeQuick' => $activeQuick,
        ] = $this->resolvePeriod($request);

        $firstAbsenDate = $this->firstAbsenDate($user->id);

        $hasil = $this->hitungPeriode(
            $this->absensiByDate($user->id, $from, $effectiveTo),
            $firstAbsenDate,
            $from,
            $effectiveTo
        );
        $stat = $hasil['stat'];

        // perbandingan kehadiran dengan periode sebelumnya yang panjangnya sama
        $panjangPeriodeHari = max(1, (int) $from->copy()->startOfDay()->diffInDays($effectiveTo->copy()->startOfDay()) + 1);
        $prevFrom = $from->copy()->subDays($panjangPeriodeHari)->startOfDay();
        $prevTo = $from->copy()->subDay()->endOfDay();

        $prevStat = $this->hitungPeriode(
            $this->absensiByDate($user->id, $prevFrom, $prevTo),
            $firstAbsenDate,
            $prevFrom,
            $prevTo
        )['stat'];

        $kehadiranPersen = $this->persen($stat['hadir'], $stat['hari_kerja']);
        $prevKehadiranPersen = $this->persen($prevStat['hadir'], $prevStat['hari_kerja']);

        $targetJamKerja = $stat['hari_kerja'] * self::JAM_KERJA_PER_HARI;
        $targetMenitKerja = $targetJamKerja * 60;

        return view('absensi.laporan', [
            'reportUser' => $user,
            'isOwnReport' => $user->id === $viewer->id,
            'canViewAll' => $canViewAll,
            // dibawa ke form & tombol cepat supaya admin tidak "terlempar" ke rekap
            'userParam' => $canViewAll ? ['user_id' => $user->id] : [],
            'from' => $from,
            'to' => $to,
            'quickFilters' => $quickFilters,
            'activeQuick' => $activeQuick,
            'hadirCount' => $stat['hadir'],
            'workDaysCount' => $stat['hari_kerja'],
            'kehadiranPersen' => $kehadiranPersen,
            'jamKerjaLabel' => $this->formatJamMenit($stat['menit_kerja']),
            'targetJamKerja' => $targetJamKerja,
            'jamKerjaPersen' => $targetMenitKerja > 0 ? round($stat['menit_kerja'] / $targetMenitKerja * 100, 1) : 0.0,
            'lupaPulangCount' => $stat['lupa_pulang'],
            'tanpaKeteranganCount' => $stat['tanpa_keterangan'],
            'prevFrom' => $prevFrom,
            'prevTo' => $prevTo,
            'prevKehadiranPersen' => $prevKehadiranPersen,
            'kehadiranSelisih' => round($kehadiranPersen - $prevKehadiranPersen, 1),
            'rincianHarian' => $hasil['rincian']->reverse()->values(), // terbaru di atas
        ]);
    }

    /**
     * Rekap semua karyawan (khusus admin) — dipanggil dari laporan().
     * Ada pencarian nama (?q=) dan paginasi 15 karyawan per halaman.
     */
    private function rekap(Request $request): View
    {
        [
            'from' => $from,
            'to' => $to,
            'effectiveTo' => $effectiveTo,
            'quickFilters' => $quickFilters,
            'activeQuick' => $activeQuick,
        ] = $this->resolvePeriod($request);

        $search = trim((string) $request->query('q', ''));

        $users = User::query()
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%' . $search . '%'))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $userIds = $users->pluck('id');

        // 2 query untuk seluruh halaman ini (bukan per karyawan) supaya tetap ringan
        $absensiPerUser = Absensi::whereIn('user_id', $userIds)
            ->whereBetween('recorded_at', [$from, $effectiveTo])
            ->get()
            ->groupBy('user_id');

        $firstAbsenPerUser = Absensi::whereIn('user_id', $userIds)
            ->where('type', 'masuk')
            ->selectRaw('user_id, MIN(recorded_at) as first_at')
            ->groupBy('user_id')
            ->pluck('first_at', 'user_id');

        $rows = $users->getCollection()->map(function (User $user) use ($absensiPerUser, $firstAbsenPerUser, $from, $effectiveTo) {
            $byDate = ($absensiPerUser->get($user->id) ?? collect())
                ->groupBy(fn ($item) => $item->recorded_at->toDateString());

            $first = $firstAbsenPerUser->get($user->id);
            $first = $first ? Carbon::parse($first)->startOfDay() : null;

            $stat = $this->hitungPeriode($byDate, $first, $from, $effectiveTo)['stat'];

            return [
                'user' => $user,
                'stat' => $stat,
                'jam_kerja' => $this->formatJamMenit($stat['menit_kerja']),
                'persen' => $this->persen($stat['hadir'], $stat['hari_kerja']),
            ];
        });

        return view('absensi.rekap', [
            'rows' => $rows,
            'users' => $users,
            'search' => $search,
            'from' => $from,
            'to' => $to,
            'quickFilters' => $quickFilters,
            'activeQuick' => $activeQuick,
            // parameter periode yang diteruskan ke tombol "Detail" tiap karyawan
            'periodParams' => $activeQuick
                ? ['quick' => $activeQuick]
                : ['from' => $from->toDateString(), 'to' => $to->toDateString()],
        ]);
    }

    /**
     * Tentukan rentang tanggal dari tombol cepat / input manual.
     * "effectiveTo" di-cap ke hari ini supaya hari yang belum terjadi tidak dihitung.
     */
    private function resolvePeriod(Request $request): array
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $quickFilters = [
            'bulan_ini' => ['label' => 'Bulan ini', 'from' => now()->startOfMonth(), 'to' => now()],
            'bulan_lalu' => ['label' => 'Bulan lalu', 'from' => now()->subMonthNoOverflow()->startOfMonth(), 'to' => now()->subMonthNoOverflow()->endOfMonth()],
            '30_hari' => ['label' => '30 hari terakhir', 'from' => now()->subDays(29), 'to' => now()],
            '90_hari' => ['label' => '90 hari terakhir', 'from' => now()->subDays(89), 'to' => now()],
            'tahun_ini' => ['label' => 'Tahun ini', 'from' => now()->startOfYear(), 'to' => now()],
        ];

        $activeQuick = $request->query('quick', 'bulan_ini');

        if ($request->filled('from') && $request->filled('to')) {
            $from = Carbon::parse($request->query('from'))->startOfDay();
            $to = Carbon::parse($request->query('to'))->endOfDay();
            $activeQuick = null; // rentang manual, bukan salah satu tombol cepat
        } elseif (isset($quickFilters[$activeQuick])) {
            $from = $quickFilters[$activeQuick]['from']->copy()->startOfDay();
            $to = $quickFilters[$activeQuick]['to']->copy()->endOfDay();
        } else {
            $from = now()->startOfMonth();
            $to = now()->endOfDay();
            $activeQuick = 'bulan_ini';
        }

        return [
            'from' => $from,
            'to' => $to,
            'effectiveTo' => $to->greaterThan(now()) ? now()->endOfDay() : $to,
            'quickFilters' => $quickFilters,
            'activeQuick' => $activeQuick,
        ];
    }

    /**
     * Hari pertama user pernah absen masuk (sepanjang sejarah). Hari-hari sebelum ini
     * tidak dihitung "Tanpa Keterangan" karena dia belum mulai memakai sistem absensi.
     */
    private function firstAbsenDate(int $userId): ?Carbon
    {
        $first = Absensi::where('user_id', $userId)
            ->where('type', 'masuk')
            ->orderBy('recorded_at')
            ->value('recorded_at');

        return $first ? Carbon::parse($first)->startOfDay() : null;
    }

    /**
     * Absensi user dalam rentang tanggal, dikelompokkan per tanggal (Y-m-d).
     */
    private function absensiByDate(int $userId, Carbon $from, Carbon $to): Collection
    {
        return Absensi::where('user_id', $userId)
            ->whereBetween('recorded_at', [$from, $to])
            ->with('workLocation')
            ->orderBy('recorded_at')
            ->get()
            ->groupBy(fn ($item) => $item->recorded_at->toDateString());
    }

    /**
     * Inti perhitungan: dipakai laporan satu orang, periode pembanding, dan rekap admin,
     * supaya aturannya selalu sama.
     *  - Hari kerja = Senin-Jumat, mulai dari hari pertama user absen
     *  - Hadir = ada absen masuk; Lupa Absen Pulang = masuk tanpa pulang;
     *    Tanpa Keterangan = hari kerja tanpa absen masuk
     *
     * @param  Collection  $absensiByDate  absensi user, dikelompokkan per tanggal
     * @return array{stat: array<string,int>, rincian: Collection}
     */
    private function hitungPeriode(Collection $absensiByDate, ?Carbon $firstAbsenDate, Carbon $from, Carbon $effectiveTo): array
    {
        $stat = [
            'hadir' => 0,
            'lupa_pulang' => 0,
            'tanpa_keterangan' => 0,
            'hari_kerja' => 0,
            'menit_kerja' => 0,
        ];
        $rincian = collect();

        $cursor = $from->copy()->startOfDay();
        while ($cursor->lte($effectiveTo)) {
            $isWorkday = $cursor->isWeekday(); // asumsi hari kerja Senin-Jumat

            $group = $absensiByDate->get($cursor->toDateString());
            $masuk = $group?->firstWhere('type', 'masuk');
            $pulang = $group?->firstWhere('type', 'pulang');

            // sebelum absen pertama (atau belum pernah absen) = belum mulai, tidak dihitung
            $belumMulai = $firstAbsenDate === null || $cursor->lt($firstAbsenDate);

            $menitHariIni = 0;
            if ($masuk && $pulang) {
                $menitHariIni = max(0, (int) $masuk->recorded_at->diffInMinutes($pulang->recorded_at, false));
            }

            if ($belumMulai) {
                $status = $isWorkday ? 'Belum Mulai' : 'Libur';
            } elseif ($masuk && $pulang) {
                $status = 'Hadir';
                $stat['menit_kerja'] += $menitHariIni;
            } elseif ($masuk) {
                $status = 'Lupa Absen Pulang';
            } elseif ($isWorkday) {
                $status = 'Tanpa Keterangan';
            } else {
                $status = 'Libur';
            }

            if ($isWorkday && ! $belumMulai) {
                $stat['hari_kerja']++;
                if ($masuk) {
                    $stat['hadir']++;
                }
                if ($masuk && ! $pulang) {
                    $stat['lupa_pulang']++;
                }
                if (! $masuk) {
                    $stat['tanpa_keterangan']++;
                }
            }

            $rincian->push([
                'date' => $cursor->copy(),
                'is_workday' => $isWorkday,
                'status' => $status,
                'masuk' => $masuk,
                'pulang' => $pulang,
                'durasi' => ($masuk && $pulang) ? $this->formatJamMenit($menitHariIni) : '-',
                'lokasi' => $masuk?->workLocation?->name ?? $pulang?->workLocation?->name ?? '-',
            ]);

            $cursor->addDay();
        }

        return ['stat' => $stat, 'rincian' => $rincian];
    }

    private function persen(int $bagian, int $total): float
    {
        return $total > 0 ? round($bagian / $total * 100, 1) : 0.0;
    }

    private function formatJamMenit(int $totalMinutes): string
    {
        $jam = intdiv($totalMinutes, 60);
        $menit = $totalMinutes % 60;

        return "{$jam}j {$menit}m";
    }

    /**
     * Simpan absensi masuk / pulang: validasi foto + koordinat,
     * cek radius terhadap work_location terdekat, baru simpan.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['masuk', 'pulang'])],
            'photo' => ['required', 'image', 'max:5120'], // maks 5MB
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        // batas 1x absen per jenis per hari — dicek di server, bukan cuma di UI,
        // supaya tidak bisa dilewati dengan mengubah <select> lewat devtools
        $sudahAbsenHariIni = Absensi::where('user_id', auth()->id())
            ->where('type', $validated['type'])
            ->whereDate('recorded_at', now()->toDateString())
            ->exists();

        if ($sudahAbsenHariIni) {
            return back()->withErrors([
                'type' => 'Anda sudah melakukan absen ' . $validated['type'] . ' hari ini.',
            ]);
        }

        $result = $this->geolocationService->findNearestActiveLocation(
            (float) $validated['latitude'],
            (float) $validated['longitude']
        );

        if ($result === null) {
            return back()->withErrors([
                'location' => 'Belum ada lokasi kerja terdaftar. Hubungi admin sebelum melakukan absensi.',
            ]);
        }

        if (! $result['is_valid']) {
            return back()->withErrors([
                'location' => sprintf(
                    'Anda berada sekitar %d meter dari %s (radius yang diizinkan %d meter). Absensi ditolak, silakan mendekat ke lokasi kerja.',
                    round($result['distance']),
                    $result['location']->name,
                    $result['location']->radius_meters
                ),
            ]);
        }

        // simpan foto ke MinIO lewat disk 's3', dikelompokkan per user
        $path = $request->file('photo')->store('absensi/' . auth()->id(), 'supabase_absensi');

        Absensi::create([
            'user_id' => auth()->id(),
            'work_location_id' => $result['location']->id,
            'type' => $validated['type'],
            'photo_path' => $path,
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'distance_meters' => $result['distance'],
            'is_valid' => true,
            'recorded_at' => now(),
        ]);

        $label = $validated['type'] === 'masuk' ? 'Absen masuk' : 'Absen pulang';

        return back()->with('success', "{$label} berhasil dicatat pada " . now()->format('H:i:s'));
    }
}