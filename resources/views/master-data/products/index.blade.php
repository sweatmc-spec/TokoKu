@extends('layouts.app')

@section('title', 'Produk Sales')

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
                    <input type="text" id="productSearch" value="{{ $search }}"
                           class="form-control form-control-solid w-250px ps-13"
                           placeholder="Cari produk" autocomplete="off">
                </div>
            </div>

            @can('master-produk-sales.create')
                <div class="card-toolbar">
                    <button type="button" class="btn btn-primary"
                            data-bs-toggle="modal" data-bs-target="#formModal"
                            data-url="{{ route('master-data.products.store') }}">
                        <i class="ki-duotone ki-plus fs-2"></i> Tambah Produk
                    </button>
                </div>
            @endcan
        </div>

        <div class="card-body py-4">
            <div class="text-muted fs-7 mb-6">
                Hanya produk di sini yang bisa dipilih di form Tambah Produk, sesuai sales-nya.
            </div>

            {{-- Bagian ini diganti otomatis saat mencari / pindah halaman --}}
            <div id="productTable">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-5">
                        <thead>
                            <tr class="text-start text-gray-500 fw-bold fs-7 gs-0">
                                <th class="w-50px">No</th>
                                <th class="min-w-200px">Nama Produk</th>
                                <th class="min-w-125px">Kategori</th>
                                <th class="min-w-200px">Dijual oleh</th>
                                <th class="text-end min-w-100px">Stok (pcs)</th>
                                @canany(['master-produk-sales.edit', 'master-produk-sales.delete'])
                                    <th class="text-end min-w-100px">Aksi</th>
                                @endcanany
                            </tr>
                        </thead>
                        <tbody class="text-gray-700 fw-semibold">
                            @forelse ($products as $product)
                                <tr>
                                    <td>{{ $products->firstItem() + $loop->index }}</td>
                                    <td>
                                        @if ($product->category->supportsVariants())
                                            {{-- kategori dengan varian (Pakaian): klik untuk atur ukuran, warna, dll --}}
                                            <a href="{{ route('master-data.products.show', $product) }}"
                                               class="text-primary fw-bold">{{ $product->name }}</a>
                                            <span class="badge badge-light fs-8 ms-2" title="Jumlah varian">{{ $product->variants_count }} varian</span>
                                        @else
                                            <span class="text-gray-800 fw-bold">{{ $product->name }}</span>
                                        @endif
                                    </td>
                                    <td><span class="badge badge-light fs-7">{{ $product->category->name }}</span></td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-2">
                                            @forelse ($product->sales as $sale)
                                                <span class="badge badge-light-primary fs-7">{{ $sale->name }}</span>
                                            @empty
                                                <span class="text-muted">-</span>
                                            @endforelse
                                        </div>
                                    </td>
                                    <td class="text-end {{ $product->stock > 0 ? 'text-gray-900 fw-bold' : 'text-muted' }}">
                                        {{ number_format($product->stock, 0, ',', '.') }}
                                    </td>
                                    @canany(['master-produk-sales.edit', 'master-produk-sales.delete'])
                                        <td class="text-end text-nowrap">
                                            @can('master-produk-sales.edit')
                                                <button type="button" class="btn btn-icon btn-sm btn-light-warning me-1"
                                                        title="Edit" aria-label="Edit {{ $product->name }}"
                                                        data-bs-toggle="modal" data-bs-target="#formModal"
                                                        data-url="{{ route('master-data.products.update', $product) }}"
                                                        data-item="{{ json_encode([
                                                            'name'        => $product->name,
                                                            'category_id' => $product->category_id,
                                                            'sales_ids'   => $product->sales->pluck('id'),
                                                        ]) }}">
                                                    <i class="ki-duotone ki-pencil fs-2">
                                                        <span class="path1"></span><span class="path2"></span>
                                                    </i>
                                                </button>
                                            @endcan
                                            @can('master-produk-sales.delete')
                                                <button type="button" class="btn btn-icon btn-sm btn-light-danger"
                                                        title="Hapus" aria-label="Hapus {{ $product->name }}"
                                                        data-bs-toggle="modal" data-bs-target="#productDeleteModal"
                                                        data-url="{{ route('master-data.products.destroy', $product) }}"
                                                        data-name="{{ $product->name }}">
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
                                    <td colspan="6" class="text-center py-15">
                                        <i class="ki-duotone ki-abstract-26 fs-3x text-gray-400 mb-3">
                                            <span class="path1"></span><span class="path2"></span>
                                        </i>
                                        <div class="text-gray-800 fw-bold fs-5">
                                            {{ $search !== '' ? 'Produk tidak ditemukan' : 'Belum ada produk' }}
                                        </div>
                                        <div class="text-muted fs-7 mt-1">
                                            @if ($search !== '')
                                                Tidak ada produk yang cocok dengan "{{ $search }}".
                                            @else
                                                Klik "Tambah Produk" untuk menambahkan.
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Paginasi bernomor (view Metronic, bukan bawaan Tailwind) --}}
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mt-4">
                    <div class="text-muted fs-7">
                        Menampilkan {{ $products->firstItem() ?? 0 }}-{{ $products->lastItem() ?? 0 }} dari {{ $products->total() }} data
                    </div>
                    {{ $products->links('vendor.pagination.metronic') }}
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
                    <h3 class="modal-title fw-bold" id="formModalTitle">Tambah Produk</h3>
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
                        <label for="name" class="required fw-semibold fs-6 mb-2">Nama produk</label>
                        <input type="text" class="form-control form-control-solid" id="name" name="name"
                               maxlength="150" autocomplete="off">
                        <div class="text-danger fs-7 mt-1" data-error-for="name"></div>
                    </div>

                    <div class="fv-row mb-7">
                        <label for="category_id" class="required fw-semibold fs-6 mb-2">Kategori</label>
                        <select class="form-select form-select-solid" id="category_id" name="category_id">
                            <option value="">Pilih kategori</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        <div class="text-danger fs-7 mt-1" data-error-for="category_id"></div>
                    </div>

                    <div class="fv-row">
                        <label for="sales_ids" class="fw-semibold fs-6 mb-2">Dijual oleh sales</label>
                        <select class="form-select form-select-solid" id="sales_ids" name="sales_ids[]" multiple
                                data-control="select2"
                                data-dropdown-parent="#formModal"
                                data-placeholder="Pilih sales"
                                data-close-on-select="false"></select>
                        <div class="form-text" id="salesHint">Pilih kategori dulu.</div>
                        <div class="text-danger fs-7 mt-1" data-error-for="sales_ids"></div>
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
    <div class="modal fade" id="productDeleteModal" tabindex="-1" aria-hidden="true">
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

                    <h3 class="fw-bold text-gray-900 mb-2">Hapus Produk</h3>
                    <div class="text-gray-600 fs-6 mb-2">
                        Hapus produk <span class="fw-bold text-gray-900" id="deleteProductName"></span>?
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
        const SALES_LIST = @json($salesList);
    </script>

    @verbatim
    <script>
    $(function () {
        const $modal    = $('#formModal');
        const $form     = $modal.find('form');
        const $category = $('#category_id');
        const $sales    = $('#sales_ids');

        // Pilihan sales dibatasi ke sales yang bekerja di kategori terpilih
        function rebuildSales(selected) {
            const catId = $category.val();
            const keep = (selected || []).map(String);

            $sales.empty();
            SALES_LIST
                .filter((s) => catId && s.category_ids.map(String).indexOf(String(catId)) !== -1)
                .forEach((s) => $sales.append(new Option(s.name, s.id)));

            $sales.val(keep).trigger('change');

            $('#salesHint').text(!catId
                ? 'Pilih kategori dulu.'
                : ($sales.find('option').length ? '' : 'Belum ada sales di kategori ini. Atur di Master Data > Nama Sales.'));
        }

        $category.on('change', function () { rebuildSales($sales.val()); });

        $modal.on('show.bs.modal', function (e) {
            const $btn = $(e.relatedTarget);
            const item = $btn.data('item');

            $form.attr('action', $btn.data('url'));
            $form.find('[name="_method"]').val(item ? 'PUT' : 'POST');
            $('#formModalTitle').text(item ? 'Edit Produk' : 'Tambah Produk');

            $('#name').val(item ? item.name : '');
            $category.val(item ? String(item.category_id) : '');
            rebuildSales(item ? item.sales_ids : []);
        });

        // Modal hapus
        $('#productDeleteModal').on('show.bs.modal', function (e) {
            const $btn = $(e.relatedTarget);
            $(this).find('form').attr('action', $btn.data('url'));
            $('#deleteProductName').text($btn.data('name'));
        });

        // ---- Cari otomatis + paginasi tanpa reload penuh ----
        const search = document.getElementById('productSearch');
        let ctrl = null, seq = 0, timer = null;

        function buildUrl(page) {
            const q = search.value.trim();
            const p = new URLSearchParams();
            if (q) p.set('q', q);
            if (page && page > 1) p.set('page', page);
            const qs = p.toString();
            return window.location.pathname + (qs ? '?' + qs : '');
        }

        async function load(url) {
            if (ctrl) ctrl.abort();
            ctrl = new AbortController();
            const mine = ++seq;

            document.getElementById('productTable').classList.add('opacity-50');

            try {
                const res = await fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
                    signal: ctrl.signal,
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);

                const html = await res.text();
                if (mine !== seq) return;

                const doc   = new DOMParser().parseFromString(html, 'text/html');
                const fresh = doc.getElementById('productTable');
                if (!fresh) throw new Error('productTable tidak ditemukan');

                document.getElementById('productTable').replaceWith(fresh);
                history.replaceState(null, '', url);
            } catch (err) {
                if (err.name === 'AbortError') return;
                window.location.href = url; // fallback: reload biasa
            }
        }

        search.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(function () { load(buildUrl(1)); }, 400);
        });

        // nomor halaman diambil dari link, kata kunci diambil dari kolom pencarian saat ini
        document.addEventListener('click', function (e) {
            const link = e.target.closest('#productTable .pagination a');
            if (!link) return;
            e.preventDefault();
            const page = parseInt(new URL(link.href, window.location.origin).searchParams.get('page'), 10) || 1;
            load(buildUrl(page));
        });
    });
    </script>
    @endverbatim
@endpush