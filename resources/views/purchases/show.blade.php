@extends('layouts.app')

@section('title', 'Detail Paket')

@push('styles')
    <style>
        .row-received td { background-color: color-mix(in srgb, var(--bs-success) 8%, transparent); }
        .stat-value { font-variant-numeric: tabular-nums; }
        .receive-check:disabled { cursor: not-allowed; }
    </style>
@endpush

@section('content')
    @include('master-data.partials.flash')

    @php
        $totalItems    = $purchase->items->count();
        $receivedItems = $purchase->items->whereNotNull('received_at')->count();
        $pct           = $totalItems ? (int) round($receivedItems / $totalItems * 100) : 0;
        $completed     = (bool) $purchase->completed_at;
        $canReceive    = auth()->user()->can('cek-paket.receive');
        $totalLabel    = 'Rp ' . number_format($purchase->total_amount, 0, ',', '.');
    @endphp

    {{-- ============ Info pembelian (hanya baca) ============ --}}
    <div class="card mb-6">
        <div class="card-header">
            <div class="d-flex align-items-center gap-4 py-5">
                <i class="ki-outline ki-notepad-edit fs-2x text-primary"></i>
                <div>
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <h3 class="fw-bold mb-0">Pembelian {{ $purchase->code }}</h3>
                        @if ($completed)
                            <span class="badge badge-light-success"><i class="ki-outline ki-lock fs-8 me-1"></i>Selesai &amp; terkunci</span>
                        @else
                            <span class="badge badge-light-warning">Belum selesai</span>
                        @endif
                    </div>
                    <div class="text-muted fs-7">Detail pembelian dari {{ $purchase->sales->name }}</div>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('purchases.index') }}" class="btn btn-light">
                    <i class="ki-outline ki-arrow-left fs-4"></i>Kembali
                </a>

                @if (! $completed)
                    @can('cek-paket.edit')
                        <a href="{{ route('purchases.edit', $purchase) }}" class="btn btn-light-warning">
                            <i class="ki-outline ki-pencil fs-4"></i>Edit
                        </a>
                    @endcan
                    @can('cek-paket.delete')
                        {{-- hanya bisa dihapus selama belum ada barang yang dicek (yang dicek sudah masuk stok) --}}
                        <button type="button" class="btn btn-light-danger {{ $receivedItems > 0 ? 'd-none' : '' }}" id="btnDeleteAll"
                                data-bs-toggle="modal" data-bs-target="#deleteModal"
                                data-url="{{ route('purchases.destroy', $purchase) }}"
                                data-name="pembelian {{ $purchase->code }} dari {{ $purchase->sales->name }} (seluruh barangnya)">
                            <i class="ki-outline ki-trash fs-4"></i>Hapus semua
                        </button>
                    @endcan
                @endif
            </div>
        </div>

        <div class="card-body">
            <div class="bg-light rounded p-5">
                <div class="row g-5">
                    <div class="col-md-4">
                        <div class="text-muted fs-8 mb-1">Sales</div>
                        <div class="fw-bold fs-5">{{ $purchase->sales->name }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted fs-8 mb-1">Tanggal pembelian</div>
                        <div class="fw-bold fs-5">{{ $purchase->purchase_date->format('d/m/Y') }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted fs-8 mb-2">Progress barang datang</div>
                        <div class="d-flex align-items-center gap-3">
                            <div class="progress flex-grow-1" style="height:8px">
                                <div class="progress-bar {{ $completed ? 'bg-success' : '' }}" id="progressBar" role="progressbar"
                                     style="width: {{ $pct }}%"
                                     aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <span class="badge {{ $completed ? 'badge-light-success' : 'badge-light-primary' }} text-nowrap" id="progressText">
                                {{ $completed ? 'Selesai' : $receivedItems . '/' . $totalItems . ' barang' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============ Barang yang dibeli + centang datang ============ --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title gap-3">
                <h3 class="fw-bold mb-0">Daftar barang yang dibeli</h3>
                <span class="badge badge-light-success">{{ $totalItems }} item</span>
            </div>
            <div class="d-flex align-items-center text-muted fs-7" id="receivedSummary">
                {{ $receivedItems }} dari {{ $totalItems }} barang sudah datang
            </div>
        </div>

        <div class="card-body">
            <div class="alert alert-danger d-none" id="receiveAlert" role="alert"></div>

            @if (! $completed)
                <div class="d-flex align-items-start gap-3 rounded border border-dashed border-warning bg-light-warning p-4 mb-6 fs-7">
                    <i class="ki-outline ki-information-5 fs-2 text-warning"></i>
                    <span>
                        Centang barang yang sudah datang. Setiap barang yang dicentang langsung masuk ke stok
                        sesuai produknya, dan centang masih bisa dibatalkan selama belum semua barang dicentang.
                        <strong>Begitu barang terakhir dicentang, paket selesai dan terkunci</strong>
                        (tidak bisa diedit, dihapus, atau dibatalkan lagi).
                    </span>
                </div>
            @else
                <div class="d-flex align-items-start gap-3 rounded border border-dashed border-success bg-light-success p-4 mb-6 fs-7">
                    <i class="ki-outline ki-lock fs-2 text-success"></i>
                    <span>
                        Semua barang sudah dicek dan masuk ke stok. Paket ini sudah selesai dan terkunci,
                        pengeluaran Kulakan-nya sudah tercatat otomatis di halaman Pengeluaran.
                    </span>
                </div>
            @endif

            <div class="table-responsive border rounded">
                <table class="table table-row-bordered align-middle gs-4 gy-4 mb-0">
                    <thead>
                        <tr class="fw-bold text-muted fs-7 bg-light">
                            <th style="width:50px">No</th>
                            <th style="width:80px" class="text-center">Datang</th>
                            <th>Nama produk</th>
                            <th>Kategori</th>
                            <th>Jumlah</th>
                            <th class="text-end">Total pcs</th>
                            <th class="text-end">Harga / unit</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($purchase->items as $item)
                            <tr class="{{ $item->received_at ? 'row-received' : '' }}">
                                <td class="text-muted">{{ $loop->iteration }}</td>
                                <td>
                                    <div class="form-check form-check-custom form-check-solid justify-content-center">
                                        <input type="checkbox" class="form-check-input receive-check"
                                               data-url="{{ route('purchases.receive', [$purchase, $item]) }}"
                                               @if ($completed) title="Paket sudah selesai dan terkunci" @elseif (! $canReceive) title="Kamu tidak punya izin mencentang barang" @endif
                                               @checked($item->received_at)
                                               @disabled(! $canReceive || $completed)>
                                    </div>
                                </td>
                                <td class="fw-semibold">
                                    {{ $item->product?->name ?? $item->product_name }}
                                    @if ($item->variant_text)
                                        <div class="fs-8 text-muted fw-normal">{{ $item->variant_text }}</div>
                                    @endif
                                </td>
                                <td><span class="badge badge-light-primary">{{ $item->category->name }}</span></td>
                                <td>
                                    {{ number_format($item->unit_qty, 0, ',', '.') }} {{ $item->unit_label }}
                                    @if ($item->pcs_per_unit > 1)
                                        <span class="text-muted fs-8">(isi {{ $item->pcs_per_unit }} pcs)</span>
                                    @endif
                                </td>
                                <td class="text-end">{{ number_format($item->qty_pcs, 0, ',', '.') }}</td>
                                <td class="text-end">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                                <td class="text-end fw-bold">Rp {{ number_format($item->total_price, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="row g-6 mt-2">
                <div class="col-lg-7">
                    <label class="form-label">Catatan transaksi</label>
                    <textarea class="form-control" rows="5" disabled>{{ $purchase->note }}</textarea>
                </div>

                <div class="col-lg-5">
                    <div class="bg-light rounded p-5">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Total jenis barang</span>
                            <strong>{{ $totalItems }} macam</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-4">
                            <span class="text-muted">Total kuantitas masuk</span>
                            <strong>{{ number_format($purchase->items->sum('qty_pcs'), 0, ',', '.') }} pcs</strong>
                        </div>
                        <div class="separator separator-dashed mb-4"></div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-semibold">Total pembelian</span>
                            <span class="fw-bold fs-2 text-primary stat-value">{{ $totalLabel }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Peringatan sebelum barang TERAKHIR dicentang (paket akan selesai dan terkunci) --}}
    <div class="modal fade" id="modalComplete" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h3 class="modal-title fw-bold d-flex align-items-center gap-3">
                        <i class="ki-outline ki-information-5 fs-2x text-warning"></i>Selesaikan paket ini?
                    </h3>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-5">
                        Ini barang terakhir. Setelah dicentang, paket <strong class="text-body">{{ $purchase->code }}</strong>
                        akan selesai dan terkunci:
                    </p>

                    <div class="d-flex flex-column gap-3 mb-5">
                        <div class="d-flex align-items-start gap-3">
                            <i class="ki-outline ki-lock fs-4 text-warning mt-1"></i>
                            <span>Centang barang tidak bisa dibatalkan lagi.</span>
                        </div>
                        <div class="d-flex align-items-start gap-3">
                            <i class="ki-outline ki-pencil fs-4 text-warning mt-1"></i>
                            <span>Pembelian tidak bisa diedit atau dihapus.</span>
                        </div>
                        <div class="d-flex align-items-start gap-3">
                            <i class="ki-outline ki-wallet fs-4 text-warning mt-1"></i>
                            <span>Pengeluaran Kulakan otomatis tercatat di halaman Pengeluaran.</span>
                        </div>
                    </div>

                    <div class="bg-light rounded p-4 d-flex justify-content-between align-items-center mb-4">
                        <span class="text-muted">Pengeluaran Kulakan</span>
                        <strong class="fs-4 text-primary stat-value" id="completeTotal">{{ $totalLabel }}</strong>
                    </div>

                    <div class="text-muted fs-7">
                        Pastikan semua barang sudah benar-benar datang dan sesuai sebelum melanjutkan.
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Periksa lagi</button>
                    <button type="button" class="btn btn-primary" id="btnConfirmComplete">Ya, selesaikan paket</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal hapus (dipakai script bersama) --}}
    @include('master-data.partials.delete-modal', ['label' => 'Pembelian'])
@endsection

@push('scripts')
    @include('master-data.partials.scripts')

    <script>
        const CSRF = '{{ csrf_token() }}';
    </script>

    @verbatim
    <script>
    $(function () {
        const errMsg = (xhr) => (xhr.responseJSON && xhr.responseJSON.message)
            ? xhr.responseJSON.message
            : 'Terjadi kesalahan. Silakan coba lagi.';

        function showAlert(msg) { $('#receiveAlert').removeClass('d-none').text(msg); }

        function updateProgress(res) {
            $('#progressBar').css('width', res.percent + '%').attr('aria-valuenow', res.percent)
                .toggleClass('bg-success', res.percent === 100);
            $('#progressText').text(res.received + '/' + res.total + ' barang');
            $('#receivedSummary').text(res.received + ' dari ' + res.total + ' barang sudah datang');
            // sudah ada barang yang masuk stok: pembelian tidak bisa dihapus lagi
            $('#btnDeleteAll').toggleClass('d-none', res.received > 0);
        }

        // Centang = barang itu langsung masuk stok. Batal centang = ditarik kembali dari stok.
        function sendReceive($cb, checked) {
            $cb.prop('disabled', true);                 // kunci selama request berjalan

            $.ajax({
                url: $cb.data('url'),
                method: 'PATCH',
                dataType: 'json',
                headers: { 'X-CSRF-TOKEN': CSRF },
                data: { received: checked ? 1 : 0 }
            }).done(function (res) {
                $cb.closest('tr').toggleClass('row-received', checked);
                updateProgress(res);
                if (res.changed || res.completed) {
                    window.location.reload();           // paket selesai -> dimuat ulang dalam keadaan terkunci
                } else {
                    $cb.prop('disabled', false);
                }
            }).fail(function (xhr) {
                $cb.prop('checked', !checked).prop('disabled', false);
                showAlert(errMsg(xhr));
            });
        }

        // ---- peringatan sebelum barang TERAKHIR dicentang ----
        const completeModalEl = document.getElementById('modalComplete');
        const completeModal   = bootstrap.Modal.getOrCreateInstance(completeModalEl);
        const $confirmBtn     = $('#btnConfirmComplete');
        let pending   = null;     // checkbox yang menunggu konfirmasi
        let confirmed = false;

        $('.receive-check').on('change', function () {
            const $cb     = $(this);
            const checked = this.checked;
            $('#receiveAlert').addClass('d-none');

            // barang terakhir = tidak ada lagi checkbox lain yang belum dicentang
            const isLast = checked && $('.receive-check').not(this).filter(':not(:checked)').length === 0;

            if (isLast) {
                pending   = $cb;
                confirmed = false;
                completeModal.show();
                return;                                 // belum dikirim ke server
            }

            sendReceive($cb, checked);                  // centang biasa & batal centang: bebas
        });

        $confirmBtn.on('click', function () {
            if (confirmed || !pending) return;          // cegah klik ganda
            confirmed = true;
            $confirmBtn.prop('disabled', true);
            completeModal.hide();
            sendReceive(pending, true);
        });

        // ditutup tanpa konfirmasi ("Periksa lagi") -> centangnya dikembalikan
        completeModalEl.addEventListener('hidden.bs.modal', function () {
            if (!confirmed && pending) pending.prop('checked', false);
            pending   = null;
            confirmed = false;
            $confirmBtn.prop('disabled', false);
        });
    });
    </script>
    @endverbatim
@endpush