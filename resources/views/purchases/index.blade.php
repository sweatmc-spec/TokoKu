@extends('layouts.app')

@section('title', 'Cek Paket')

@section('content')
    @include('master-data.partials.flash')

    @php
        $search     = request('search');
        $counts     = $counts ?? null;   // opsional: ['all' => 24, 'pending' => 18, 'completed' => 6]
        $tabs       = [
            ['label' => 'Semua',   'key' => null,        'count' => 'all'],
            ['label' => 'Berjalan', 'key' => 'pending',   'count' => 'pending'],
            ['label' => 'Selesai',  'key' => 'completed', 'count' => 'completed'],
        ];
    @endphp

    <div class="card">
        {{-- ============ Judul + filter + search + tambah ============ --}}
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-4 py-5">
            <div>
                <h4 class="card-title mb-1">Cek Paket</h4>
                <div class="text-muted fs-7">Pantau paket dari sales dan centang barang yang sudah datang.</div>
            </div>

            <div class="d-flex align-items-center flex-wrap gap-3">
                {{-- Filter status --}}
                <div class="btn-group btn-group-sm" role="group" aria-label="Filter status" id="statusTabs">
                    @foreach ($tabs as $tab)
                        @php $active = ($status ?? null) === $tab['key']; @endphp
                        <a href="{{ route('purchases.index', array_filter(['status' => $tab['key'], 'search' => $search])) }}"
                           class="btn {{ $active ? 'btn-primary' : 'btn-outline-secondary' }}">
                            {{ $tab['label'] }}
                            @if ($counts)
                                <span class="opacity-75 ms-1">({{ $counts[$tab['count']] ?? 0 }})</span>
                            @endif
                        </a>
                    @endforeach
                </div>

                {{-- Search --}}
                <form method="GET" action="{{ route('purchases.index') }}" id="searchForm" class="position-relative" style="min-width:260px">
                    @if ($status ?? null)
                        <input type="hidden" name="status" value="{{ $status }}">
                    @endif
                    <i class="ki-outline ki-magnifier fs-4 text-muted position-absolute top-50 start-0 translate-middle-y ms-3"></i>
                    <input type="search" name="search" value="{{ $search }}"
                           class="form-control form-control-sm ps-10" placeholder="Cari kode atau sales...">
                </form>

                @can('produk-tambah.view')
                    <a href="{{ route('purchases.create') }}" class="btn btn-sm btn-primary text-nowrap d-inline-flex align-items-center gap-1">
                        <i class="ki-outline ki-plus fs-4"></i> Tambah Produk
                    </a>
                @endcan
            </div>
        </div>

        {{-- ============ Tabel ============ --}}
        <div class="card-body p-0" id="purchaseBody">
            <div class="table-responsive">
                <table class="table table-row-bordered align-middle mb-0">
                    <thead class="bg-body-tertiary">
                        <tr class="text-uppercase fs-8 fw-semibold text-muted">
                            <th class="ps-6" style="width:60px">No</th>
                            <th>Kode</th>
                            <th>Nama Sales</th>
                            <th>Tanggal Pembelian</th>
                            <th class="text-end">Total</th>
                            <th style="min-width:280px">Progress</th>
                            <th class="pe-6">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($purchases as $purchase)
                            @php
                                $pct = $purchase->items_count
                                    ? (int) round($purchase->received_items_count / $purchase->items_count * 100)
                                    : 0;
                            @endphp
                            <tr>
                                <td class="ps-6">{{ $purchases->firstItem() + $loop->index }}</td>

                                <td class="font-monospace fw-semibold text-nowrap">{{ $purchase->code }}</td>

                                <td>{{ $purchase->sales->name }}</td>

                                <td class="text-nowrap">
                                    <i class="ki-outline ki-calendar fs-5 text-muted me-1 align-middle"></i>
                                    {{ $purchase->purchase_date->format('d/m/Y') }}
                                </td>

                                <td class="text-end fw-semibold text-nowrap">Rp {{ number_format($purchase->total_amount, 0, ',', '.') }}</td>

                                <td>
                                    @if ($purchase->completed_at)
                                        <div class="d-flex align-items-center gap-3">
                                            <span class="badge bg-success d-inline-flex align-items-center gap-1">
                                                <i class="ki-outline ki-check fs-6 text-white"></i> Selesai
                                            </span>
                                            <small class="text-nowrap text-muted">{{ $purchase->received_items_count }}/{{ $purchase->items_count }} barang</small>
                                        </div>
                                    @else
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="progress flex-grow-1" style="height:6px">
                                                <div class="progress-bar" role="progressbar"
                                                     style="width: {{ $pct }}%"
                                                     aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                            <small class="text-nowrap text-muted">{{ $purchase->received_items_count }}/{{ $purchase->items_count }} barang</small>
                                            @if ($purchase->received_items_count > 0)
                                                <span class="badge bg-primary-subtle text-primary-emphasis">Proses</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary-emphasis">Menunggu</span>
                                            @endif
                                        </div>
                                    @endif
                                </td>

                                <td class="pe-6">
                                    {{-- Rata kiri supaya tombol Detail selalu di posisi yang sama, baik paket selesai maupun belum --}}
                                    <div class="d-flex justify-content-start align-items-center gap-2">
                                        <a href="{{ route('purchases.show', $purchase) }}" title="Detail"
                                           class="btn btn-sm btn-icon border-0 bg-primary-subtle text-primary-emphasis">
                                            <i class="ki-outline ki-eye fs-4 text-primary"></i>
                                        </a>

                                        {{-- Paket yang sudah selesai terkunci (stok sudah bertambah) --}}
                                        @unless ($purchase->completed_at)
                                            @can('cek-paket.edit')
                                                <a href="{{ route('purchases.edit', $purchase) }}" title="Edit"
                                                   class="btn btn-sm btn-icon border-0 bg-warning-subtle text-warning-emphasis">
                                                    <i class="ki-outline ki-pencil fs-4 text-warning"></i>
                                                </a>
                                            @endcan
                                            @can('cek-paket.delete')
                                                <button type="button" title="Hapus"
                                                        class="btn btn-sm btn-icon border-0 bg-danger-subtle text-danger-emphasis"
                                                        data-bs-toggle="modal" data-bs-target="#deleteModal"
                                                        data-url="{{ route('purchases.destroy', $purchase) }}"
                                                        data-name="pembelian {{ $purchase->code }} dari {{ $purchase->sales->name }} (seluruh barangnya)">
                                                    <i class="ki-outline ki-trash fs-4 text-danger"></i>
                                                </button>
                                            @endcan
                                        @endunless
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-8">
                                    {{ $search ? 'Tidak ada paket yang cocok dengan pencarian.' : 'Belum ada paket dari sales.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ============ Footer: info jumlah + pagination ============ --}}
        <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-3" id="purchaseFooter">
            <div class="text-muted fs-7">
                @if ($purchases->total() > 0)
                    Menampilkan <strong>{{ $purchases->firstItem() }}</strong> sampai
                    <strong>{{ $purchases->lastItem() }}</strong> dari
                    <strong>{{ $purchases->total() }}</strong> paket
                @else
                    Tidak ada data
                @endif
            </div>

            {{ $purchases->links() }}
        </div>
    </div>

    @include('master-data.partials.delete-modal', ['label' => 'Pembelian'])
@endsection

@push('scripts')
    @include('master-data.partials.scripts')

    <script>
    $(function () {
        const $form  = $('#searchForm');
        const $input = $form.find('input[name="search"]');
        let timer = null;
        let req   = null;

        function loadList() {
            const url = $form.attr('action') + '?' + $form.serialize();

            if (req) req.abort();   // batalkan request lama kalau user masih mengetik

            req = $.get(url, function (html) {
                const $doc = $('<div>').html(html);

                // ganti tab, isi tabel, dan footer; kolom search tidak disentuh supaya fokus tetap
                ['#statusTabs', '#purchaseBody', '#purchaseFooter'].forEach(function (sel) {
                    $(sel).replaceWith($doc.find(sel));
                });

                history.replaceState(null, '', url);
            });
        }

        // update otomatis 300 ms setelah berhenti mengetik
        $input.on('input', function () {
            clearTimeout(timer);
            timer = setTimeout(loadList, 300);
        });

        // Enter tetap jalan tanpa reload halaman
        $form.on('submit', function (e) {
            e.preventDefault();
            clearTimeout(timer);
            loadList();
        });
    });
    </script>
@endpush