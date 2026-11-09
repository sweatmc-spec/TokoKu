@extends('layouts.app')

@section('title', $meta['title'])

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
                    <input type="text" id="optionSearch" class="form-control form-control-solid w-250px ps-13"
                           placeholder="Cari {{ $meta['lower'] }}" autocomplete="off">
                </div>
            </div>

            @can($meta['slug'] . '.create')
                <div class="card-toolbar">
                    <button type="button" class="btn btn-primary"
                            data-bs-toggle="modal" data-bs-target="#formModal"
                            data-url="{{ route('master-data.' . $type . '.store') }}">
                        <i class="ki-duotone ki-plus fs-2"></i> Tambah {{ $meta['title'] }}
                    </button>
                </div>
            @endcan
        </div>

        <div class="card-body py-4">
            <div class="text-muted fs-7 mb-6">
                Pilihan {{ $meta['lower'] }} untuk varian pakaian di halaman produk (Master Data &gt; Produk Sales).
            </div>

            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5" id="optionTable">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 gs-0">
                            <th class="w-50px">No</th>
                            <th class="min-w-175px">Nama</th>
                            @if ($meta['hex'])
                                <th class="min-w-125px">Kode warna</th>
                            @endif
                            <th class="text-end min-w-75px">Urutan</th>
                            <th class="text-end min-w-100px">Dipakai</th>
                            @canany([$meta['slug'] . '.edit', $meta['slug'] . '.delete'])
                                <th class="text-end min-w-100px">Aksi</th>
                            @endcanany
                        </tr>
                    </thead>
                    <tbody class="text-gray-700 fw-semibold">
                        @forelse ($options as $option)
                            @php $used = (int) ($usage[mb_strtolower($option->name)] ?? 0); @endphp
                            <tr class="option-row">
                                <td>{{ $loop->iteration }}</td>

                                <td class="text-gray-800 fw-bold">
                                    <div class="d-flex align-items-center">
                                        @if ($meta['hex'])
                                            <span class="d-inline-block rounded-circle border w-20px h-20px me-3 flex-shrink-0"
                                                  style="background: {{ $option->hex ?: 'transparent' }}"></span>
                                        @endif
                                        <span class="option-name">{{ $option->name }}</span>
                                    </div>
                                </td>

                                @if ($meta['hex'])
                                    <td>
                                        @if ($option->hex)
                                            <span class="badge badge-light fs-7 option-hex">{{ $option->hex }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                @endif

                                <td class="text-end">{{ $option->sort_order }}</td>

                                <td class="text-end">
                                    @if ($used > 0)
                                        <span class="badge badge-light-primary fs-7">{{ $used }} varian</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>

                                @canany([$meta['slug'] . '.edit', $meta['slug'] . '.delete'])
                                    <td class="text-end text-nowrap">
                                        @can($meta['slug'] . '.edit')
                                            <button type="button" class="btn btn-icon btn-sm btn-light-warning me-1"
                                                    title="Edit" aria-label="Edit {{ $option->name }}"
                                                    data-bs-toggle="modal" data-bs-target="#formModal"
                                                    data-url="{{ route('master-data.' . $type . '.update', $option) }}"
                                                    data-item="{{ json_encode([
                                                        'name'       => $option->name,
                                                        'hex'        => $option->hex,
                                                        'sort_order' => $option->sort_order,
                                                    ]) }}">
                                                <i class="ki-duotone ki-pencil fs-2">
                                                    <span class="path1"></span><span class="path2"></span>
                                                </i>
                                            </button>
                                        @endcan
                                        @can($meta['slug'] . '.delete')
                                            <button type="button" class="btn btn-icon btn-sm btn-light-danger"
                                                    title="Hapus" aria-label="Hapus {{ $option->name }}"
                                                    data-bs-toggle="modal" data-bs-target="#optionDeleteModal"
                                                    data-url="{{ route('master-data.' . $type . '.destroy', $option) }}"
                                                    data-name="{{ $option->name }}">
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
                                    <div class="text-gray-800 fw-bold fs-5">Belum ada {{ $meta['lower'] }}</div>
                                    <div class="text-muted fs-7 mt-1">
                                        Klik "Tambah {{ $meta['title'] }}" atau jalankan
                                        <code>php artisan db:seed --class=VariantOptionSeeder</code>.
                                    </div>
                                </td>
                            </tr>
                        @endforelse

                        {{-- Muncul saat pencarian tidak menemukan hasil --}}
                        <tr id="optionNoResult" class="d-none">
                            <td colspan="6" class="text-center text-muted py-10">{{ $meta['title'] }} tidak ditemukan.</td>
                        </tr>
                    </tbody>
                </table>
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
                    <h3 class="modal-title fw-bold" id="formModalTitle">Tambah {{ $meta['title'] }}</h3>
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
                        <label for="name" class="required fw-semibold fs-6 mb-2">Nama {{ $meta['lower'] }}</label>
                        <input type="text" class="form-control form-control-solid" id="name" name="name"
                               maxlength="50" autocomplete="off">
                        <div class="text-danger fs-7 mt-1" data-error-for="name"></div>
                    </div>

                    @if ($meta['hex'])
                        <div class="fv-row mb-7">
                            <label for="hex" class="fw-semibold fs-6 mb-2">Kode warna</label>
                            <div class="d-flex align-items-center gap-3">
                                <input type="color" class="form-control form-control-solid form-control-color w-65px"
                                       id="hex" name="hex" value="#6b7280">
                                <span id="hexText" class="badge badge-light fs-7">#6b7280</span>
                            </div>
                            <div class="form-text">Dipakai untuk bulatan warna di halaman varian.</div>
                            <div class="text-danger fs-7 mt-1" data-error-for="hex"></div>
                        </div>
                    @endif

                    <div class="fv-row">
                        <label for="sort_order" class="fw-semibold fs-6 mb-2">Urutan</label>
                        <input type="text" inputmode="numeric" class="form-control form-control-solid" id="sort_order"
                               name="sort_order" maxlength="4" autocomplete="off"
                               placeholder="Kosongkan untuk ditaruh paling akhir">
                        <div class="form-text">Angka kecil tampil lebih dulu (mis. ukuran S = 1, M = 2, L = 3).</div>
                        <div class="text-danger fs-7 mt-1" data-error-for="sort_order"></div>
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
    <div class="modal fade" id="optionDeleteModal" tabindex="-1" aria-hidden="true">
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

                    <h3 class="fw-bold text-gray-900 mb-2">Hapus {{ $meta['title'] }}</h3>
                    <div class="text-gray-600 fs-6 mb-2">
                        Hapus {{ $meta['lower'] }} <span class="fw-bold text-gray-900" id="deleteOptionName"></span>?
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
        const OPTION_TITLE = @json($meta['title']);
        const HAS_HEX = @json($meta['hex']);
    </script>

    @verbatim
    <script>
    $(function () {
        const $modal = $('#formModal');
        const $form  = $modal.find('form');
        const DEFAULT_HEX = '#6b7280';

        $('#sort_order').on('input', function () { this.value = this.value.replace(/\D/g, ''); });

        if (HAS_HEX) {
            $('#hex').on('input change', function () { $('#hexText').text(this.value); });
        }

        $modal.on('show.bs.modal', function (e) {
            const $btn = $(e.relatedTarget);
            const item = $btn.data('item');

            $form.attr('action', $btn.data('url'));
            $form.find('[name="_method"]').val(item ? 'PUT' : 'POST');
            $('#formModalTitle').text((item ? 'Edit ' : 'Tambah ') + OPTION_TITLE);

            $('#name').val(item ? item.name : '');
            $('#sort_order').val(item && item.sort_order !== null ? item.sort_order : '');

            if (HAS_HEX) {
                const hex = (item && item.hex) ? item.hex : DEFAULT_HEX;
                $('#hex').val(hex);
                $('#hexText').text(hex);
            }
        });

        // Modal hapus
        $('#optionDeleteModal').on('show.bs.modal', function (e) {
            const $btn = $(e.relatedTarget);
            $(this).find('form').attr('action', $btn.data('url'));
            $('#deleteOptionName').text($btn.data('name'));
        });

        // Pencarian cepat di tabel (nama + kode warna)
        $('#optionSearch').on('input', function () {
            const q = this.value.trim().toLowerCase();
            let found = 0;

            $('#optionTable .option-row').each(function () {
                const text  = ($(this).find('.option-name').text() + ' ' + $(this).find('.option-hex').text()).toLowerCase();
                const match = text.indexOf(q) !== -1;
                $(this).toggleClass('d-none', !match);
                if (match) found++;
            });

            $('#optionNoResult').toggleClass('d-none', found > 0 || $('#optionTable .option-row').length === 0);
        });
    });
    </script>
    @endverbatim
@endpush