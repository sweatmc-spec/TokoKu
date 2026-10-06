@extends('layouts.app')

@section('title', 'Terjual')

@section('content')
    <style>
        /* kolom tanggal: ikon + label tetap terlihat, isi input dibuat polos */
        .date-field { display: flex; align-items: center; gap: .5rem; cursor: pointer; }
        .date-field input { flex: 1; min-width: 0; width: 100%; border: 0; padding: 0; outline: 0; box-shadow: none; background: transparent; color: inherit; cursor: pointer; }
    </style>

    @php $adaFilter = $search !== '' || $dari || $sampai; @endphp

    <div class="card">
        {{-- Header: pencarian + filter tanggal + tombol transaksi baru --}}
        <div class="card-header border-0 pt-6">
            <div class="card-title flex-wrap gap-3">
                <div class="position-relative my-1">
                    <i class="ki-outline ki-magnifier fs-3 text-gray-500 position-absolute top-50 translate-middle-y ms-4"></i>
                    <input type="text" id="terjualSearch" value="{{ $search }}"
                           class="form-control form-control-solid w-250px ps-12"
                           placeholder="Cari kode atau customer" autocomplete="off">
                </div>
                <div class="form-control form-control-solid date-field w-225px">
                    <i class="ki-outline ki-calendar fs-3 text-gray-500"></i>
                    <span class="text-gray-500 fs-7 fw-semibold">Dari</span>
                    <input type="text" id="filterDari" value="{{ $dari }}" placeholder="Pilih tanggal" readonly>
                </div>
                <div class="form-control form-control-solid date-field w-225px">
                    <i class="ki-outline ki-calendar fs-3 text-gray-500"></i>
                    <span class="text-gray-500 fs-7 fw-semibold">Sampai</span>
                    <input type="text" id="filterSampai" value="{{ $sampai }}" placeholder="Pilih tanggal" readonly>
                </div>
                <button type="button" id="btnReset" class="btn btn-light {{ $adaFilter ? '' : 'd-none' }}">
                    <i class="ki-outline ki-cross fs-3"></i> Reset
                </button>
            </div>

            @can('penjualan-pembayaran.view')
                <div class="card-toolbar">
                    <a href="{{ route('penjualan.pembayaran.create') }}" class="btn btn-primary">
                        <i class="ki-outline ki-plus fs-2"></i> Transaksi baru
                    </a>
                </div>
            @endcan
        </div>

        <div class="card-body py-4">
            {{-- Ringkasan (ikut berubah sesuai filter) --}}
            <div id="terjualSummary" class="d-flex flex-wrap align-items-center gap-7 mb-6">
                <div>
                    <div class="text-gray-500 fs-7 fw-semibold">Transaksi</div>
                    <div class="fs-2x fw-bold text-gray-900 lh-sm">{{ number_format($summary['count'], 0, ',', '.') }}</div>
                </div>
                <div class="border-start border-gray-300 h-40px"></div>
                <div>
                    <div class="text-gray-500 fs-7 fw-semibold">Pendapatan</div>
                    <div class="fs-2x fw-bold text-gray-900 lh-sm">{{ \App\Models\Terjual::rupiah($summary['revenue']) }}</div>
                </div>
                @if ($adaFilter)
                    <span class="badge badge-light-primary fs-7">Sesuai filter</span>
                @endif
            </div>

            {{-- Bagian ini diganti otomatis saat mencari / memfilter / pindah halaman --}}
            <div id="terjualTable">
                <div class="table-responsive">
                    <table class="table table-row-dashed align-middle fs-6 gy-5 mb-0">
                        <thead>
                            <tr class="text-start text-gray-500 fw-bold fs-7 gs-0">
                                <th class="w-50px">No</th>
                                <th class="min-w-175px">Kode</th>
                                <th class="min-w-125px">Tanggal</th>
                                <th class="min-w-125px">Customer</th>
                                <th>Metode</th>
                                <th class="text-end min-w-125px">Total</th>
                                <th class="text-end min-w-125px">Bayar</th>
                                <th class="text-end min-w-125px">Kembalian</th>
                                <th class="text-end min-w-125px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-700 fw-semibold">
                            @forelse ($terjuals as $t)
                                <tr>
                                    <td>{{ $terjuals->firstItem() + $loop->index }}</td>
                                    <td>
                                        <div class="text-gray-800 fw-bold">{{ $t->code }}</div>
                                        <div class="text-muted fs-8">{{ $t->items_count }} macam barang</div>
                                    </td>
                                    <td>
                                        <div>{{ $t->sold_at->format('d/m/Y') }}</div>
                                        <div class="text-muted fs-8">{{ $t->sold_at->format('H:i') }}</div>
                                    </td>
                                    <td>{{ $t->customer_name }}</td>
                                    <td><span class="badge badge-light-primary fs-7">{{ $t->payment_method_text }}</span></td>
                                    <td class="text-end">
                                        <div class="text-gray-900 fw-bold">{{ \App\Models\Terjual::rupiah($t->total) }}</div>
                                        @if ($t->discount_label)
                                            <div class="text-muted fs-8">diskon {{ $t->discount_label }}</div>
                                        @endif
                                    </td>
                                    <td class="text-end">{{ \App\Models\Terjual::rupiah($t->paid_amount) }}</td>
                                    <td class="text-end">
                                        @if ($t->change_amount > 0)
                                            {{ \App\Models\Terjual::rupiah($t->change_amount) }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end text-nowrap">
                                        @can('penjualan-terjual.view')
                                            <button type="button" class="btn btn-icon btn-sm btn-light-primary me-1"
                                                    title="Detail" aria-label="Detail {{ $t->code }}"
                                                    data-bs-toggle="modal" data-bs-target="#modal-view-terjual"
                                                    data-url="{{ route('penjualan.terjual.show', $t) }}"
                                                    data-code="{{ $t->code }}">
                                                <i class="ki-outline ki-eye fs-2"></i>
                                            </button>
                                        @endcan
                                        @can('penjualan-terjual.edit')
                                            <a href="{{ route('penjualan.terjual.edit', $t) }}"
                                               class="btn btn-icon btn-sm btn-light-warning me-1"
                                               title="Edit" aria-label="Edit {{ $t->code }}">
                                                <i class="ki-outline ki-pencil fs-2"></i>
                                            </a>
                                        @endcan
                                        @can('penjualan-terjual.delete')
                                            <button type="button" class="btn btn-icon btn-sm btn-light-danger"
                                                    title="Hapus" aria-label="Hapus {{ $t->code }}"
                                                    data-bs-toggle="modal" data-bs-target="#modal-delete-terjual"
                                                    data-action="{{ route('penjualan.terjual.destroy', $t) }}"
                                                    data-code="{{ $t->code }}"
                                                    data-customer="{{ $t->customer_name }}"
                                                    data-total="{{ \App\Models\Terjual::rupiah($t->total) }}">
                                                <i class="ki-outline ki-trash fs-2"></i>
                                            </button>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-15">
                                        <i class="ki-outline ki-wallet fs-3x text-gray-400 mb-3"></i>
                                        <div class="text-gray-800 fw-bold fs-5">
                                            {{ $adaFilter ? 'Transaksi tidak ditemukan' : 'Belum ada transaksi' }}
                                        </div>
                                        <div class="text-muted fs-7 mt-1">
                                            {{ $adaFilter
                                                ? 'Tidak ada transaksi yang cocok dengan filter.'
                                                : 'Transaksi muncul di sini setelah dibuat dari halaman Pembayaran.' }}
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
                        Menampilkan {{ $terjuals->firstItem() ?? 0 }}-{{ $terjuals->lastItem() ?? 0 }} dari {{ $terjuals->total() }} data
                    </div>
                    {{ $terjuals->links('vendor.pagination.metronic') }}
                </div>
            </div>
        </div>
    </div>

    @include('Penjualan.Terjual.partials._modals')
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const search  = document.getElementById('terjualSearch');
    const dariEl  = document.getElementById('filterDari');
    const sampaiEl = document.getElementById('filterSampai');
    const resetBtn = document.getElementById('btnReset');
    const swapIds = ['terjualSummary', 'terjualTable'];

    // ---- Datepicker (flatpickr bawaan Metronic) ----
    const fpBase = {
        dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', allowInput: false,
        locale: (flatpickr.l10ns && flatpickr.l10ns.id) ? flatpickr.l10ns.id : 'default',
    };
    const fpSampai = flatpickr(sampaiEl, Object.assign({}, fpBase, { onChange: () => muat(1) }));
    const fpDari = flatpickr(dariEl, Object.assign({}, fpBase, {
        onChange: function (dates, str) { fpSampai.set('minDate', str || null); muat(1); },
    }));
    if (dariEl.value) fpSampai.set('minDate', dariEl.value);

    // ---- URL sesuai filter saat ini ----
    function buildUrl(page) {
        const p = new URLSearchParams();
        const q = search.value.trim();
        if (q) p.set('q', q);
        if (dariEl.value) p.set('dari', dariEl.value);
        if (sampaiEl.value) p.set('sampai', sampaiEl.value);
        if (page && page > 1) p.set('page', page);
        const qs = p.toString();
        return window.location.pathname + (qs ? '?' + qs : '');
    }

    function updateReset() {
        resetBtn.classList.toggle('d-none', !(search.value.trim() || dariEl.value || sampaiEl.value));
    }

    // ---- Muat data tanpa reload penuh ----
    let ctrl = null, seq = 0;

    async function muat(page) {
        const url = buildUrl(page);
        updateReset();

        if (ctrl) ctrl.abort();
        ctrl = new AbortController();
        const mine = ++seq;
        document.getElementById('terjualTable').classList.add('opacity-50');

        try {
            const res = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
                signal: ctrl.signal,
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);

            const html = await res.text();
            if (mine !== seq) return;

            const doc = new DOMParser().parseFromString(html, 'text/html');
            swapIds.forEach(id => {
                const fresh = doc.getElementById(id), old = document.getElementById(id);
                if (fresh && old) old.replaceWith(fresh);
            });
            history.replaceState(null, '', url);
        } catch (err) {
            if (err.name === 'AbortError') return;
            window.location.href = url; // fallback: reload biasa
        }
    }

    // ---- Cari otomatis (tanpa Enter) ----
    let timer = null;
    search.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(() => muat(1), 400);
    });
    search.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); clearTimeout(timer); muat(1); } });

    // ---- Reset filter ----
    resetBtn.addEventListener('click', function () {
        search.value = '';
        fpDari.clear();
        fpSampai.clear();
        fpSampai.set('minDate', null);
        muat(1);
    });

    // ---- Paginasi: nomor halaman diambil dari link, filter diambil dari kolom saat ini ----
    document.addEventListener('click', function (e) {
        const link = e.target.closest('#terjualTable .pagination a');
        if (!link) return;
        e.preventDefault();
        const page = parseInt(new URL(link.href, window.location.origin).searchParams.get('page'), 10) || 1;
        muat(page);
    });
});
</script>
@endpush