<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\Pengajuan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PengajuanController extends Controller
{
    /** Permission modul "absensi-pengajuan" (dibuat ModuleSeeder). */
    private const PERMISSION_APPROVE = 'absensi-pengajuan.approve'; // terima / tolak pengajuan yang masih menunggu
    private const PERMISSION_EDIT = 'absensi-pengajuan.edit';       // ubah keputusan yang sudah diambil
    private const PERMISSION_DELETE = 'absensi-pengajuan.delete';   // hapus pengajuan yang sudah diputuskan

    private const STATUSES = ['menunggu', 'disetujui', 'ditolak'];

    /**
     * Daftar pengajuan.
     *  - Karyawan: hanya pengajuan miliknya sendiri (untuk memantau status).
     *  - Yang punya salah satu permission approve/edit/delete: semua pengajuan, bisa cari nama.
     */
    public function index(Request $request): View
    {
        $user = auth()->user();
        $canApprove = $user->can(self::PERMISSION_APPROVE);
        $canEdit = $user->can(self::PERMISSION_EDIT);
        $canDelete = $user->can(self::PERMISSION_DELETE);
        $canSeeAll = $canApprove || $canEdit || $canDelete;

        $status = $request->query('status');
        $status = in_array($status, self::STATUSES, true) ? $status : null; // null = semua status
        $search = $canSeeAll ? trim((string) $request->query('q', '')) : '';

        // Pembatasan akses ada di sini (server), bukan cuma disembunyikan di tampilan
        $base = Pengajuan::query()
            ->when(! $canSeeAll, fn ($query) => $query->where('user_id', $user->id));

        $counts = (clone $base)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $pengajuans = (clone $base)
            ->with('user', 'reviewer')
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($search !== '', fn ($query) => $query->whereHas(
                'user',
                fn ($u) => $u->where('name', 'like', '%' . $search . '%')
            ))
            ->orderByRaw("CASE status WHEN 'menunggu' THEN 0 ELSE 1 END") // yang menunggu selalu di atas
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('pengajuan.index', compact(
            'pengajuans', 'counts', 'status', 'search',
            'canSeeAll', 'canApprove', 'canEdit', 'canDelete'
        ));
    }

    /**
     * Ajukan sakit / izin / cuti. Bisa dari mana saja — sengaja TIDAK ada cek lokasi.
     * Semua jenis (termasuk sakit) berstatus "menunggu" sampai admin menerima atau menolak.
     *  - Sakit: wajib alasan + foto bukti.
     *  - Izin/Cuti: wajib alasan, tanggal mulai tidak boleh sebelum hari ini.
     */
    public function store(Request $request): RedirectResponse
    {
        $isSakit = $request->input('type') === 'sakit';

        $rules = [
            'type' => ['required', Rule::in(['sakit', 'izin', 'cuti'])],
            'alasan' => ['required', 'string', 'min:5', 'max:1000'],
        ];

        if ($isSakit) {
            $rules['foto'] = ['required', 'image', 'max:5120']; // maks 5MB
        } else {
            // izin/cuti: karyawan memilih rentang tanggal sendiri, mulai dari hari ini dan ke depan.
            // Sakit TIDAK punya aturan tanggal: tanggalnya diisi server (hari ini), input tanggal diabaikan.
            $rules['tanggal_mulai'] = ['required', 'date', 'after_or_equal:today'];
            $rules['tanggal_selesai'] = ['required', 'date', 'after_or_equal:tanggal_mulai'];
        }

        $validated = $request->validate($rules, [
            'required' => ':attribute wajib diisi.',
            'alasan.min' => 'Alasan minimal 5 karakter.',
            'alasan.max' => 'Alasan maksimal 1000 karakter.',
            'tanggal_mulai.after_or_equal' => 'Tanggal mulai izin/cuti tidak boleh sebelum hari ini.',
            'tanggal_selesai.after_or_equal' => 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.',
            'foto.image' => 'Foto bukti harus berupa gambar (jpg/png).',
            'foto.max' => 'Ukuran foto bukti terlalu besar. Maksimal 5 MB.',
            // muncul kalau melebihi upload_max_filesize di php.ini
            'foto.uploaded' => 'Foto bukti gagal diunggah karena ukurannya terlalu besar. Maksimal 5 MB.',
        ], [
            'type' => 'Jenis pengajuan',
            'tanggal_mulai' => 'Tanggal mulai',
            'tanggal_selesai' => 'Tanggal selesai',
            'alasan' => 'Alasan',
            'foto' => 'Foto bukti',
        ]);

        // Sakit selalu dicatat untuk HARI INI (tanggal server), apa pun yang dikirim dari form.
        // Dengan begitu sakit langsung berlaku hari ini dan tombol Hadir ikut mati.
        if ($isSakit) {
            $validated['tanggal_mulai'] = $validated['tanggal_selesai'] = now()->toDateString();
        }

        // satu hari tidak boleh punya dua status: kalau sudah absen di salah satu tanggal itu, tolak
        $sudahAbsen = Absensi::where('user_id', auth()->id())
            ->whereDate('recorded_at', '>=', $validated['tanggal_mulai'])
            ->whereDate('recorded_at', '<=', $validated['tanggal_selesai'])
            ->orderBy('recorded_at')
            ->first();

        if ($sudahAbsen) {
            return back()->withInput()->withErrors([
                'tanggal_mulai' => 'Anda sudah absen pada ' . $sudahAbsen->recorded_at->translatedFormat('d M Y')
                    . ', jadi tanggal itu tidak bisa diajukan sakit/izin/cuti.',
            ]);
        }

        // cegah pengajuan ganda: rentang tanggal yang sama sudah punya pengajuan aktif
        $bentrok = Pengajuan::where('user_id', auth()->id())
            ->whereIn('status', ['menunggu', 'disetujui'])
            ->whereDate('tanggal_mulai', '<=', $validated['tanggal_selesai'])
            ->whereDate('tanggal_selesai', '>=', $validated['tanggal_mulai'])
            ->exists();

        if ($bentrok) {
            return back()->withInput()->withErrors([
                'tanggal_mulai' => 'Pada rentang tanggal itu Anda sudah punya pengajuan yang masih menunggu atau sudah diterima.',
            ]);
        }

        // foto bukti ke MinIO (bucket privat), dikelompokkan per user
        $fotoPath = $isSakit
            ? $request->file('foto')->store('pengajuan/' . auth()->id(), 'supabase_pengajuan')
            : null;

        Pengajuan::create([
            'user_id' => auth()->id(),
            'type' => $validated['type'],
            'tanggal_mulai' => $validated['tanggal_mulai'],
            'tanggal_selesai' => $validated['tanggal_selesai'],
            'alasan' => $validated['alasan'],
            'foto_path' => $fotoPath,
            'status' => 'menunggu',
        ]);

        return redirect()->route('absensi.index')->with(
            'success',
            'Pengajuan ' . $validated['type'] . ' terkirim, menunggu pemeriksaan admin.'
        );
    }

    /**
     * Terima / tolak pengajuan yang masih "menunggu".
     * Route-nya dijaga middleware permission:absensi-pengajuan.approve.
     */
    public function review(Request $request, Pengajuan $pengajuan): RedirectResponse
    {
        $validated = $request->validate([
            'aksi' => ['required', Rule::in(['terima', 'tolak'])],
            'catatan_admin' => ['nullable', 'string', 'max:500', 'required_if:aksi,tolak'],
        ], [
            'catatan_admin.required_if' => 'Alasan penolakan wajib diisi.',
            'catatan_admin.max' => 'Catatan maksimal 500 karakter.',
        ]);

        if ($pengajuan->status !== 'menunggu') {
            return back()->withErrors(['aksi' => 'Pengajuan ini sudah diputuskan. Gunakan tombol Edit untuk mengubahnya.']);
        }

        $diterima = $validated['aksi'] === 'terima';

        if ($diterima && ($bentrok = $this->bentrokSaatDiterima($pengajuan))) {
            return back()->withErrors(['aksi' => $bentrok]);
        }

        $pengajuan->update([
            'status' => $diterima ? 'disetujui' : 'ditolak',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'catatan_admin' => $validated['catatan_admin'] ?? null,
        ]);

        return back()->with(
            'success',
            'Pengajuan ' . $pengajuan->user->name . ' berhasil ' . ($diterima ? 'diterima.' : 'ditolak.')
        );
    }

    /**
     * Edit keputusan yang sudah diambil (terima <-> tolak) beserta alasan editnya.
     * Route-nya dijaga middleware permission:absensi-pengajuan.edit.
     */
    public function update(Request $request, Pengajuan $pengajuan): RedirectResponse
    {
        $validated = $request->validate([
            'aksi' => ['required', Rule::in(['terima', 'tolak'])],
            'alasan_edit' => ['required', 'string', 'min:3', 'max:500'],
        ], [
            'alasan_edit.required' => 'Alasan edit wajib diisi.',
            'alasan_edit.min' => 'Alasan edit minimal 3 karakter.',
            'alasan_edit.max' => 'Alasan edit maksimal 500 karakter.',
        ]);

        if ($pengajuan->status === 'menunggu') {
            return back()->withErrors(['aksi' => 'Pengajuan ini belum diputuskan. Gunakan tombol Terima atau Tolak.']);
        }

        $diterima = $validated['aksi'] === 'terima';

        if ($diterima && ($bentrok = $this->bentrokSaatDiterima($pengajuan))) {
            return back()->withErrors(['aksi' => $bentrok]);
        }

        $pengajuan->update([
            'status' => $diterima ? 'disetujui' : 'ditolak',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            // alasan penolakan lama tidak relevan lagi kalau sekarang diterima
            'catatan_admin' => $diterima ? null : $pengajuan->catatan_admin,
            'alasan_edit' => $validated['alasan_edit'],
            'edited_at' => now(),
        ]);

        return back()->with(
            'success',
            'Keputusan untuk ' . $pengajuan->user->name . ' diubah menjadi ' . ($diterima ? 'diterima.' : 'ditolak.')
        );
    }

    /**
     * Hapus pengajuan yang sudah diputuskan, sekalian foto buktinya di MinIO.
     * Hari itu otomatis dihitung ulang di laporan. Route dijaga permission:absensi-pengajuan.delete.
     */
    public function destroy(Pengajuan $pengajuan): RedirectResponse
    {
        if ($pengajuan->status === 'menunggu') {
            return back()->withErrors(['aksi' => 'Pengajuan yang masih menunggu tidak bisa dihapus. Terima atau tolak dulu.']);
        }

        if ($pengajuan->foto_path) {
            Storage::disk('supabase_pengajuan')->delete($pengajuan->foto_path);
        }

        $nama = $pengajuan->user->name;
        $pengajuan->delete();

        return back()->with('success', 'Pengajuan ' . $nama . ' berhasil dihapus.');
    }

    /**
     * Tampilkan foto bukti. Bucket absensi itu privat, jadi filenya dialirkan lewat Laravel
     * (bukan link langsung ke MinIO) dan hanya boleh dilihat pemiliknya atau pengelola pengajuan.
     */
    public function foto(Pengajuan $pengajuan): StreamedResponse
    {
        $user = auth()->user();
        $pengelola = $user->can(self::PERMISSION_APPROVE)
            || $user->can(self::PERMISSION_EDIT)
            || $user->can(self::PERMISSION_DELETE);

        abort_unless($pengajuan->user_id === $user->id || $pengelola, 403);
        abort_if(! $pengajuan->foto_path, 404);

        return Storage::disk('supabase_pengajuan')->response($pengajuan->foto_path);
    }

    /**
     * Alasan pengajuan TIDAK boleh diterima (null = aman): karyawan sudah absen di salah satu
     * tanggalnya, atau bertumpuk dengan pengajuan aktif lain miliknya. Penting saat keputusan
     * "tolak" diubah jadi "terima", karena selama ditolak karyawan bebas absen di tanggal itu.
     */
    private function bentrokSaatDiterima(Pengajuan $pengajuan): ?string
    {
        $absen = Absensi::where('user_id', $pengajuan->user_id)
            ->whereDate('recorded_at', '>=', $pengajuan->tanggal_mulai)
            ->whereDate('recorded_at', '<=', $pengajuan->tanggal_selesai)
            ->orderBy('recorded_at')
            ->first();

        if ($absen) {
            return $pengajuan->user->name . ' sudah absen pada ' . $absen->recorded_at->translatedFormat('d M Y')
                . ', jadi pengajuan ini tidak bisa diterima.';
        }

        $bertumpuk = Pengajuan::where('user_id', $pengajuan->user_id)
            ->where('id', '!=', $pengajuan->id)
            ->whereIn('status', ['menunggu', 'disetujui'])
            ->whereDate('tanggal_mulai', '<=', $pengajuan->tanggal_selesai)
            ->whereDate('tanggal_selesai', '>=', $pengajuan->tanggal_mulai)
            ->exists();

        if ($bertumpuk) {
            return 'Tanggal pengajuan ini bertumpuk dengan pengajuan lain milik ' . $pengajuan->user->name . '.';
        }

        return null;
    }
}