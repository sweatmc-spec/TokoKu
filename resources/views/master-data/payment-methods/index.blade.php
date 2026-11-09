@extends('layouts.app')

@section('title', 'Metode Pembayaran')

@section('content')
    @include('master-data.partials.flash')

    <div class="card">
        <div class="card-header border-0 pt-6">
            <div class="card-title flex-column align-items-start">
                <h3 class="fw-bold m-0">Daftar Metode Pembayaran</h3>
                <div class="text-muted fs-7 mt-1">Pilihan pembayaran yang tersedia saat transaksi.</div>
            </div>

            @can('master-metode-pembayaran.create')
                <div class="card-toolbar">
                    <button type="button" class="btn btn-primary"
                            data-bs-toggle="modal" data-bs-target="#formModal"
                            data-url="{{ route('master-data.payment-methods.store') }}">
                        <i class="ki-duotone ki-plus fs-2"></i> Tambah Metode
                    </button>
                </div>
            @endcan
        </div>

        <div class="card-body py-4">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 gs-0">
                            <th class="w-50px">No</th>
                            <th class="min-w-175px">Nama</th>
                            <th class="min-w-250px">Deskripsi</th>
                            <th class="min-w-100px">Status</th>
                            @canany(['master-metode-pembayaran.edit', 'master-metode-pembayaran.delete'])
                                <th class="text-end min-w-100px">Aksi</th>
                            @endcanany
                        </tr>
                    </thead>
                    <tbody class="text-gray-700 fw-semibold">
                        @forelse ($paymentMethods as $method)
                            <tr>
                                <td>{{ $paymentMethods->firstItem() + $loop->index }}</td>
                                <td class="text-gray-800 fw-bold">{{ $method->name }}</td>
                                <td class="text-gray-600">{{ $method->description ?: '-' }}</td>
                                <td>
                                    @if ($method->is_active)
                                        <span class="badge badge-light-success fs-7">Aktif</span>
                                    @else
                                        <span class="badge badge-light fs-7">Nonaktif</span>
                                    @endif
                                </td>
                                @canany(['master-metode-pembayaran.edit', 'master-metode-pembayaran.delete'])
                                    <td class="text-end text-nowrap">
                                        @can('master-metode-pembayaran.edit')
                                            <button type="button" class="btn btn-icon btn-sm btn-light-warning me-1"
                                                    title="Edit" aria-label="Edit {{ $method->name }}"
                                                    data-bs-toggle="modal" data-bs-target="#formModal"
                                                    data-url="{{ route('master-data.payment-methods.update', $method) }}"
                                                    data-item="{{ json_encode([
                                                        'name'        => $method->name,
                                                        'description' => $method->description,
                                                        'is_active'   => $method->is_active,
                                                    ]) }}">
                                                <i class="ki-duotone ki-pencil fs-2">
                                                    <span class="path1"></span><span class="path2"></span>
                                                </i>
                                            </button>
                                        @endcan
                                        @can('master-metode-pembayaran.delete')
                                            <button type="button" class="btn btn-icon btn-sm btn-light-danger"
                                                    title="Hapus" aria-label="Hapus {{ $method->name }}"
                                                    data-bs-toggle="modal" data-bs-target="#methodDeleteModal"
                                                    data-url="{{ route('master-data.payment-methods.destroy', $method) }}"
                                                    data-name="{{ $method->name }}">
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
                                    <i class="ki-duotone ki-wallet fs-3x text-gray-400 mb-3">
                                        <span class="path1"></span><span class="path2"></span>
                                        <span class="path3"></span><span class="path4"></span>
                                    </i>
                                    <div class="text-gray-800 fw-bold fs-5">Belum ada metode pembayaran</div>
                                    <div class="text-muted fs-7 mt-1">Klik "Tambah Metode" untuk menambahkan.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Paginasi bernomor (view Metronic, bukan bawaan Tailwind) --}}
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mt-4">
                <div class="text-muted fs-7">
                    Menampilkan {{ $paymentMethods->firstItem() ?? 0 }}-{{ $paymentMethods->lastItem() ?? 0 }} dari {{ $paymentMethods->total() }} data
                </div>
                {{ $paymentMethods->links('vendor.pagination.metronic') }}
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
                    <h3 class="modal-title fw-bold" id="formModalTitle">Tambah Metode Pembayaran</h3>
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
                        <label for="name" class="required fw-semibold fs-6 mb-2">Nama metode</label>
                        <input type="text" class="form-control form-control-solid" id="name" name="name"
                               maxlength="100" autocomplete="off"
                               placeholder="Contoh: Tunai, Transfer BCA, QRIS">
                        <div class="text-danger fs-7 mt-1" data-error-for="name"></div>
                    </div>

                    <div class="fv-row mb-7">
                        <label for="description" class="fw-semibold fs-6 mb-2">Deskripsi</label>
                        <input type="text" class="form-control form-control-solid" id="description" name="description"
                               maxlength="255" autocomplete="off" placeholder="Opsional">
                        <div class="text-danger fs-7 mt-1" data-error-for="description"></div>
                    </div>

                    <div class="form-check form-switch form-check-custom form-check-solid">
                        <input class="form-check-input" type="checkbox" role="switch"
                               id="is_active" name="is_active" value="1" checked>
                        <label class="form-check-label text-gray-700" for="is_active">Aktif</label>
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
    <div class="modal fade" id="methodDeleteModal" tabindex="-1" aria-hidden="true">
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

                    <h3 class="fw-bold text-gray-900 mb-2">Hapus Metode Pembayaran</h3>
                    <div class="text-gray-600 fs-6 mb-2">
                        Hapus metode <span class="fw-bold text-gray-900" id="deleteMethodName"></span>?
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
        const $modal = $('#formModal');
        const $form  = $modal.find('form');

        $modal.on('show.bs.modal', function (e) {
            const $btn = $(e.relatedTarget);
            const item = $btn.data('item');

            $form.attr('action', $btn.data('url'));
            $form.find('[name="_method"]').val(item ? 'PUT' : 'POST');
            $('#formModalTitle').text(item ? 'Edit Metode Pembayaran' : 'Tambah Metode Pembayaran');

            $('#name').val(item ? item.name : '');
            $('#description').val(item ? item.description : '');
            $('#is_active').prop('checked', item ? !!item.is_active : true);
        });

        // Modal hapus
        $('#methodDeleteModal').on('show.bs.modal', function (e) {
            const $btn = $(e.relatedTarget);
            $(this).find('form').attr('action', $btn.data('url'));
            $('#deleteMethodName').text($btn.data('name'));
        });
    });
    </script>
@endpush