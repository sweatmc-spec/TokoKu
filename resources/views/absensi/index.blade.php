@extends('layouts.app') {{-- sesuaikan dengan layout Metronic yang sudah kamu pakai --}}

@section('title', 'Absensi')

@section('content')
<div class="row g-5">

    {{-- Notifikasi sukses / error --}}
    @if (session('success'))
        <div class="col-12">
            <div class="alert alert-success d-flex align-items-center">
                <i class="ki-duotone ki-check-circle fs-2x me-3"><span class="path1"></span><span class="path2"></span></i>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if ($errors->any())
        <div class="col-12">
            <div class="alert alert-danger d-flex align-items-center">
                <i class="ki-duotone ki-shield-cross fs-2x me-3"><span class="path1"></span><span class="path2"></span></i>
                <span>{{ $errors->first() }}</span>
            </div>
        </div>
    @endif

    {{-- Hari ini tercatat sakit / izin / cuti: absen masuk & pulang dinonaktifkan --}}
    @if ($pengajuanHariIni)
        <div class="col-12">
            <div class="alert {{ $pengajuanHariIni->status === 'menunggu' ? 'alert-warning' : 'alert-info' }} d-flex align-items-center">
                <span>
                    Hari ini kamu tercatat <strong>{{ strtolower($pengajuanHariIni->type_label) }}</strong>
                    ({{ $pengajuanHariIni->status === 'menunggu' ? 'menunggu pemeriksaan admin' : 'sudah diterima' }}),
                    jadi absen masuk dan pulang dinonaktifkan.
                </span>
            </div>
        </div>
    @endif

    {{-- HERO BANNER --}}
    <div class="col-12">
        <div class="card bg-primary">
            <div class="card-body d-flex flex-wrap align-items-center justify-content-between py-6">
                <div>
                    <h2 class="text-white mb-1">Halo, {{ auth()->user()->name }} 👋</h2>
                    <div class="text-white opacity-75 fs-6">
                        {{ now()->translatedFormat('l, d F Y') }} &middot; Pastikan lokasi & kamera aktif saat absen.
                    </div>
                </div>
                <div class="bg-white bg-opacity-10 rounded-3 px-5 py-3 text-white text-center">
                    <div class="fs-7 opacity-75">Waktu Sekarang</div>
                    <div class="fs-2 fw-bold" id="live-clock">{{ now()->format('H:i:s') }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- KARTU STATUS CEPAT --}}
    <div class="col-6 col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="fs-8 text-muted text-uppercase fw-bold mb-2">Status Masuk</div>
                @if ($absensiMasuk)
                    <span class="badge badge-light-success">Sudah Absen</span>
                @elseif ($pengajuanHariIni)
                    <span class="badge badge-light-info">{{ $pengajuanHariIni->type_label }}</span>
                @else
                    <span class="badge badge-light-warning">Belum Absen</span>
                @endif
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="fs-8 text-muted text-uppercase fw-bold mb-2">Jam Masuk</div>
                <div class="fs-4 fw-bold">{{ $absensiMasuk?->recorded_at->format('H:i') ?? '--:--' }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="fs-8 text-muted text-uppercase fw-bold mb-2">Status Pulang</div>
                @if ($absensiPulang)
                    <span class="badge badge-light-success">Sudah Absen</span>
                @elseif ($pengajuanHariIni)
                    <span class="badge badge-light-info">{{ $pengajuanHariIni->type_label }}</span>
                @else
                    <span class="badge badge-light-secondary">Belum Absen</span>
                @endif
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="fs-8 text-muted text-uppercase fw-bold mb-2">Jam Pulang</div>
                <div class="fs-4 fw-bold">{{ $absensiPulang?->recorded_at->format('H:i') ?? '--:--' }}</div>
            </div>
        </div>
    </div>

    {{-- KARTU OPSI ABSENSI --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Hai, apa kabar?</h3>
                <div class="card-toolbar d-flex align-items-center gap-3">
                    <span class="text-muted fs-7">Pilih opsi absensi hari ini</span>
                    @can('absensi-pengajuan.view')
                        <a href="{{ route('pengajuan.index') }}" class="btn btn-sm btn-light-dark">
                            Riwayat Pengajuan
                        </a>
                    @endcan
                </div>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    {{-- Hadir (Masuk) --}}
                    <div class="col-6 col-lg-3">
                        <button type="button"
                                class="btn btn-outline btn-outline-dashed btn-active-light-success w-100 h-100 p-5 text-start"
                                data-bs-toggle="modal" data-bs-target="#absensiModal"
                                data-type="masuk"
                                {{ ($absensiMasuk || $pengajuanHariIni) ? 'disabled' : '' }}>
                            <i class="ki-duotone ki-check-square fs-2x text-success mb-3 d-block"><span class="path1"></span><span class="path2"></span></i>
                            <div class="fw-bold text-gray-900">Hadir (Masuk)</div>
                            <div class="fs-8 text-muted">Biometrik wajah & GPS</div>
                        </button>
                    </div>

                    @can('absensi-pengajuan.create')
                    {{-- Sakit --}}
                    <div class="col-6 col-lg-3">
                        <button type="button"
                                class="btn btn-outline btn-outline-dashed btn-active-light-info w-100 h-100 p-5 text-start"
                                data-bs-toggle="modal" data-bs-target="#pengajuanModal"
                                data-type="sakit"
                                {{ $absensiMasuk ? 'disabled' : '' }}>
                            <i class="ki-duotone ki-cross-square fs-2x text-info mb-3 d-block"><span class="path1"></span><span class="path2"></span></i>
                            <div class="fw-bold text-gray-900">Sakit</div>
                            <div class="fs-8 text-muted">{{ $absensiMasuk ? 'Sudah absen masuk hari ini' : 'Sakit hari ini + bukti foto' }}</div>
                        </button>
                    </div>

                    {{-- Izin / Cuti --}}
                    <div class="col-6 col-lg-3">
                        <button type="button"
                                class="btn btn-outline btn-outline-dashed btn-active-light-warning w-100 h-100 p-5 text-start"
                                data-bs-toggle="modal" data-bs-target="#pengajuanModal"
                                data-type="izin"
                                {{ $absensiMasuk ? 'disabled' : '' }}>
                            <i class="ki-duotone ki-calendar fs-2x text-warning mb-3 d-block"><span class="path1"></span><span class="path2"></span></i>
                            <div class="fw-bold text-gray-900">Izin / Cuti</div>
                            <div class="fs-8 text-muted">{{ $absensiMasuk ? 'Sudah absen masuk hari ini' : 'Diperiksa admin dulu' }}</div>
                        </button>
                    </div>
                    @endcan

                    {{-- Absen Pulang --}}
                    <div class="col-6 col-lg-3">
                        <button type="button"
                                class="btn btn-outline btn-outline-dashed btn-active-light-primary w-100 h-100 p-5 text-start"
                                data-bs-toggle="modal" data-bs-target="#absensiModal"
                                data-type="pulang"
                                {{ (! $absensiMasuk || $absensiPulang || $pengajuanHariIni) ? 'disabled' : '' }}>
                            <i class="ki-duotone ki-exit-right fs-2x text-primary mb-3 d-block"><span class="path1"></span><span class="path2"></span></i>
                            <div class="fw-bold text-gray-900">Absen Pulang</div>
                            <div class="fs-8 text-muted">Lapor kepulangan</div>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- LOG KEHADIRAN HARI INI --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header flex-wrap gap-3">
                <div class="d-flex gap-2">
                    <button type="button" id="tab-log-masuk" class="btn btn-sm btn-color-gray-700 btn-active-light-success active" onclick="switchLogTab('masuk')">
                        Data Absensi Masuk
                        <span class="badge badge-light-success ms-1">{{ $logAbsensiMasuk->count() }}</span>
                    </button>
                    <button type="button" id="tab-log-pulang" class="btn btn-sm btn-color-gray-700 btn-active-light-primary" onclick="switchLogTab('pulang')">
                        Data Absensi Pulang
                        <span class="badge badge-light-primary ms-1">{{ $logAbsensiPulang->count() }}</span>
                    </button>
                </div>
                <div class="card-toolbar">
                    <input type="text" id="log-search-input" class="form-control form-control-sm w-250px"
                           placeholder="Cari nama pegawai...">
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="text-muted fs-8 mb-3">Menampilkan data absensi hari ini, {{ now()->translatedFormat('d F Y') }} saja.</div>

                {{-- Tabel Absensi Masuk --}}
                <div id="log-panel-masuk" class="table-responsive">
                    <table class="table table-row-dashed align-middle" id="log-table-masuk">
                        <thead>
                            <tr class="text-muted fw-bold fs-8 text-uppercase">
                                <th>#</th>
                                <th>Nama Pegawai</th>
                                <th>Waktu Masuk</th>
                                <th>Lokasi Kerja</th>
                                <th>Jarak dari Lokasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($logAbsensiMasuk as $i => $log)
                                <tr class="log-row" data-name="{{ strtolower($log->user->name) }}">
                                    <td>{{ $i + 1 }}</td>
                                    <td class="fw-bold">{{ $log->user->name }}</td>
                                    <td>{{ $log->recorded_at->format('H:i:s') }} WIB</td>
                                    <td>{{ $log->workLocation?->name ?? '-' }}</td>
                                    <td>
                                        <span class="badge badge-light-success">
                                            {{ round($log->distance_meters) }}m &middot; Valid
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-5">Belum ada yang absen masuk hari ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Tabel Absensi Pulang --}}
                <div id="log-panel-pulang" class="table-responsive d-none">
                    <table class="table table-row-dashed align-middle" id="log-table-pulang">
                        <thead>
                            <tr class="text-muted fw-bold fs-8 text-uppercase">
                                <th>#</th>
                                <th>Nama Pegawai</th>
                                <th>Waktu Pulang</th>
                                <th>Lokasi Kerja</th>
                                <th>Jarak dari Lokasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($logAbsensiPulang as $i => $log)
                                <tr class="log-row" data-name="{{ strtolower($log->user->name) }}">
                                    <td>{{ $i + 1 }}</td>
                                    <td class="fw-bold">{{ $log->user->name }}</td>
                                    <td>{{ $log->recorded_at->format('H:i:s') }} WIB</td>
                                    <td>{{ $log->workLocation?->name ?? '-' }}</td>
                                    <td>
                                        <span class="badge badge-light-success">
                                            {{ round($log->distance_meters) }}m &middot; Valid
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-5">Belum ada yang absen pulang hari ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MODAL: kamera + geolokasi --}}
<div class="modal fade" id="absensiModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="absensi-form" action="{{ route('absensi.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="type" id="modal-type-input">
                <input type="hidden" name="latitude" id="latitude-input">
                <input type="hidden" name="longitude" id="longitude-input">
                <input type="file" name="photo" id="photo-input" accept="image/*" class="d-none">

                <div class="modal-header">
                    <h4 class="modal-title" id="modal-title-text">Absen Masuk</h4>
                    <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal" aria-label="Tutup">
                        <i class="ki-duotone ki-cross fs-2"><span class="path1"></span><span class="path2"></span></i>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="ratio ratio-4x3 bg-dark rounded overflow-hidden mb-4">
                        <video id="camera-preview" autoplay playsinline muted class="w-100 h-100 object-fit-cover d-block"></video>
                        <canvas id="camera-canvas" class="d-none"></canvas>
                        <img id="captured-photo" class="w-100 h-100 object-fit-cover d-none" alt="Foto absensi">
                    </div>

                    {{-- Pesan error kamera (izin ditolak, kamera dipakai aplikasi lain, dll) — terpisah dari status GPS --}}
                    <div id="camera-message" class="alert alert-danger py-2 px-3 fs-7 mb-4 d-none"></div>

                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <button type="button" id="btn-capture" class="btn btn-sm btn-light-primary" disabled>
                                <i class="ki-duotone ki-camera fs-3"><span class="path1"></span><span class="path2"></span></i>
                                Ambil Foto
                            </button>
                            <button type="button" id="btn-retake" class="btn btn-sm btn-light-secondary d-none">
                                Ambil Ulang
                            </button>
                        </div>
                        <span id="gps-status" class="badge badge-light-secondary">
                            <i class="ki-duotone ki-geolocation fs-6 me-1"><span class="path1"></span><span class="path2"></span></i>
                            Mencari lokasi...
                        </span>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Kembali</button>
                    <button type="submit" id="btn-submit" class="btn btn-success" disabled>
                        <span id="btn-submit-label">Mencari Lokasi...</span>
                        <span id="btn-submit-spinner" class="spinner-border spinner-border-sm align-middle ms-2"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL: pengajuan sakit / izin / cuti — bisa dari mana saja, tidak ada cek lokasi --}}
<div class="modal fade" id="pengajuanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="pengajuan-form" action="{{ route('pengajuan.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="type" id="pengajuan-type-input">

                <div class="modal-header">
                    <h4 class="modal-title" id="pengajuan-title-text">Ajukan Sakit</h4>
                    <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal" aria-label="Tutup">
                        <i class="ki-duotone ki-cross fs-2"><span class="path1"></span><span class="path2"></span></i>
                    </button>
                </div>

                <div class="modal-body">
                    {{-- Hanya muncul untuk Izin/Cuti, supaya user memilih salah satu jenisnya --}}
                    <div id="pengajuan-jenis-wrap" class="mb-4 d-none">
                        <label class="form-label fw-bold">Jenis Pengajuan</label>
                        <select id="pengajuan-jenis-select" class="form-select">
                            <option value="izin">Izin</option>
                            <option value="cuti">Cuti</option>
                        </select>
                    </div>

                    {{-- Sakit: tanggal otomatis hari ini, jadi tidak ada input tanggal --}}
                    <div id="pengajuan-sakit-note" class="alert alert-light-info mb-4 d-none">
                        Sakit dicatat untuk <strong>hari ini, {{ now()->translatedFormat('d F Y') }}</strong>.
                        Kalau besok masih sakit, laporkan lagi besok.
                    </div>

                    {{-- Izin / Cuti: pilih rentang tanggal sendiri --}}
                    <div id="pengajuan-tanggal-wrap" class="row g-3 mb-4">
                        <div class="col-6">
                            <label class="form-label fw-bold">Tanggal Mulai</label>
                            <input type="date" name="tanggal_mulai" id="pengajuan-tanggal-mulai" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">Tanggal Selesai</label>
                            <input type="date" name="tanggal_selesai" id="pengajuan-tanggal-selesai" class="form-control" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Alasan</label>
                        <textarea name="alasan" class="form-control" rows="3" minlength="5" maxlength="1000" required
                                  placeholder="Jelaskan alasan secara singkat..."></textarea>
                    </div>

                    {{-- Hanya muncul untuk Sakit --}}
                    <div id="pengajuan-foto-wrap" class="mb-2 d-none">
                        <label class="form-label fw-bold">Foto Bukti (surat keterangan dokter, dll)</label>
                        <input type="file" name="foto" id="pengajuan-foto-input" accept="image/*" class="form-control">
                        <div class="form-text">Maksimal 5 MB.</div>
                        <div id="pengajuan-foto-error" class="alert alert-danger py-2 px-3 fs-7 mt-3 mb-0 d-none"></div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Kembali</button>
                    <button type="submit" id="pengajuan-submit" class="btn btn-success">Kirim Pengajuan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Batas ukuran foto di browser. Samakan dengan rule max di controller.
    const ABSENSI_MAX_FOTO_MB = 5;    // AbsensiController@store: max:5120
    const PENGAJUAN_MAX_FOTO_MB = 5;  // samakan dengan rule 'foto' di PengajuanController

    function formatUkuranFile(bytes) {
        return (bytes / 1024 / 1024).toFixed(1) + ' MB';
    }

document.addEventListener('DOMContentLoaded', function () {
    const modalEl = document.getElementById('absensiModal');
    const modalTitle = document.getElementById('modal-title-text');
    const typeInput = document.getElementById('modal-type-input');

    const video = document.getElementById('camera-preview');
    const canvas = document.getElementById('camera-canvas');
    const capturedPhoto = document.getElementById('captured-photo');
    const btnCapture = document.getElementById('btn-capture');
    const btnRetake = document.getElementById('btn-retake');
    const btnSubmit = document.getElementById('btn-submit');
    const btnSubmitLabel = document.getElementById('btn-submit-label');
    const btnSubmitSpinner = document.getElementById('btn-submit-spinner');
    const gpsStatus = document.getElementById('gps-status');
    const cameraMessage = document.getElementById('camera-message');
    const form = document.getElementById('absensi-form');
    const photoInput = document.getElementById('photo-input');
    const latitudeInput = document.getElementById('latitude-input');
    const longitudeInput = document.getElementById('longitude-input');

    let mediaStream = null;
    let photoBlob = null;
    let currentPosition = null;
    let watchId = null;
    let modalDibuka = false;

    function resetModalState() {
        photoBlob = null;
        currentPosition = null;
        photoInput.value = '';
        capturedPhoto.classList.add('d-none');
        video.classList.remove('d-none');
        btnRetake.classList.add('d-none');
        btnCapture.classList.remove('d-none');
        btnCapture.disabled = true; // baru aktif setelah kamera benar-benar menampilkan gambar
        tampilkanPesanKamera('');
        gpsStatus.className = 'badge badge-light-secondary';
        gpsStatus.innerHTML = '<i class="ki-duotone ki-geolocation fs-6 me-1"><span class="path1"></span><span class="path2"></span></i> Mencari lokasi...';
        updateSubmitState();
    }

    function updateSubmitState() {
        const ready = photoBlob && currentPosition;
        btnSubmit.disabled = !ready;
        btnSubmitLabel.textContent = ready ? 'Kirim Absensi' : (currentPosition ? 'Ambil foto dulu' : 'Mencari Lokasi...');
        // spinner hanya saat masih mencari lokasi
        btnSubmitSpinner.classList.toggle('d-none', Boolean(currentPosition));
    }

    // ---------- Kamera ----------
    // Pesan kamera punya tempat sendiri (bukan di badge GPS), supaya tidak tertimpa status lokasi.
    function tampilkanPesanKamera(teks) {
        cameraMessage.textContent = teks;
        cameraMessage.classList.toggle('d-none', ! teks);
    }

    function pesanErrorKamera(error) {
        switch (error?.name) {
            case 'NotAllowedError':
            case 'PermissionDeniedError':
                return 'Izin kamera ditolak. Klik ikon gembok/kamera di address bar, izinkan kamera, lalu buka ulang jendela ini.';
            case 'NotFoundError':
            case 'DevicesNotFoundError':
                return 'Kamera tidak ditemukan di perangkat ini.';
            case 'NotReadableError':
            case 'TrackStartError':
                return 'Kamera sedang dipakai aplikasi atau tab lain. Tutup yang lain, lalu buka ulang jendela ini.';
            default:
                return 'Kamera tidak dapat dibuka' + (error?.name ? ' (' + error.name + ')' : '') + '.';
        }
    }

    async function mulaiKamera() {
        // getUserMedia hanya tersedia di HTTPS atau localhost
        if (! navigator.mediaDevices?.getUserMedia) {
            tampilkanPesanKamera('Browser ini tidak mendukung kamera, atau halaman tidak dibuka lewat HTTPS / localhost.');
            return;
        }

        try {
            const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false });

            // jendela sudah ditutup sebelum izin kamera selesai: matikan lagi supaya lampu kamera tidak menyala terus
            if (! modalDibuka) {
                stream.getTracks().forEach((track) => track.stop());
                return;
            }

            mediaStream = stream;
            video.srcObject = stream;
            await video.play();
        } catch (error) {
            if (modalDibuka) {
                tampilkanPesanKamera(pesanErrorKamera(error));
            }
        }
    }

    // Tombol Ambil Foto baru aktif setelah kamera benar-benar mengirim gambar
    video.addEventListener('playing', () => {
        if (video.videoWidth > 0) {
            btnCapture.disabled = false;
            tampilkanPesanKamera('');
        }
    });

    // ---------- Buka modal: mulai kamera + geolokasi ----------
    modalEl.addEventListener('show.bs.modal', function (event) {
        const trigger = event.relatedTarget;
        const type = trigger?.dataset.type ?? 'masuk';

        typeInput.value = type;
        modalTitle.textContent = type === 'masuk' ? 'Absen Masuk' : 'Absen Pulang';

        modalDibuka = true;
        resetModalState();
        mulaiKamera();

        if ('geolocation' in navigator) {
            watchId = navigator.geolocation.watchPosition(
                (position) => {
                    currentPosition = position.coords;
                    const akurasi = Math.round(position.coords.accuracy);
                    gpsStatus.className = akurasi <= 30 ? 'badge badge-light-success' : 'badge badge-light-warning';
                    gpsStatus.innerHTML = `<i class="ki-duotone ki-geolocation fs-6 me-1"><span class="path1"></span><span class="path2"></span></i> Lokasi didapat (±${akurasi}m)`;
                    updateSubmitState();
                },
                () => {
                    gpsStatus.className = 'badge badge-light-danger';
                    gpsStatus.textContent = 'Izin lokasi ditolak';
                },
                { enableHighAccuracy: true, maximumAge: 5000, timeout: 20000 }
            );
        } else {
            gpsStatus.className = 'badge badge-light-danger';
            gpsStatus.textContent = 'Geolokasi tidak didukung browser ini';
        }
    });

    // ---------- Tutup modal: matikan kamera & GPS ----------
    modalEl.addEventListener('hidden.bs.modal', function () {
        modalDibuka = false;

        if (mediaStream) {
            mediaStream.getTracks().forEach((track) => track.stop());
            mediaStream = null;
        }
        video.srcObject = null;
        if (watchId !== null) {
            navigator.geolocation.clearWatch(watchId);
            watchId = null;
        }
        resetModalState();
    });

    // ---------- Ambil foto ----------
    btnCapture.addEventListener('click', () => {
        // Kamera belum mengirim gambar = ukuran video 0, canvas 0x0, lalu toBlob() mengembalikan null
        // dan URL.createObjectURL(null) melempar TypeError. Tahan di sini dengan pesan yang jelas.
        if (! video.videoWidth || ! video.videoHeight) {
            tampilkanPesanKamera('Kamera belum siap. Tunggu gambar kamera muncul, lalu ambil foto.');
            return;
        }

        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0);

        canvas.toBlob((blob) => {
            if (! blob) {
                tampilkanPesanKamera('Gagal mengambil foto. Coba lagi.');
                return;
            }
                if (blob.size > ABSENSI_MAX_FOTO_MB * 1024 * 1024) {
                tampilkanPesanKamera('Ukuran foto ' + formatUkuranFile(blob.size) + ' terlalu besar. Maksimal ' + ABSENSI_MAX_FOTO_MB + ' MB. Coba ambil ulang.');
                return;
            }

            tampilkanPesanKamera('');
            photoBlob = blob;
            capturedPhoto.src = URL.createObjectURL(blob);
            capturedPhoto.classList.remove('d-none');
            video.classList.add('d-none');
            btnCapture.classList.add('d-none');
            btnRetake.classList.remove('d-none');

            const file = new File([blob], 'absensi.jpg', { type: 'image/jpeg' });
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            photoInput.files = dataTransfer.files;

            updateSubmitState();
        }, 'image/jpeg', 0.9);
    });

    btnRetake.addEventListener('click', () => {
        photoBlob = null;
        photoInput.value = '';
        capturedPhoto.classList.add('d-none');
        video.classList.remove('d-none');
        btnRetake.classList.add('d-none');
        btnCapture.classList.remove('d-none');
        btnCapture.disabled = ! (video.videoWidth > 0);
        updateSubmitState();
    });

    // ---------- Submit (native, bukan fetch, supaya session errors Laravel tetap jalan) ----------
    form.addEventListener('submit', function (e) {
        if (!photoBlob || !currentPosition) {
            e.preventDefault();
            return;
        }

        latitudeInput.value = currentPosition.latitude;
        longitudeInput.value = currentPosition.longitude;

        btnSubmit.disabled = true;
        btnSubmitLabel.textContent = 'Mengirim...';
        btnSubmitSpinner.classList.remove('d-none');
    });

    // ---------- Jam berjalan di hero banner ----------
    setInterval(() => {
        const el = document.getElementById('live-clock');
        if (el) el.textContent = new Date().toLocaleTimeString('id-ID', { hour12: false });
    }, 1000);

    // ---------- Log kehadiran: search nama (client-side, data cuma hari ini) ----------
    const searchInput = document.getElementById('log-search-input');
    searchInput.addEventListener('input', function () {
        const keyword = this.value.trim().toLowerCase();
        document.querySelectorAll('.log-row').forEach((row) => {
            const match = row.dataset.name.includes(keyword);
            row.classList.toggle('d-none', !match);
        });
    });
});

// ---------- Log kehadiran: switch tab Masuk / Pulang ----------
// Diletakkan di luar DOMContentLoaded supaya bisa dipanggil dari onclick di HTML.
function switchLogTab(type) {
    const isMasuk = type === 'masuk';

    document.getElementById('log-panel-masuk').classList.toggle('d-none', !isMasuk);
    document.getElementById('log-panel-pulang').classList.toggle('d-none', isMasuk);

    document.getElementById('tab-log-masuk').classList.toggle('active', isMasuk);
    document.getElementById('tab-log-pulang').classList.toggle('active', !isMasuk);

    // reset search supaya tidak membingungkan saat pindah tab
    const searchInput2 = document.getElementById('log-search-input');
    searchInput2.value = '';
    document.querySelectorAll('.log-row').forEach((row) => row.classList.remove('d-none'));
}

// ---------- Modal pengajuan: Sakit / Izin / Cuti ----------
document.addEventListener('DOMContentLoaded', function () {
    const modalEl = document.getElementById('pengajuanModal');
    const titleText = document.getElementById('pengajuan-title-text');
    const typeInput = document.getElementById('pengajuan-type-input');
    const jenisWrap = document.getElementById('pengajuan-jenis-wrap');
    const jenisSelect = document.getElementById('pengajuan-jenis-select');
    const fotoWrap = document.getElementById('pengajuan-foto-wrap');
    const fotoInput = document.getElementById('pengajuan-foto-input');
    const tanggalMulai = document.getElementById('pengajuan-tanggal-mulai');
    const tanggalSelesai = document.getElementById('pengajuan-tanggal-selesai');
    const tanggalWrap = document.getElementById('pengajuan-tanggal-wrap');
    const sakitNote = document.getElementById('pengajuan-sakit-note');
    const form = document.getElementById('pengajuan-form');
    const fotoError = document.getElementById('pengajuan-foto-error');
    const maxFotoBytes = PENGAJUAN_MAX_FOTO_MB * 1024 * 1024;

    function tampilkanErrorFoto(teks) {
        fotoError.textContent = teks;
        fotoError.classList.toggle('d-none', ! teks);
    }

    function pesanFotoTerlaluBesar(file) {
        return 'Ukuran foto ' + formatUkuranFile(file.size) + ' terlalu besar. Maksimal ' + PENGAJUAN_MAX_FOTO_MB + ' MB.';
    }

    // cek begitu file dipilih
    fotoInput.addEventListener('change', function () {
        const file = this.files[0];

        if (file && file.size > maxFotoBytes) {
            tampilkanErrorFoto(pesanFotoTerlaluBesar(file));
            this.value = ''; // kosongkan supaya tidak ikut terkirim
            return;
        }

        tampilkanErrorFoto('');
    });

    // jaring pengaman saat submit
    form.addEventListener('submit', function (e) {
        const file = fotoInput.files[0];

        if (file && file.size > maxFotoBytes) {
            e.preventDefault();
            tampilkanErrorFoto(pesanFotoTerlaluBesar(file));
        }
    });

    function terapkanJenis(jenis) {
        typeInput.value = jenis;
        const isSakit = jenis === 'sakit';

        // Sakit: tanggal otomatis hari ini (diisi server), jadi input tanggal disembunyikan.
        // disabled = tidak ikut terkirim dan tidak ikut divalidasi browser.
        tanggalWrap.classList.toggle('d-none', isSakit);
        sakitNote.classList.toggle('d-none', ! isSakit);
        tanggalMulai.disabled = isSakit;
        tanggalSelesai.disabled = isSakit;
        tanggalMulai.required = ! isSakit;
        tanggalSelesai.required = ! isSakit;

        fotoWrap.classList.toggle('d-none', ! isSakit);
        fotoInput.required = isSakit;

        if (isSakit) {
            titleText.textContent = 'Ajukan Sakit';
        } else {
            titleText.textContent = jenis === 'cuti' ? 'Ajukan Cuti' : 'Ajukan Izin';
            fotoInput.value = '';
            // izin/cuti mulai dari hari ini; pakai tanggal lokal browser (bukan UTC dari toISOString)
            const hariIni = new Date().toLocaleDateString('en-CA');
            tanggalMulai.min = hariIni;
            tanggalSelesai.min = hariIni;
        }
    }

    modalEl.addEventListener('show.bs.modal', function (event) {
        const trigger = event.relatedTarget;
        const type = trigger?.dataset.type ?? 'sakit';

        form.reset();
        tampilkanErrorFoto(''); 
        jenisWrap.classList.toggle('d-none', type === 'sakit');
        if (type !== 'sakit') {
            jenisSelect.value = 'izin';
        }

        terapkanJenis(type);
    });

    jenisSelect.addEventListener('change', () => terapkanJenis(jenisSelect.value));

    // tanggal selesai tidak boleh lebih awal dari tanggal mulai
    tanggalMulai.addEventListener('change', function () {
        document.getElementById('pengajuan-tanggal-selesai').min = this.value;
    });
});
</script>
@endpush