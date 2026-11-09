@extends('layouts.app')

@section('title', 'Profit Harga')

@section('content')
    @include('master-data.partials.flash')

    <div class="card">
        {{-- Header: pencarian + tombol tambah --}}
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <div class="d-flex align-items-center position-relative my-1">
                    <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                    <input type="text" id="profitSearch" value="{{ request('q') }}"
                           class="form-control form-control-solid w-250px ps-13"
                           placeholder="Cari harga atau code" autocomplete="off">
                </div>
            </div>

            @can('master-profit-harga.create')
                <div class="card-toolbar">
                    <button type="button" class="btn btn-primary"
                            data-bs-toggle="modal" data-bs-target="#formModal"
                            data-url="{{ route('master-data.profit-harga.store') }}">
                        <i class="ki-duotone ki-plus fs-2"></i> Tambah Profit Harga
                    </button>
                </div>
            @endcan
        </div>

        <div class="card-body py-4">
            <div class="text-muted fs-7 mb-6">
                Harga jual per pcs. Dipilih saat Tambah Produk dan tampil sebagai Harga Jual di halaman Stok.
                Harga beli dari sales tidak terpengaruh.
            </div>

            {{-- Bagian ini diganti otomatis saat mencari / pindah halaman --}}
            <div id="profitTable">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-5">
                        <thead>
                            <tr class="text-start text-gray-500 fw-bold fs-7 gs-0">
                                <th class="w-50px">No</th>
                                <th class="min-w-175px">Harga (per pcs)</th>
                                <th class="min-w-125px">Code</th>
                                <th class="text-end min-w-100px">Dipakai</th>
                                @canany(['master-profit-harga.edit', 'master-profit-harga.delete'])
                                    <th class="text-end min-w-100px">Aksi</th>
                                @endcanany
                            </tr>
                        </thead>
                        <tbody class="text-gray-700 fw-semibold">
                            @forelse ($items as $item)
                                <tr>
                                    <td>{{ $items->firstItem() + $loop->index }}</td>
                                    <td class="text-gray-800 fw-bold">{{ $item->harga_formatted }}</td>
                                    <td>
                                        @if ($item->code)
                                            <span class="badge badge-light fs-7">{{ $item->code }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if ($item->products_count > 0)
                                            <span class="badge badge-light-primary fs-7">{{ $item->products_count }} stok</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    @canany(['master-profit-harga.edit', 'master-profit-harga.delete'])
                                        <td class="text-end text-nowrap">
                                            @can('master-profit-harga.edit')
                                                <button type="button" class="btn btn-icon btn-sm btn-light-warning me-1"
                                                        title="Edit" aria-label="Edit profit harga"
                                                        data-bs-toggle="modal" data-bs-target="#formModal"
                                                        data-url="{{ route('master-data.profit-harga.update', $item) }}"
                                                        data-item="{{ json_encode(['harga' => $item->harga, 'code' => $item->code]) }}">
                                                    <i class="ki-duotone ki-pencil fs-2">
                                                        <span class="path1"></span><span class="path2"></span>
                                                    </i>
                                                </button>
                                            @endcan
                                            @can('master-profit-harga.delete')
                                                <button type="button" class="btn btn-icon btn-sm btn-light-danger"
                                                        title="Hapus" aria-label="Hapus profit harga"
                                                        data-bs-toggle="modal" data-bs-target="#profitDeleteModal"
                                                        data-url="{{ route('master-data.profit-harga.destroy', $item) }}"
                                                        data-name="{{ $item->harga_formatted }}{{ $item->code ? ' (' . $item->code . ')' : '' }}">
                                                    <i class="ki-duotone ki-trash fs-2">
                                                        <span class="path1"></span><span class="path2"></span>
                                                        <span class="path3"></span><span class="path4"></span>
                                                        <span class="path5"></span>
                                                    </i>
                                                </button>
                                            @endcan
                                        </td>
                                    @endcanany
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-15">
                                        <i class="ki-duotone ki-price-tag fs-3x text-gray-400 mb-3">
                                            <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                                        </i>
                                        <div class="text-gray-800 fw-bold fs-5">
                                            {{ filled(request('q')) ? 'Profit Harga tidak ditemukan' : 'Belum ada Profit Harga' }}
                                        </div>
                                        @unless (filled(request('q')))
                                            <div class="text-muted fs-7 mt-1">Klik "Tambah Profit Harga" untuk menambahkan.</div>
                                        @endunless
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Paginasi bernomor (view Metronic, bukan bawaan Tailwind) --}}
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mt-4">
                    <div class="text-muted fs-7">
                        Menampilkan {{ $items->firstItem() ?? 0 }}-{{ $items->lastItem() ?? 0 }} dari {{ $items->total() }} data
                    </div>
                    {{ $items->links('vendor.pagination.metronic') }}
                </div>
            </div>
        </div>
    </div>

    {{-- Modal tambah / edit --}}
    <div class="modal fade" id="formModal" tabindex="-1" aria-hidden="true"
         data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered mw-550px">
            <form class="modal-content ajax-form" method="POST" action="#" novalidate>
                @csrf
                <input type="hidden" name="_method" value="POST">

                <div class="modal-header">
                    <h3 class="modal-title fw-bold" id="formModalTitle">Tambah Profit Harga</h3>
                    <button type="button" class="btn btn-icon btn-sm btn-active-icon-primary"
                            data-bs-dismiss="modal" aria-label="Tutup">
                        <i class="ki-duotone ki-cross fs-1">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </button>
                </div>

                <div class="modal-body px-lg-10 py-8">
                    <div class="alert alert-danger d-none form-alert" role="alert"></div>

                    <div class="fv-row mb-7">
                        <label for="harga" class="required fw-semibold fs-6 mb-2">Harga (per pcs)</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" inputmode="numeric" class="form-control form-control-solid"
                                   id="harga" name="harga" maxlength="15" autocomplete="off" placeholder="0">
                        </div>
                        <div class="form-text" id="hargaHint">Harga jual yang akan dipakai ke depannya.</div>
                        <div class="text-danger fs-7 mt-1" data-error-for="harga"></div>
                    </div>

                    <div class="fv-row">
                        <label for="code" class="fw-semibold fs-6 mb-2">Code</label>
                        <input type="text" class="form-control form-control-solid" id="code" name="code"
                               maxlength="50" autocomplete="off" placeholder="Opsional, mis. XQC">
                        <div class="text-danger fs-7 mt-1" data-error-for="code"></div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" data-loading-text="Menyimpan...">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal hapus --}}
    <div class="modal fade" id="profitDeleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-450px">
            <form class="modal-content ajax-form" method="POST" action="#" novalidate>
                @csrf
                <input type="hidden" name="_method" value="DELETE">

                <div class="modal-body text-center px-10 py-10">
                    <div class="alert alert-danger d-none form-alert text-start" role="alert"></div>

                    <i class="ki-duotone ki-trash fs-5x text-danger mb-5">
                        <span class="path1"></span><span class="path2"></span>
                        <span class="path3"></span><span class="path4"></span>
                        <span class="path5"></span>
                    </i>

                    <h3 class="fw-bold text-gray-900 mb-2">Hapus Profit Harga</h3>
                    <div class="text-gray-600 fs-6 mb-2">
                        Hapus <span class="fw-bold text-gray-900" id="deleteProfitName"></span>?
                    </div>
                    <div class="text-muted fs-7 mb-8">Tindakan ini tidak bisa dibatalkan.</div>

                    <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger" data-loading-text="Menghapus...">Ya, Hapus</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    @include('master-data.partials.scripts')

    @verbatim
    <script>
    $(function () {
        const $modal = $('#formModal');
        const $form  = $modal.find('form');
        const nf = new Intl.NumberFormat('id-ID');

        // Input harga: hanya angka, ribuan diberi titik. "120.000,00" yang ditempel diambil angka sebelum koma.
        $('#harga').on('input', function () {
            const digits = this.value.split(',')[0].replace(/\D/g, '');
            this.value = digits ? nf.format(parseInt(digits, 10)) : '';
        });

        $modal.on('show.bs.modal', function (e) {
            const $btn = $(e.relatedTarget);
            const item = $btn.data('item');

            $form.attr('action', $btn.data('url'));
            $form.find('[name="_method"]').val(item ? 'PUT' : 'POST');
            $('#formModalTitle').text(item ? 'Edit Profit Harga' : 'Tambah Profit Harga');

            $('#harga').val(item ? nf.format(item.harga) : '');
            $('#code').val(item && item.code ? item.code : '');
            $('#hargaHint').text(item
                ? 'Mengubah harga ikut mengubah Harga Jual semua stok yang memakai Profit Harga ini.'
                : 'Harga jual yang akan dipakai ke depannya.');
        });

        // Modal hapus
        $('#profitDeleteModal').on('show.bs.modal', function (e) {
            const $btn = $(e.relatedTarget);
            $(this).find('form').attr('action', $btn.data('url'));
            $('#deleteProfitName').text($btn.data('name'));
        });

        // ---- Cari otomatis + paginasi tanpa reload penuh ----
        const search = document.getElementById('profitSearch');
        let ctrl = null, seq = 0, timer = null;

        async function load(url) {
            if (ctrl) ctrl.abort();
            ctrl = new AbortController();
            const mine = ++seq;

            document.getElementById('profitTable').classList.add('opacity-50');

            try {
                const res = await fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
                    signal: ctrl.signal,
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);

                const html = await res.text();
                if (mine !== seq) return;

                const doc   = new DOMParser().parseFromString(html, 'text/html');
                const fresh = doc.getElementById('profitTable');
                if (!fresh) throw new Error('profitTable tidak ditemukan');

                document.getElementById('profitTable').replaceWith(fresh);
                history.replaceState(null, '', url);
            } catch (err) {
                if (err.name === 'AbortError') return;
                window.location.href = url; // fallback: reload biasa
            }
        }

        search.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(function () {
                const q = search.value.trim();
                load(window.location.pathname + (q ? '?q=' + encodeURIComponent(q) : ''));
            }, 400);
        });

        document.addEventListener('click', function (e) {
            const link = e.target.closest('#profitTable .pagination a');
            if (!link) return;
            e.preventDefault();
            load(link.href);
        });
    });
    </script>
    @endverbatim
@endpush