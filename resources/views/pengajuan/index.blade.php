@extends('layouts.app') {{-- sesuaikan dengan layout Metronic yang sudah kamu pakai --}}

@section('title', 'Pengajuan Sakit / Izin / Cuti')

@section('content')
@php
    $totalPengajuan = $counts->sum();
    $cur  = $pengajuans->currentPage();
    $last = $pengajuans->lastPage();
    $from = max(1, $cur - 2);
    $to   = min($last, $cur + 2);
@endphp

<div class="row g-5">

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

    {{-- ====== KARTU RINGKASAN ====== --}}
    <div class="col-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body d-flex justify-content-between align-items-start">
                <div>
                    <div class="text-gray-600 fw-semibold fs-7 mb-2">Total Pengajuan</div>
                    <div class="fs-2hx fw-bold text-gray-900 lh-1">{{ $totalPengajuan }}</div>
                    <div class="text-muted fs-8 mt-3">{{ $canSeeAll ? 'Semua karyawan' : 'Semua pengajuanmu' }}</div>
                </div>
                <i class="ki-duotone ki-document fs-2x text-primary"><span class="path1"></span><span class="path2"></span></i>
            </div>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body d-flex justify-content-between align-items-start">
                <div>
                    <div class="text-gray-600 fw-semibold fs-7 mb-2">Menunggu Review</div>
                    <div class="fs-2hx fw-bold text-warning lh-1">{{ $counts->get('menunggu', 0) }}</div>
                    <div class="text-muted fs-8 mt-3">{{ $canSeeAll ? 'Perlu verifikasi segera' : 'Belum diputuskan' }}</div>
                </div>
                <i class="ki-duotone ki-time fs-2x text-warning"><span class="path1"></span><span class="path2"></span></i>
            </div>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body d-flex justify-content-between align-items-start">
                <div>
                    <div class="text-gray-600 fw-semibold fs-7 mb-2">Disetujui (Diterima)</div>
                    <div class="fs-2hx fw-bold text-success lh-1">{{ $counts->get('disetujui', 0) }}</div>
                    <div class="text-muted fs-8 mt-3">Pengajuan yang diterima</div>
                </div>
                <i class="ki-duotone ki-check-circle fs-2x text-success"><span class="path1"></span><span class="path2"></span></i>
            </div>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body d-flex justify-content-between align-items-start">
                <div>
                    <div class="text-gray-600 fw-semibold fs-7 mb-2">Ditolak</div>
                    <div class="fs-2hx fw-bold text-danger lh-1">{{ $counts->get('ditolak', 0) }}</div>
                    <div class="text-muted fs-8 mt-3">Tidak memenuhi syarat</div>
                </div>
                <i class="ki-duotone ki-cross-circle fs-2x text-danger"><span class="path1"></span><span class="path2"></span></i>
            </div>
        </div>
    </div>

    {{-- ====== TABEL PENGAJUAN ====== --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header align-items-center flex-wrap gap-4 py-6">
                <div class="card-title flex-column align-items-start m-0">
                    <h3 class="fw-bold text-gray-900 mb-1">{{ $canSeeAll ? 'Pengajuan Sakit / Izin / Cuti' : 'Pengajuan Saya' }}</h3>
                    <div class="text-muted fs-7 fw-normal mw-500px">
                        {{ $canSeeAll ? 'Semua pengajuan karyawan. Periksa lalu Terima atau Tolak; setelah diputuskan bisa di-Edit atau di-Hapus.' : 'Status pengajuan sakit, izin, dan cuti yang pernah kamu kirim.' }}
                    </div>
                </div>

                <div class="card-toolbar flex-column align-items-stretch align-items-lg-end gap-3 m-0">
                    @if ($canSeeAll)
                        <div class="position-relative">
                            <i class="ki-duotone ki-magnifier fs-3 text-gray-500 position-absolute top-50 translate-middle-y ms-4">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                            <input type="text" id="pengajuan-search-input"
                                   class="form-control form-control-solid form-control-sm rounded-pill ps-12 w-100 w-lg-300px"
                                   value="{{ $search }}" placeholder="Cari nama karyawan...">
                        </div>
                    @endif

                    {{-- filter status (pill) --}}
                    <div class="d-inline-flex flex-wrap gap-1 bg-light rounded-pill p-1">
                        <a href="{{ route('pengajuan.index', array_filter(['q' => $search])) }}"
                           class="btn btn-sm rounded-pill {{ $status === null ? 'btn-success' : 'btn-active-light-success btn-color-gray-600' }}">Semua</a>
                        <a href="{{ route('pengajuan.index', array_filter(['status' => 'menunggu', 'q' => $search])) }}"
                           class="btn btn-sm rounded-pill {{ $status === 'menunggu' ? 'btn-success' : 'btn-active-light-success btn-color-gray-600' }}">
                            Menunggu <span class="badge badge-circle badge-warning ms-1">{{ $counts->get('menunggu', 0) }}</span>
                        </a>
                        <a href="{{ route('pengajuan.index', array_filter(['status' => 'disetujui', 'q' => $search])) }}"
                           class="btn btn-sm rounded-pill {{ $status === 'disetujui' ? 'btn-success' : 'btn-active-light-success btn-color-gray-600' }}">Diterima</a>
                        <a href="{{ route('pengajuan.index', array_filter(['status' => 'ditolak', 'q' => $search])) }}"
                           class="btn btn-sm rounded-pill {{ $status === 'ditolak' ? 'btn-success' : 'btn-active-light-success btn-color-gray-600' }}">Ditolak</a>
                    </div>
                </div>
            </div>

            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table table-row-bordered table-hover align-middle gs-0 gy-4">
                        <thead>
                            <tr class="text-gray-500 fw-bold fs-8 text-uppercase">
                                @if ($canSeeAll)
                                    <th class="min-w-150px">Karyawan</th>
                                @endif
                                <th class="min-w-125px">Jenis</th>
                                <th class="min-w-175px">Tanggal &amp; Durasi</th>
                                <th class="min-w-250px">Alasan</th>
                                <th>Bukti</th>
                                <th class="min-w-150px">Status</th>
                                <th class="text-end min-w-150px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($pengajuans as $p)
                                <tr>
                                    @if ($canSeeAll)
                                        <td>
                                            <div class="fw-bold fs-6 text-gray-900">{{ $p->user->name }}</div>
                                        </td>
                                    @endif

                                    <td>
                                        <span class="badge {{ $p->type_badge }} rounded-pill px-3 py-2 d-inline-flex align-items-center">
                                            <span class="bullet bullet-dot me-2" style="background-color: currentColor;"></span>{{ $p->type_label }}
                                        </span>
                                    </td>

                                    <td>
                                        <div class="fw-bold text-gray-900">
                                            {{ $p->tanggal_mulai->translatedFormat('d M Y') }}
                                            @if (! $p->tanggal_mulai->isSameDay($p->tanggal_selesai))
                                                - {{ $p->tanggal_selesai->translatedFormat('d M Y') }}
                                            @endif
                                        </div>
                                        <div class="text-muted fs-8 mt-1">{{ $p->jumlah_hari }} hari</div>
                                    </td>

                                    <td style="max-width: 280px;">
                                        <div class="text-gray-800">{{ \Illuminate\Support\Str::limit($p->alasan, 80) }}</div>
                                        <div class="text-muted fs-8 mt-1">Diajukan: {{ $p->created_at->translatedFormat('d M Y, H:i') }}</div>
                                    </td>

                                    <td>
                                        @if ($p->foto_path)
                                            <a href="{{ route('pengajuan.foto', $p) }}" target="_blank" class="btn btn-sm btn-light-info rounded-pill">
                                                <i class="ki-duotone ki-eye fs-4 me-1"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>Lihat
                                            </a>
                                        @else
                                            <span class="text-muted fs-8 fst-italic">Tanpa bukti</span>
                                        @endif
                                    </td>

                                    <td>
                                        <span class="badge {{ $p->status_badge }} rounded-pill px-3 py-2 d-inline-flex align-items-center">
                                            <span class="bullet bullet-dot me-2" style="background-color: currentColor;"></span>{{ $p->status_label }}
                                        </span>
                                        @if ($p->status !== 'menunggu' && $p->reviewer)
                                            <div class="text-muted fs-8 mt-2">oleh {{ $p->reviewer->name }}</div>
                                        @endif
                                        @if ($p->status === 'ditolak' && $p->catatan_admin)
                                            <div class="text-danger fs-8 mt-1">Alasan: {{ $p->catatan_admin }}</div>
                                        @endif
                                        @if ($p->edited_at)
                                            <div class="text-muted fs-8 mt-1">
                                                Diedit {{ $p->edited_at->translatedFormat('d M Y H:i') }}: {{ $p->alasan_edit }}
                                            </div>
                                        @endif
                                    </td>

                                    <td class="text-end text-nowrap">
                                        @if ($p->status === 'menunggu')
                                            @if ($canApprove)
                                                <button type="button" class="btn btn-sm btn-light-success"
                                                        data-bs-toggle="modal" data-bs-target="#reviewModal"
                                                        data-pengajuan-id="{{ $p->id }}"
                                                        data-aksi="terima"
                                                        data-nama="{{ $p->user->name }}">Terima</button>
                                                <button type="button" class="btn btn-sm btn-light-danger"
                                                        data-bs-toggle="modal" data-bs-target="#reviewModal"
                                                        data-pengajuan-id="{{ $p->id }}"
                                                        data-aksi="tolak"
                                                        data-nama="{{ $p->user->name }}">Tolak</button>
                                            @else
                                                <span class="text-muted fs-8">-</span>
                                            @endif
                                        @else
                                            @if ($canEdit)
                                                <button type="button" class="btn btn-sm btn-icon btn-light-warning"
                                                        title="Edit" aria-label="Edit"
                                                        data-bs-toggle="modal" data-bs-target="#editModal"
                                                        data-pengajuan-id="{{ $p->id }}"
                                                        data-status="{{ $p->status }}"
                                                        data-nama="{{ $p->user->name }}">
                                                    <i class="ki-duotone ki-pencil fs-3"><span class="path1"></span><span class="path2"></span></i>
                                                </button>
                                            @endif
                                            @if ($canDelete)
                                                <button type="button" class="btn btn-sm btn-icon btn-light-danger"
                                                        title="Hapus" aria-label="Hapus"
                                                        data-bs-toggle="modal" data-bs-target="#deleteModal"
                                                        data-pengajuan-id="{{ $p->id }}"
                                                        data-nama="{{ $p->user->name }}">
                                                    <i class="ki-duotone ki-trash fs-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
                                                </button>
                                            @endif
                                            @if (! $canEdit && ! $canDelete)
                                                <span class="text-muted fs-8">-</span>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $canSeeAll ? 7 : 6 }}" class="text-center text-muted py-10">
                                        Belum ada pengajuan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- footer: info + pagination angka --}}
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mt-4">
                    <div class="text-muted fs-8">
                        @if ($pengajuans->total() > 0)
                            Menampilkan <span class="fw-bold text-gray-800">{{ $pengajuans->firstItem() }} - {{ $pengajuans->lastItem() }}</span>
                            dari <span class="fw-bold text-gray-800">{{ $pengajuans->total() }}</span> data pengajuan
                        @endif
                    </div>

                    @if ($pengajuans->hasPages())
                        <div class="d-flex align-items-center gap-2">
                            @if ($pengajuans->onFirstPage())
                                <span class="btn btn-sm btn-icon btn-light disabled">&lsaquo;</span>
                            @else
                                <a href="{{ $pengajuans->previousPageUrl() }}" class="btn btn-sm btn-icon btn-light">&lsaquo;</a>
                            @endif

                            @foreach ($pengajuans->getUrlRange($from, $to) as $page => $url)
                                <a href="{{ $url }}"
                                   class="btn btn-sm btn-icon {{ $page == $cur ? 'btn-primary' : 'btn-light' }}">{{ $page }}</a>
                            @endforeach

                            @if ($pengajuans->hasMorePages())
                                <a href="{{ $pengajuans->nextPageUrl() }}" class="btn btn-sm btn-icon btn-light">&rsaquo;</a>
                            @else
                                <span class="btn btn-sm btn-icon btn-light disabled">&rsaquo;</span>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@if ($canApprove)
    {{-- MODAL: Terima / Tolak pengajuan yang masih menunggu --}}
    <div class="modal fade" id="reviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-450px">
            <div class="modal-content">
                <form id="review-form" method="POST">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="aksi" id="review-aksi-input">

                    <div class="modal-body text-center pt-12 pb-6 px-10">
                        <i id="review-icon" class="ki-duotone ki-check-circle fs-5x text-success mb-5">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                        <h3 id="review-title-text" class="fw-bold text-gray-900 mb-2">Terima Pengajuan</h3>
                        <p id="review-confirm-text" class="text-gray-600 fs-6 mb-0">
                            <span id="review-verb">Terima</span> pengajuan dari <span id="review-nama" class="fw-bold text-gray-900"></span>?
                        </p>

                        <div id="review-catatan-wrap" class="d-none text-start mt-6">
                            <label class="form-label fw-bold">Alasan Penolakan</label>
                            <textarea name="catatan_admin" id="review-catatan-input" class="form-control form-control-solid" rows="3" maxlength="500"
                                      placeholder="Jelaskan alasan penolakan..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer flex-center border-0 pt-0 pb-10 gap-2">
                        <button type="button" class="btn btn-light min-w-100px" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" id="review-submit-btn" class="btn btn-success min-w-100px">Konfirmasi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@if ($canEdit)
    {{-- MODAL: Edit keputusan (select Terima/Tolak + alasan edit) --}}
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-450px">
            <div class="modal-content">
                <form id="edit-form" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="modal-header">
                        <h4 class="modal-title">Edit Keputusan</h4>
                        <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal" aria-label="Tutup">
                            <i class="ki-duotone ki-cross fs-2"><span class="path1"></span><span class="path2"></span></i>
                        </button>
                    </div>

                    <div class="modal-body">
                        <p class="text-gray-600 mb-5">
                            Pengajuan dari <span id="edit-nama-text" class="fw-bold text-gray-900"></span>
                        </p>

                        <div class="mb-5">
                            <label class="form-label fw-bold">Keputusan</label>
                            <select name="aksi" id="edit-aksi-select" class="form-select form-select-solid" required>
                                <option value="terima">Terima</option>
                                <option value="tolak">Tolak</option>
                            </select>
                        </div>

                        <div>
                            <label class="form-label fw-bold">Alasan Edit</label>
                            <textarea name="alasan_edit" id="edit-alasan-input" class="form-control form-control-solid" rows="3"
                                      minlength="3" maxlength="500" required
                                      placeholder="Kenapa keputusan ini diubah?"></textarea>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@if ($canDelete)
    {{-- MODAL: konfirmasi hapus --}}
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-450px">
            <div class="modal-content">
                <form id="delete-form" method="POST">
                    @csrf
                    @method('DELETE')

                    <div class="modal-body text-center pt-12 pb-6 px-10">
                        <i class="ki-duotone ki-trash fs-5x text-danger mb-5">
                            <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span>
                        </i>
                        <h3 class="fw-bold text-gray-900 mb-2">Hapus Pengajuan</h3>
                        <p class="text-gray-600 fs-6 mb-2">
                            Hapus pengajuan dari <span id="delete-nama-text" class="fw-bold text-gray-900"></span>?
                        </p>
                        <p class="text-muted fs-7 mb-0">Foto bukti ikut terhapus dan hari itu dihitung ulang di laporan.</p>
                    </div>
                    <div class="modal-footer flex-center border-0 pt-0 pb-10 gap-2">
                        <button type="button" class="btn btn-light min-w-100px" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger min-w-100px">Ya, Hapus</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const baseUrl = "{{ url('pengajuan') }}";
    const canApprove = '{{ $canApprove ? 1 : 0 }}' === '1';
    const canEdit = '{{ $canEdit ? 1 : 0 }}' === '1';
    const canDelete = '{{ $canDelete ? 1 : 0 }}' === '1';
    const canSeeAll = '{{ $canSeeAll ? 1 : 0 }}' === '1';

    if (canApprove) {
        const reviewModal = document.getElementById('reviewModal');
        const reviewForm = document.getElementById('review-form');
        const reviewAksi = document.getElementById('review-aksi-input');
        const reviewIcon = document.getElementById('review-icon');
        const reviewTitle = document.getElementById('review-title-text');
        const reviewVerb = document.getElementById('review-verb');
        const reviewNama = document.getElementById('review-nama');
        const reviewCatatanWrap = document.getElementById('review-catatan-wrap');
        const reviewCatatan = document.getElementById('review-catatan-input');
        const reviewSubmit = document.getElementById('review-submit-btn');

        reviewModal.addEventListener('show.bs.modal', function (event) {
            const trigger = event.relatedTarget;
            const aksi = trigger?.dataset.aksi;

            reviewForm.action = `${baseUrl}/${trigger?.dataset.pengajuanId}/review`;
            reviewAksi.value = aksi;
            reviewCatatan.value = '';
            reviewNama.textContent = trigger?.dataset.nama ?? '';

            if (aksi === 'terima') {
                reviewIcon.className = 'ki-duotone ki-check-circle fs-5x text-success mb-5';
                reviewTitle.textContent = 'Terima Pengajuan';
                reviewVerb.textContent = 'Terima';
                reviewCatatanWrap.classList.add('d-none');
                reviewCatatan.required = false;
                reviewSubmit.className = 'btn btn-success min-w-100px';
                reviewSubmit.textContent = 'Ya, Terima';
            } else {
                reviewIcon.className = 'ki-duotone ki-cross-circle fs-5x text-danger mb-5';
                reviewTitle.textContent = 'Tolak Pengajuan';
                reviewVerb.textContent = 'Tolak';
                reviewCatatanWrap.classList.remove('d-none');
                reviewCatatan.required = true;
                reviewSubmit.className = 'btn btn-danger min-w-100px';
                reviewSubmit.textContent = 'Ya, Tolak';
            }
        });
    }

    if (canEdit) {
        const editModal = document.getElementById('editModal');
        const editForm = document.getElementById('edit-form');
        const editNama = document.getElementById('edit-nama-text');
        const editSelect = document.getElementById('edit-aksi-select');
        const editAlasan = document.getElementById('edit-alasan-input');

        editModal.addEventListener('show.bs.modal', function (event) {
            const trigger = event.relatedTarget;

            editForm.action = `${baseUrl}/${trigger?.dataset.pengajuanId}`;
            editNama.textContent = trigger?.dataset.nama ?? '';
            // pilihan awal = keputusan yang berlaku sekarang
            editSelect.value = trigger?.dataset.status === 'disetujui' ? 'terima' : 'tolak';
            editAlasan.value = '';
        });
    }

    if (canDelete) {
        const deleteModal = document.getElementById('deleteModal');
        const deleteForm = document.getElementById('delete-form');
        const deleteNama = document.getElementById('delete-nama-text');

        deleteModal.addEventListener('show.bs.modal', function (event) {
            const trigger = event.relatedTarget;

            deleteForm.action = `${baseUrl}/${trigger?.dataset.pengajuanId}`;
            deleteNama.textContent = trigger?.dataset.nama ?? '';
        });
    }

    if (canSeeAll) {
        // pencarian nama: tunggu berhenti mengetik sebentar, lalu muat ulang dengan ?q=
        const searchInput = document.getElementById('pengajuan-search-input');
        let searchTimer = null;

        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimer);
            const keyword = this.value;

            searchTimer = setTimeout(() => {
                const url = new URL(window.location.href);
                url.searchParams.set('q', keyword);
                url.searchParams.delete('page');
                window.location.href = url.toString();
            }, 500);
        });
    }
});
</script>
@endpush