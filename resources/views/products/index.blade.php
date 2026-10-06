@extends('layouts.app')

@section('title', 'Produk Sales')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <style>
        /* Select2 mengikuti tema (dark/light) Bootstrap */
        .select2-container--default .select2-selection--multiple {
            background-color: var(--bs-body-bg);
            border: 1px solid var(--bs-border-color);
            min-height: 38px;
        }
        .select2-container--default.select2-container--focus .select2-selection--multiple {
            border-color: var(--bs-primary);
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: var(--bs-primary);
            border: 0;
            color: #fff;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            color: #fff;
            margin-right: 6px;
        }
        .select2-dropdown {
            background-color: var(--bs-body-bg);
            border-color: var(--bs-border-color);
            color: var(--bs-body-color);
        }
        .select2-container--default .select2-search--inline .select2-search__field {
            color: var(--bs-body-color);
        }
        .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
            background-color: var(--bs-primary);
            color: #fff;
        }
        .select2-container--default .select2-results__option--selected {
            background-color: var(--bs-tertiary-bg);
        }
        .is-invalid + .select2-container .select2-selection--multiple {
            border-color: var(--bs-danger);
        }
        /* nama produk yang bisa dibuka (punya varian) */
        .pv-link { color: #3b82f6; text-decoration: none; }
        .pv-link:hover { color: #2563eb; text-decoration: underline; }
    </style>
@endpush

@section('content')
    @include('master-data.partials.flash')

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h5 class="card-title mb-0">Daftar Produk Sales</h5>
                <div class="text-muted small">Hanya produk di sini yang bisa dipilih di form Tambah Produk, sesuai sales-nya.</div>
            </div>

            <div class="d-flex gap-2">
                <form method="GET" action="{{ url()->current() }}" class="d-flex gap-2">
                    <input type="text" name="q" value="{{ $search }}" class="form-control" placeholder="Cari produk..." autocomplete="off">
                    <button type="submit" class="btn btn-outline-secondary">Cari</button>
                    @if ($search !== '')
                        <a href="{{ url()->current() }}" class="btn btn-outline-secondary">Reset</a>
                    @endif
                </form>

                @can('master-produk-sales.create')
                    <button type="button" class="btn btn-primary text-nowrap"
                            data-bs-toggle="modal" data-bs-target="#formModal"
                            data-url="{{ route('master-data.products.store') }}">
                        + Tambah Produk
                    </button>
                @endcan
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th style="width:60px">No</th>
                            <th>Nama Produk</th>
                            <th>Kategori</th>
                            <th>Dijual oleh</th>
                            <th class="text-end">Stok (pcs)</th>
                            @canany(['master-produk-sales.edit', 'master-produk-sales.delete'])
                                <th class="text-end">Aksi</th>
                            @endcanany
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($products as $product)
                            <tr>
                                <td>{{ $products->firstItem() + $loop->index }}</td>
                                <td class="fw-semibold">
                                    @if ($product->category->supportsVariants())
                                        {{-- kategori dengan varian (Pakaian): klik untuk atur ukuran, warna, dll --}}
                                        <a href="{{ route('master-data.products.show', $product) }}" class="pv-link">{{ $product->name }}</a>
                                        <span class="badge bg-secondary ms-1" title="Jumlah varian">{{ $product->variants_count }} varian</span>
                                    @else
                                        {{ $product->name }}
                                    @endif
                                </td>
                                <td>{{ $product->category->name }}</td>
                                <td>
                                    @foreach ($product->sales as $sale)
                                        <span class="badge bg-primary">{{ $sale->name }}</span>
                                    @endforeach
                                </td>
                                <td class="text-end">{{ number_format($product->stock, 0, ',', '.') }}</td>
                                @canany(['master-produk-sales.edit', 'master-produk-sales.delete'])
                                    <td class="text-end text-nowrap">
                                        @can('master-produk-sales.edit')
                                            <button type="button" class="btn btn-sm btn-outline-warning"
                                                    data-bs-toggle="modal" data-bs-target="#formModal"
                                                    data-url="{{ route('master-data.products.update', $product) }}"
                                                    data-item="{{ json_encode([
                                                        'name'        => $product->name,
                                                        'category_id' => $product->category_id,
                                                        'sales_ids'   => $product->sales->pluck('id'),
                                                    ]) }}">
                                                Edit
                                            </button>
                                        @endcan
                                        @can('master-produk-sales.delete')
                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                    data-bs-toggle="modal" data-bs-target="#deleteModal"
                                                    data-url="{{ route('master-data.products.destroy', $product) }}"
                                                    data-name="{{ $product->name }}">
                                                Hapus
                                            </button>
                                        @endcan
                                    </td>
                                @endcanany
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    @if ($search !== '')
                                        Tidak ada produk yang cocok dengan "{{ $search }}".
                                    @else
                                        Belum ada produk. Klik "Tambah Produk" untuk menambahkan.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $products->links() }}
        </div>
    </div>

    {{-- Modal tambah / edit --}}
    <div class="modal fade" id="formModal" tabindex="-1" aria-hidden="true"
         data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content ajax-form" method="POST" action="#" novalidate>
                @csrf
                <input type="hidden" name="_method" value="POST">

                <div class="modal-header">
                    <h5 class="modal-title" id="formModalTitle">Tambah Produk</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>

                <div class="modal-body">
                    <div class="alert alert-danger d-none form-alert" role="alert"></div>

                    <div class="mb-3">
                        <label for="name" class="form-label">Nama produk</label>
                        <input type="text" class="form-control" id="name" name="name" maxlength="150" autocomplete="off">
                        <div class="text-danger small mt-1" data-error-for="name"></div>
                    </div>

                    <div class="mb-3">
                        <label for="category_id" class="form-label">Kategori</label>
                        <select class="form-select" id="category_id" name="category_id">
                            <option value="">Pilih kategori</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        <div class="text-danger small mt-1" data-error-for="category_id"></div>
                    </div>

                    <div class="mb-0">
                        <label for="sales_ids" class="form-label">Dijual oleh sales</label>
                        <select class="form-select" id="sales_ids" name="sales_ids[]" multiple></select>
                        <div class="form-text" id="salesHint">Pilih kategori dulu.</div>
                        <div class="text-danger small mt-1" data-error-for="sales_ids"></div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" data-loading-text="Menyimpan...">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    @include('master-data.partials.delete-modal', ['label' => 'Produk'])
@endsection

@push('scripts')
    {{-- Hapus baris ini kalau layout sudah memuat Select2 --}}
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

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

        $sales.select2({
            dropdownParent: $modal,
            width: '100%',
            placeholder: 'Pilih sales',
            closeOnSelect: false
        });

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
    });
    </script>
    @endverbatim
@endpush
