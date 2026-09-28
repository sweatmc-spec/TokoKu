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
                <div class="card-toolbar text-muted fs-7">Pilih opsi absensi hari ini</div>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    {{-- Hadir (Masuk) --}}
                    <div class="col-6 col-lg-3">
                        <button type="button"
                                class="btn btn-outline btn-outline-dashed btn-active-light-success w-100 h-100 p-5 text-start"
                                data-bs-toggle="modal" data-bs-target="#absensiModal"
                                data-type="masuk"
                                {{ $absensiMasuk ? 'disabled' : '' }}>
                            <i class="ki-duotone ki-check-square fs-2x text-success mb-3 d-block"><span class="path1"></span><span class="path2"></span></i>
                            <div class="fw-bold text-gray-900">Hadir (Masuk)</div>
                            <div class="fs-8 text-muted">Biometrik wajah & GPS</div>
                        </button>
                    </div>

                    {{-- Sakit — belum dibangun --}}
                    <div class="col-6 col-lg-3">
                        <div class="btn btn-outline btn-outline-dashed w-100 h-100 p-5 text-start opacity-50" style="cursor:not-allowed">
                            <i class="ki-duotone ki-cross-square fs-2x text-danger mb-3 d-block"><span class="path1"></span><span class="path2"></span></i>
                            <div class="fw-bold text-gray-900">Sakit</div>
                            <div class="fs-8 text-muted">Segera hadir</div>
                        </div>
                    </div>

                    {{-- Izin / Cuti — belum dibangun --}}
                    <div class="col-6 col-lg-3">
                        <div class="btn btn-outline btn-outline-dashed w-100 h-100 p-5 text-start opacity-50" style="cursor:not-allowed">
                            <i class="ki-duotone ki-calendar fs-2x text-warning mb-3 d-block"><span class="path1"></span><span class="path2"></span></i>
                            <div class="fw-bold text-gray-900">Izin / Cuti</div>
                            <div class="fs-8 text-muted">Segera hadir</div>
                        </div>
                    </div>

                    {{-- Absen Pulang --}}
                    <div class="col-6 col-lg-3">
                        <button type="button"
                                class="btn btn-outline btn-outline-dashed btn-active-light-primary w-100 h-100 p-5 text-start"
                                data-bs-toggle="modal" data-bs-target="#absensiModal"
                                data-type="pulang"
                                {{ (!$absensiMasuk || $absensiPulang) ? 'disabled' : '' }}>
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

                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <button type="button" id="btn-capture" class="btn btn-sm btn-light-primary">
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
@endsection

@push('scripts')
<script>
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
    const gpsStatus = document.getElementById('gps-status');
    const form = document.getElementById('absensi-form');
    const photoInput = document.getElementById('photo-input');
    const latitudeInput = document.getElementById('latitude-input');
    const longitudeInput = document.getElementById('longitude-input');

    let mediaStream = null;
    let photoBlob = null;
    let currentPosition = null;
    let watchId = null;

    function resetModalState() {
        photoBlob = null;
        currentPosition = null;
        photoInput.value = '';
        capturedPhoto.classList.add('d-none');
        video.classList.remove('d-none');
        btnRetake.classList.add('d-none');
        btnCapture.classList.remove('d-none');
        gpsStatus.className = 'badge badge-light-secondary';
        gpsStatus.innerHTML = '<i class="ki-duotone ki-geolocation fs-6 me-1"><span class="path1"></span><span class="path2"></span></i> Mencari lokasi...';
        updateSubmitState();
    }

    function updateSubmitState() {
        const ready = photoBlob && currentPosition;
        btnSubmit.disabled = !ready;
        btnSubmitLabel.textContent = ready ? 'Kirim Absensi' : (currentPosition ? 'Ambil foto dulu' : 'Mencari Lokasi...');
    }

    // ---------- Buka modal: mulai kamera + geolokasi ----------
    modalEl.addEventListener('show.bs.modal', function (event) {
        const trigger = event.relatedTarget;
        const type = trigger?.dataset.type ?? 'masuk';

        typeInput.value = type;
        modalTitle.textContent = type === 'masuk' ? 'Absen Masuk' : 'Absen Pulang';

        resetModalState();

        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false })
            .then((stream) => {
                mediaStream = stream;
                video.srcObject = stream;
            })
            .catch(() => {
                gpsStatus.className = 'badge badge-light-danger';
                gpsStatus.textContent = 'Kamera tidak dapat diakses';
            });

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
        if (mediaStream) {
            mediaStream.getTracks().forEach((track) => track.stop());
            mediaStream = null;
        }
        if (watchId !== null) {
            navigator.geolocation.clearWatch(watchId);
            watchId = null;
        }
        resetModalState();
    });

    // ---------- Ambil foto ----------
    btnCapture.addEventListener('click', () => {
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0);

        canvas.toBlob((blob) => {
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
    const searchInput = document.getElementById('log-search-input');
    searchInput.value = '';
    document.querySelectorAll('.log-row').forEach((row) => row.classList.remove('d-none'));
}
</script>
@endpush