@extends('layouts.app')

@section('title', 'Unit')

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
                    <input type="text" id="unitSearch" class="form-control form-control-solid w-250px ps-13"
                           placeholder="Cari unit atau kategori" autocomplete="off">
                </div>
            </div>

            @can('master-unit.create')
                <div class="card-toolbar">
                    <button type="button" class="btn btn-primary"
                            data-bs-toggle="modal" data-bs-target="#formModal"
                            data-url="{{ route('master-data.units.store') }}">
                        <i class="ki-duotone ki-plus fs-2"></i> Tambah Unit
                    </button>
                </div>
            @endcan
        </div>

        <div class="card-body py-4">
            {{-- Info singkat --}}
            <div class="text-muted fs-7 mb-6">
                Dipakai di form Tambah Produk. Stok selalu disimpan dalam pcs (2 lusin = 24 pcs).
            </div>

            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5" id="unitTable">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 gs-0">
                            <th class="w-50px">No</th>
                            <th class="min-w-175px">Nama</th>
                            <th class="min-w-150px">Isi per unit</th>
                            <th class="min-w-250px">Berlaku untuk kategori</th>
                            @canany(['master-unit.edit', 'master-unit.delete'])
                                <th class="text-end min-w-100px">Aksi</th>
                            @endcanany
                        </tr>
                    </thead>
                    <tbody class="text-gray-700 fw-semibold">
                        @forelse ($units as $unit)
                            <tr class="unit-row">
                                <td>{{ $loop->iteration }}</td>

                                <td class="text-gray-800 fw-bold unit-name">{{ $unit->name }}</td>

                                <td>
                                    @if ($unit->is_manual)
                                        <span class="badge badge-light-warning fs-7">Manual</span>
                                        <div class="text-muted fs-8 mt-1">Diisi saat input pembelian</div>
                                    @else
                                        <span class="badge badge-light-success fs-7">
                                            {{ number_format($unit->pcs_per_unit, 0, ',', '.') }} pcs
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    <div class="d-flex flex-wrap gap-2 unit-categories">
                                        @forelse ($unit->categories as $category)
                                            <span class="badge badge-light-primary fs-7">{{ $category->name }}</span>
                                        @empty
                                            <span class="text-muted fs-7">Belum dipilih</span>
                                        @endforelse
                                    </div>
                                </td>

                                @canany(['master-unit.edit', 'master-unit.delete'])
                                    <td class="text-end text-nowrap">
                                        @can('master-unit.edit')
                                            <button type="button" class="btn btn-icon btn-sm btn-light-warning me-1"
                                                    title="Edit" aria-label="Edit {{ $unit->name }}"
                                                    data-bs-toggle="modal" data-bs-target="#formModal"
                                                    data-url="{{ route('master-data.units.update', $unit) }}"
                                                    data-item="{{ json_encode([
                                                        'name'         => $unit->name,
                                                        'pcs_per_unit' => $unit->pcs_per_unit,
                                                        'is_manual'    => $unit->is_manual,
                                                        'category_ids' => $unit->categories->pluck('id'),
                                                    ]) }}">
                                                <i class="ki-duotone ki-pencil fs-2">
                                                    <span class="path1"></span><span class="path2"></span>
                                                </i>
                                            </button>
                                        @endcan
                                        @can('master-unit.delete')
                                            <button type="button" class="btn btn-icon btn-sm btn-light-danger"
                                                    title="Hapus" aria-label="Hapus {{ $unit->name }}"
                                                    data-bs-toggle="modal" data-bs-target="#unitDeleteModal"
                                                    data-url="{{ route('master-data.units.destroy', $unit) }}"
                                                    data-name="{{ $unit->name }}">
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
                                    <i class="ki-duotone ki-abstract-26 fs-3x text-gray-400 mb-3">
                                        <span class="path1"></span><span class="path2"></span>
                                    </i>
                                    <div class="text-gray-800 fw-bold fs-5">Belum ada unit</div>
                                    <div class="text-muted fs-7 mt-1">
                                        Klik "Tambah Unit" atau jalankan <code>php artisan db:seed --class=UnitSeeder</code>.
                                    </div>
                                </td>
                            </tr>
                        @endforelse

                        {{-- Muncul saat pencarian tidak menemukan hasil --}}
                        <tr id="unitNoResult" class="d-none">
                            <td colspan="5" class="text-center text-muted py-10">Unit tidak ditemukan.</td>
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
                    <h3 class="modal-title fw-bold" id="formModalTitle">Tambah Unit</h3>
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
                        <label for="name" class="required fw-semibold fs-6 mb-2">Nama unit</label>
                        <input type="text" class="form-control form-control-solid" id="name" name="name"
                               maxlength="50" autocomplete="off"
                               placeholder="Contoh: Pcs, Lusin, Kodi, Box, Pak">
                        <div class="text-danger fs-7 mt-1" data-error-for="name"></div>
                    </div>

                    <div class="fv-row mb-7">
                        <label for="pcs_per_unit" class="fw-semibold fs-6 mb-2">Isi per unit</label>
                        <div class="input-group">
                            <input type="text" inputmode="numeric" class="form-control form-control-solid"
                                   id="pcs_per_unit" name="pcs_per_unit" maxlength="5" autocomplete="off"
                                   placeholder="Contoh: 12">
                            <span class="input-group-text">pcs</span>
                        </div>
                        <div class="form-check form-switch form-check-custom form-check-solid mt-4">
                            <input class="form-check-input" type="checkbox" role="switch"
                                   id="is_manual" name="is_manual" value="1">
                            <label class="form-check-label text-gray-700" for="is_manual">
                                Isi berbeda tiap produk (diisi saat input pembelian, mis. Box)
                            </label>
                        </div>
                        <div class="text-danger fs-7 mt-1" data-error-for="pcs_per_unit"></div>
                    </div>

                    <div class="fv-row">
                        <label for="category_ids" class="fw-semibold fs-6 mb-2">Berlaku untuk kategori</label>
                        <select class="form-select form-select-solid" id="category_ids" name="category_ids[]" multiple
                                data-control="select2"
                                data-dropdown-parent="#formModal"
                                data-placeholder="Pilih kategori"
                                data-close-on-select="false">
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Unit hanya muncul di form pembelian untuk kategori yang dipilih.</div>
                        <div class="text-danger fs-7 mt-1" data-error-for="category_ids"></div>
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
    <div class="modal fade" id="unitDeleteModal" tabindex="-1" aria-hidden="true">
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

                    <h3 class="fw-bold text-gray-900 mb-2">Hapus Unit</h3>
                    <div class="text-gray-600 fs-6 mb-2">
                        Hapus unit <span class="fw-bold text-gray-900" id="deleteUnitName"></span>?
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
        const $pcs    = $('#pcs_per_unit');
        const $manual = $('#is_manual');

        function togglePcs() {
            const manual = $manual.is(':checked');
            $pcs.prop('disabled', manual);
            if (manual) $pcs.val('');
        }

        $manual.on('change', togglePcs);
        $pcs.on('input', function () { this.value = this.value.replace(/\D/g, ''); });

        $modal.on('show.bs.modal', function (e) {
            const $btn = $(e.relatedTarget);
            const item = $btn.data('item');

            $form.attr('action', $btn.data('url'));
            $form.find('[name="_method"]').val(item ? 'PUT' : 'POST');
            $('#formModalTitle').text(item ? 'Edit Unit' : 'Tambah Unit');

            $('#name').val(item ? item.name : '');
            $manual.prop('checked', item ? !!item.is_manual : false);
            $pcs.val(item && !item.is_manual ? item.pcs_per_unit : '');
            togglePcs();
            $select.val(item ? item.category_ids : []).trigger('change');
        });

        // Modal hapus
        $('#unitDeleteModal').on('show.bs.modal', function (e) {
            const $btn = $(e.relatedTarget);
            $(this).find('form').attr('action', $btn.data('url'));
            $('#deleteUnitName').text($btn.data('name'));
        });

        // Pencarian cepat di tabel (nama unit + kategori)
        $('#unitSearch').on('input', function () {
            const q = this.value.trim().toLowerCase();
            let found = 0;

            $('#unitTable .unit-row').each(function () {
                const text  = ($(this).find('.unit-name').text() + ' ' + $(this).find('.unit-categories').text()).toLowerCase();
                const match = text.indexOf(q) !== -1;
                $(this).toggleClass('d-none', !match);
                if (match) found++;
            });

            $('#unitNoResult').toggleClass('d-none', found > 0 || $('#unitTable .unit-row').length === 0);
        });
    });
    </script>
@endpush