@extends('layouts.app')

@section('title', 'Nama Sales')

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
                    <input type="text" id="salesSearch" value="{{ request('q') }}"
                           class="form-control form-control-solid w-250px ps-13"
                           placeholder="Cari nama, telepon, atau kategori" autocomplete="off">
                </div>
            </div>

            @can('master-nama-sales.create')
                <div class="card-toolbar">
                    <button type="button" class="btn btn-primary"
                            data-bs-toggle="modal" data-bs-target="#formModal"
                            data-url="{{ route('master-data.sales.store') }}">
                        <i class="ki-duotone ki-plus fs-2"></i> Tambah Sales
                    </button>
                </div>
            @endcan
        </div>

        <div class="card-body py-4">
            {{-- Bagian ini diganti otomatis saat mencari / pindah halaman --}}
            <div id="salesTable">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-5">
                        <thead>
                            <tr class="text-start text-gray-500 fw-bold fs-7 gs-0">
                                <th class="w-50px">No</th>
                                <th class="min-w-175px">Nama</th>
                                <th class="min-w-150px">No. Telepon</th>
                                <th class="min-w-250px">Kategori Bekerja</th>
                                @canany(['master-nama-sales.edit', 'master-nama-sales.delete'])
                                    <th class="text-end min-w-100px">Aksi</th>
                                @endcanany
                            </tr>
                        </thead>
                        <tbody class="text-gray-700 fw-semibold">
                            @forelse ($sales as $sale)
                                <tr>
                                    <td>{{ $sales->firstItem() + $loop->index }}</td>
                                    <td class="text-gray-800 fw-bold">{{ $sale->name }}</td>
                                    <td>{{ $sale->phone ?: '-' }}</td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-2">
                                            @forelse ($sale->categories as $category)
                                                <span class="badge badge-light-primary fs-7">{{ $category->name }}</span>
                                            @empty
                                                <span class="text-muted">-</span>
                                            @endforelse
                                        </div>
                                    </td>
                                    @canany(['master-nama-sales.edit', 'master-nama-sales.delete'])
                                        <td class="text-end text-nowrap">
                                            @can('master-nama-sales.edit')
                                                <button type="button" class="btn btn-icon btn-sm btn-light-warning me-1"
                                                        title="Edit" aria-label="Edit {{ $sale->name }}"
                                                        data-bs-toggle="modal" data-bs-target="#formModal"
                                                        data-url="{{ route('master-data.sales.update', $sale) }}"
                                                        data-item="{{ json_encode([
                                                            'name'         => $sale->name,
                                                            'phone'        => $sale->phone,
                                                            'address'      => $sale->address,
                                                            'category_ids' => $sale->categories->pluck('id'),
                                                        ]) }}">
                                                    <i class="ki-duotone ki-pencil fs-2">
                                                        <span class="path1"></span><span class="path2"></span>
                                                    </i>
                                                </button>
                                            @endcan
                                            @can('master-nama-sales.delete')
                                                <button type="button" class="btn btn-icon btn-sm btn-light-danger"
                                                        title="Hapus" aria-label="Hapus {{ $sale->name }}"
                                                        data-bs-toggle="modal" data-bs-target="#salesDeleteModal"
                                                        data-url="{{ route('master-data.sales.destroy', $sale) }}"
                                                        data-name="{{ $sale->name }}">
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
                                        <i class="ki-duotone ki-profile-user fs-3x text-gray-400 mb-3">
                                            <span class="path1"></span><span class="path2"></span>
                                            <span class="path3"></span><span class="path4"></span>
                                        </i>
                                        <div class="text-gray-800 fw-bold fs-5">
                                            {{ filled(request('q')) ? 'Sales tidak ditemukan' : 'Belum ada sales' }}
                                        </div>
                                        @unless (filled(request('q')))
                                            <div class="text-muted fs-7 mt-1">Klik "Tambah Sales" untuk menambahkan.</div>
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
                        Menampilkan {{ $sales->firstItem() ?? 0 }}-{{ $sales->lastItem() ?? 0 }} dari {{ $sales->total() }} data
                    </div>
                    {{ $sales->links('vendor.pagination.metronic') }}
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
                    <h3 class="modal-title fw-bold" id="formModalTitle">Tambah Sales</h3>
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
                        <label for="name" class="required fw-semibold fs-6 mb-2">Nama sales</label>
                        <input type="text" class="form-control form-control-solid" id="name" name="name"
                               maxlength="100" autocomplete="off">
                        <div class="text-danger fs-7 mt-1" data-error-for="name"></div>
                    </div>

                    <div class="fv-row mb-7">
                        <label for="phone" class="fw-semibold fs-6 mb-2">No. telepon</label>
                        <input type="text" class="form-control form-control-solid" id="phone" name="phone"
                               maxlength="20" autocomplete="off">
                        <div class="text-danger fs-7 mt-1" data-error-for="phone"></div>
                    </div>

                    <div class="fv-row mb-7">
                        <label for="category_ids" class="fw-semibold fs-6 mb-2">Kategori bekerja</label>
                        <select class="form-select form-select-solid" id="category_ids" name="category_ids[]" multiple
                                data-control="select2"
                                data-dropdown-parent="#formModal"
                                data-placeholder="Pilih kategori"
                                data-close-on-select="false">
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        <div class="text-danger fs-7 mt-1" data-error-for="category_ids"></div>
                    </div>

                    <div class="fv-row">
                        <label for="address" class="fw-semibold fs-6 mb-2">Alamat</label>
                        <textarea class="form-control form-control-solid" id="address" name="address"
                                  rows="3" maxlength="500"></textarea>
                        <div class="text-danger fs-7 mt-1" data-error-for="address"></div>
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
    <div class="modal fade" id="salesDeleteModal" tabindex="-1" aria-hidden="true">
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

                    <h3 class="fw-bold text-gray-900 mb-2">Hapus Sales</h3>
                    <div class="text-gray-600 fs-6 mb-2">
                        Hapus sales <span class="fw-bold text-gray-900" id="deleteSalesName"></span>?
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

    <script>
    $(function () {
        const $modal  = $('#formModal');
        const $form   = $modal.find('form');
        const $select = $('#category_ids');

        // Isi form saat modal dibuka (Tambah = kosong, Edit = terisi)
        $modal.on('show.bs.modal', function (e) {
            const $btn = $(e.relatedTarget);
            const item = $btn.data('item');   // jQuery otomatis parse JSON

            $form.attr('action', $btn.data('url'));
            $form.find('[name="_method"]').val(item ? 'PUT' : 'POST');
            $('#formModalTitle').text(item ? 'Edit Sales' : 'Tambah Sales');

            $('#name').val(item ? item.name : '');
            $('#phone').val(item ? item.phone : '');
            $('#address').val(item ? item.address : '');
            $select.val(item ? item.category_ids : []).trigger('change');
        });

        // Modal hapus
        $('#salesDeleteModal').on('show.bs.modal', function (e) {
            const $btn = $(e.relatedTarget);
            $(this).find('form').attr('action', $btn.data('url'));
            $('#deleteSalesName').text($btn.data('name'));
        });

        // ---- Cari otomatis + paginasi tanpa reload penuh ----
        const search = document.getElementById('salesSearch');
        let ctrl = null, seq = 0, timer = null;

        async function load(url) {
            if (ctrl) ctrl.abort();
            ctrl = new AbortController();
            const mine = ++seq;

            document.getElementById('salesTable').classList.add('opacity-50');

            try {
                const res = await fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
                    signal: ctrl.signal,
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);

                const html = await res.text();
                if (mine !== seq) return;

                const doc   = new DOMParser().parseFromString(html, 'text/html');
                const fresh = doc.getElementById('salesTable');
                if (!fresh) throw new Error('salesTable tidak ditemukan');

                document.getElementById('salesTable').replaceWith(fresh);
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
            const link = e.target.closest('#salesTable .pagination a');
            if (!link) return;
            e.preventDefault();
            load(link.href);
        });
    });
    </script>
@endpush