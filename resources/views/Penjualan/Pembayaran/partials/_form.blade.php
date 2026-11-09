{{--
    Form kasir. Dipakai oleh:
      - Penjualan/Pembayaran/create.blade.php  (transaksi baru)
      - Penjualan/Terjual/edit.blade.php       (edit transaksi)

    Variabel : $action, $method ('POST'|'PUT'), $catalog, $paymentMethods, $submitLabel, $terjual (null = baru)

    Layout: kiri = keranjang (baris barang), kanan = panel ringkasan pembayaran yang menempel saat scroll.
    Pintasan keyboard: F2 cari/scan barang, F4 diskon, F9 bayar pas.
    Harga tidak pernah dikirim dari browser; server mengambilnya dari Profit Harga.
--}}
@php
    $terjual    = $terjual ?? null;
    $isEdit     = $terjual !== null;
    $selectedPm = old('payment_method_id', $terjual?->payment_method_id ?? $paymentMethods->first()?->id);
    $discType   = old('discount_type', $terjual?->discount_type ?? 'nominal');
    $discValue  = old('discount_value', $terjual?->discount_value ?: '');
    $paidValue  = old('paid', $terjual?->paid_amount ?? '');

    // ikon tiap metode pembayaran, ditebak dari namanya
    $pmIcon = function (string $name): string {
        $n = \Illuminate\Support\Str::lower($name);

        return match (true) {
            \Illuminate\Support\Str::contains($n, ['tunai', 'cash']) => 'ki-wallet',
            \Illuminate\Support\Str::contains($n, ['qris', 'qr']) => 'ki-scan-barcode',
            \Illuminate\Support\Str::contains($n, ['kartu', 'card', 'debit', 'kredit']) => 'ki-credit-cart',
            \Illuminate\Support\Str::contains($n, ['bca', 'bni', 'bri', 'mandiri', 'transfer', 'bank']) => 'ki-bank',
            default => 'ki-dollar',
        };
    };
    $selectedPmName = $paymentMethods->firstWhere('id', (int) $selectedPm)?->name;

    if (old('items') !== null) {
        // setelah validasi gagal: pakai isian terakhir; index asli dipertahankan supaya pesan error cocok per baris
        $initialRows = collect(old('items'))
            ->map(fn ($r, $k) => ['index' => (int) $k, 'item' => $r['item'] ?? '', 'qty' => (int) ($r['qty'] ?? 1)])
            ->values();
    } elseif ($isEdit) {
        $initialRows = $terjual->items
            ->map(fn ($it, $k) => ['index' => $k, 'item' => $it->item_key, 'qty' => (int) $it->qty])
            ->values();
    } else {
        $initialRows = collect([['index' => 0, 'item' => '', 'qty' => 1]]);
    }
@endphp

<style>
    /* Semua warna memakai variabel tema, jadi ikut dark / light */
    .receipt-sticky { position: sticky; top: 90px; }
    .num { font-variant-numeric: tabular-nums; }

    .sec-label {
        font-size: .7rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase;
        color: var(--bs-gray-500); margin-bottom: .5rem; display: block;
    }
    .kbd-chip {
        font: 600 .7rem/1 ui-monospace, SFMono-Regular, Menlo, monospace;
        padding: .2rem .45rem; border: 1px solid var(--bs-border-color); border-radius: .3rem;
        background: var(--bs-gray-100); color: var(--bs-gray-700);
    }

    /* baris keranjang */
    .cart-row {
        border: 1px solid var(--bs-border-color); border-radius: .75rem;
        background: var(--bs-gray-100); padding: .75rem 1rem 1rem; transition: border-color .15s, background-color .15s;
    }
    .cart-row.is-blank { border-style: dashed; background: transparent; }
    .cart-row.is-blank .cart-col { opacity: .55; }
    .cart-row.is-invalid-row { border-color: var(--bs-danger) !important; }
    .cart-no {
        width: 1.75rem; height: 1.75rem; flex: 0 0 1.75rem;
        display: inline-flex; align-items: center; justify-content: center;
        border-radius: 50%; font-size: .8rem; font-weight: 600;
        background: var(--bs-gray-200); color: var(--bs-gray-700);
    }
    /* tata letak satu kartu: baris 1 = tombol hapus di kanan atas, baris 2 = no | barang | jumlah | harga | subtotal */
    .cart-grid {
        display: grid; align-items: start; gap: .5rem 1.25rem;
        grid-template-columns: 1.75rem minmax(0, 1fr) 132px max-content max-content;
        grid-template-areas:
            ".  .    .   .     del"
            "no item qty price sub";
    }
    .cart-grid .g-no   { grid-area: no; margin-top: .5rem; }
    .cart-grid .g-item { grid-area: item; min-width: 0; }
    .cart-grid .g-qty  { grid-area: qty; }
    .cart-grid .g-price, .cart-grid .g-sub {
        min-height: 2.75rem; display: flex; flex-direction: column; justify-content: center; white-space: nowrap;
    }
    .cart-grid .g-price { grid-area: price; min-width: 7rem; }
    .cart-grid .g-sub   { grid-area: sub; min-width: 8rem; }
    .cart-grid .g-del   { grid-area: del; justify-self: end; }
    /* selector panjang supaya mengalahkan ukuran bawaan .btn-icon Metronic */
    .cart-row .btn.btn-icon.cart-del { width: 1.9rem; height: 1.9rem; }
    @media (max-width: 767.98px) {
        .cart-grid {
            grid-template-columns: 1.75rem 132px 1fr 1fr;
            grid-template-areas:
                ".  .    .     del"
                "no item item  item"
                ".  qty  price sub";
        }
        .cart-grid .g-price, .cart-grid .g-sub { min-width: 0; }
    }
    .stepper {
        display: inline-flex; align-items: center; width: 132px; height: 2.75rem;
        border: 1px solid var(--bs-border-color); border-radius: .6rem; background: var(--bs-body-bg);
    }
    .stepper:has(.is-invalid) { border-color: var(--bs-danger); }
    .stepper .form-control { flex: 1; min-width: 0; border: 0; background: transparent; box-shadow: none; text-align: center; padding-left: 0; padding-right: 0; font-weight: 600; }
    .stepper .btn { flex: 0 0 auto; }

    /* pilihan (metode pembayaran, Rp / %) */
    .choice {
        display: inline-flex; align-items: center; justify-content: center; gap: .35rem; cursor: pointer;
        border: 1px solid var(--bs-border-color); border-radius: .6rem; color: var(--bs-gray-700);
        background: transparent; transition: border-color .15s, background-color .15s, color .15s;
        user-select: none;
    }
    .choice:hover { border-color: var(--bs-gray-400); }
    .btn-check:checked + .choice {
        border-color: var(--bs-primary); color: var(--bs-primary);
        background: rgba(var(--bs-primary-rgb), .1);
    }
    .btn-check:focus-visible + .choice { box-shadow: 0 0 0 .2rem rgba(var(--bs-primary-rgb), .25); }
    .pm-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(76px, 1fr)); gap: .5rem; }
    .pm-grid > div { display: flex; position: relative; }
    .pm-grid .pm-tile { flex: 1; }
    .pm-tile { flex-direction: column; padding: .65rem .25rem; font-size: .8rem; font-weight: 600; }
    .seg-group { display: inline-flex; }
    .seg-group .choice { border-radius: 0; padding: .35rem .75rem; font-size: .8rem; font-weight: 700; }
    .seg-group label.choice:first-of-type { border-radius: .5rem 0 0 .5rem; }
    .seg-group label.choice:last-of-type { border-radius: 0 .5rem .5rem 0; margin-left: -1px; }

    /* kotak total */
    .total-box {
        border: 1px solid rgba(var(--bs-primary-rgb), .4); border-radius: .75rem; padding: 1rem 1.25rem;
        background: linear-gradient(135deg, rgba(var(--bs-primary-rgb), .16), rgba(var(--bs-primary-rgb), .04));
    }
    .total-box .amount { font-size: 2rem; line-height: 1.15; font-weight: 700; letter-spacing: -.02em; }

    /* input uang */
    .money-field {
        display: flex; align-items: center; gap: .5rem; padding: .4rem .9rem;
        border: 1px solid var(--bs-border-color); border-radius: .6rem; background: var(--bs-body-bg);
    }
    .money-field:focus-within { border-color: var(--bs-primary); box-shadow: 0 0 0 .2rem rgba(var(--bs-primary-rgb), .15); }
    .money-field .prefix { color: var(--bs-gray-500); font-weight: 600; }
    .money-field .form-control,
    .money-field .form-control:focus { border: 0; background: transparent; box-shadow: none; padding: 0; text-align: right; flex: 1; min-width: 0; }
    .quick-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: .5rem; }
    .quick-grid .btn { border: 1px solid var(--bs-border-color); background: transparent; color: var(--bs-gray-700); font-weight: 600; padding: .45rem .25rem; }
    .quick-grid .btn:hover { border-color: var(--bs-primary); color: var(--bs-primary); }
    .quick-grid .btn.is-exact { color: var(--bs-primary); }

    /* kotak kembalian */
    .status-box {
        display: flex; align-items: center; justify-content: space-between; gap: 1rem;
        border: 1px solid var(--bs-border-color); border-radius: .75rem; padding: .9rem 1.1rem;
        transition: background-color .15s, color .15s, border-color .15s;
    }
    /* Metronic tidak mendukung kelas transparansi Bootstrap, jadi tint dibuat sendiri */
    .status-box.status-ok {
        color: var(--bs-success);
        background: rgba(23, 198, 83, .12);  background: color-mix(in srgb, var(--bs-success) 12%, transparent);
        border-color: rgba(23, 198, 83, .45); border-color: color-mix(in srgb, var(--bs-success) 45%, transparent);
    }
    .status-box.status-bad {
        color: var(--bs-danger);
        background: rgba(248, 40, 90, .12);  background: color-mix(in srgb, var(--bs-danger) 12%, transparent);
        border-color: rgba(248, 40, 90, .45); border-color: color-mix(in srgb, var(--bs-danger) 45%, transparent);
    }
</style>

<form id="form-penjualan" method="POST" action="{{ $action }}" autocomplete="off" novalidate>
    @csrf
    @if ($method === 'PUT')
        @method('PUT')
    @endif

    @if ($errors->any())
        <div class="alert alert-danger mb-6">
            <div class="fw-bold mb-1">Transaksi belum bisa disimpan:</div>
            <ul class="mb-0 ps-5">
                @foreach (collect($errors->all())->unique() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-6 align-items-start">

        {{-- ============================ KERANJANG ============================ --}}
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header border-0 pt-6 d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <h3 class="card-title fw-bold mb-0">{{ $isEdit ? 'Edit ' . $terjual->code : 'Barang yang dibeli' }}</h3>
                        <span class="badge badge-light-primary" id="cart-count">0 macam</span>
                    </div>
                </div>

                <div class="card-body pt-2">
                    {{-- pintasan keyboard --}}
                    <div class="d-flex flex-wrap align-items-center gap-5 fs-8 text-gray-600 pb-4 mb-5 border-bottom border-gray-200">
                        <span><kbd class="kbd-chip">F2</kbd> Scan / cari</span>
                        <span><kbd class="kbd-chip">F4</kbd> Diskon</span>
                        <span><kbd class="kbd-chip">F9</kbd> Bayar pas</span>
                    </div>

                    <div id="cart-rows" class="d-flex flex-column gap-4"></div>

                    <div id="cart-empty" class="text-center text-muted py-12 d-none">
                        <div class="mb-3">Keranjang kosong. Tambahkan barang yang dibeli customer.</div>
                        <button type="button" class="btn btn-primary js-add-row">
                            <i class="ki-outline ki-plus fs-3"></i> Tambah barang
                        </button>
                    </div>

                    <div id="cart-footer" class="mt-4 d-none">
                        <button type="button" class="btn btn-outline btn-outline-dashed btn-outline-default w-100 js-add-row">
                            <i class="ki-outline ki-plus fs-3"></i> Tambah barang lain
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============================ RINGKASAN PEMBAYARAN ============================ --}}
        <div class="col-xl-4">
            <div class="receipt-sticky">
                <div class="card">
                    <div class="card-body p-7">

                        {{-- Customer (+ tanggal saat edit) --}}
                        <div class="mb-6">
                            <label for="customer-name" class="sec-label">Customer</label>
                            <input type="text" id="customer-name" name="customer_name" class="form-control form-control-solid"
                                   value="{{ old('customer_name', $terjual?->customer_name === 'Umum' ? '' : $terjual?->customer_name) }}"
                                   placeholder="Umum" maxlength="100">
                        </div>

                        @if ($isEdit)
                            <div class="mb-6">
                                <label for="sold-at" class="sec-label">Tanggal transaksi</label>
                                <input type="datetime-local" id="sold-at" name="sold_at" class="form-control form-control-solid"
                                       value="{{ old('sold_at', $terjual->sold_at->format('Y-m-d\TH:i')) }}">
                            </div>
                        @endif

                        {{-- Metode pembayaran (dari Master Data > Metode Pembayaran) --}}
                        <div class="mb-6">
                            <span class="sec-label">Metode pembayaran</span>
                            @if ($paymentMethods->isEmpty())
                                <div class="text-danger fs-7">Belum ada metode pembayaran. Tambahkan di Master Data > Metode Pembayaran.</div>
                            @else
                                <div class="pm-grid">
                                    @foreach ($paymentMethods as $pm)
                                        <div>
                                            <input type="radio" class="btn-check" name="payment_method_id"
                                                   id="pm-{{ $pm->id }}" value="{{ $pm->id }}" @checked((int) $selectedPm === (int) $pm->id)>
                                            <label class="choice pm-tile" for="pm-{{ $pm->id }}">
                                                <i class="ki-outline {{ $pmIcon($pm->name) }} fs-2"></i>
                                                <span class="pm-name">{{ $pm->name }}</span>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="separator separator-dashed my-5"></div>

                        {{-- Subtotal + Diskon --}}
                        <div class="d-flex justify-content-between mb-4">
                            <span class="text-gray-600">Subtotal</span>
                            <span class="num fw-bold text-gray-900" id="sum-subtotal">Rp 0,00</span>
                        </div>

                        <div class="d-flex align-items-center justify-content-between gap-3">
                            <label for="discount-input" class="text-gray-600 fs-7 mb-0">Diskon <span class="text-muted">(opsional)</span></label>
                            <div class="d-flex align-items-center gap-2">
                                <div class="seg-group">
                                    <input type="radio" class="btn-check" name="discount_type" id="dt-nominal" value="nominal" @checked($discType !== 'persen')>
                                    <label class="choice" for="dt-nominal">Rp</label>
                                    <input type="radio" class="btn-check" name="discount_type" id="dt-persen" value="persen" @checked($discType === 'persen')>
                                    <label class="choice" for="dt-persen">%</label>
                                </div>
                                <input type="text" id="discount-input" inputmode="numeric"
                                       class="form-control form-control-solid form-control-sm text-end w-100px"
                                       placeholder="0" value="{{ $discValue }}">
                            </div>
                        </div>
                        <input type="hidden" name="discount_value" id="discount-value" value="{{ $discValue ?: 0 }}">

                        <div class="d-flex justify-content-between mt-3 d-none" id="discount-line">
                            <span class="text-gray-600">Potongan diskon</span>
                            <span class="num fw-semibold text-danger" id="sum-discount">- Rp 0,00</span>
                        </div>

                        {{-- Total --}}
                        <div class="total-box my-6">
                            <div class="sec-label text-primary mb-1">Total yang harus dibayar</div>
                            <div class="amount num text-gray-900" id="sum-total">Rp 0,00</div>
                        </div>

                        {{-- Jumlah bayar --}}
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label for="paid-input" class="sec-label mb-0">Jumlah bayar</label>
                            <span class="fs-8 text-gray-500" id="paid-method">{{ $selectedPmName }}</span>
                        </div>
                        <div class="money-field mb-3">
                            <span class="prefix">Rp</span>
                            <input type="text" id="paid-input" inputmode="numeric" class="form-control fs-2 fw-bold"
                                   placeholder="0" value="{{ $paidValue }}">
                        </div>
                        <input type="hidden" name="paid" id="paid-value" value="{{ $paidValue !== '' ? $paidValue : 0 }}">

                        <div class="quick-grid mb-4" id="quick-paid"></div>

                        {{-- Kembalian / kurang --}}
                        <div class="status-box mb-6" id="status-box">
                            <div class="sec-label mb-0" id="status-label">Kembalian</div>
                            <div class="num fs-2 fw-bold" id="status-value">Rp 0,00</div>
                        </div>

                        <div class="mb-6">
                            <label for="note" class="sec-label">Catatan <span class="text-muted fw-normal text-lowercase">(opsional)</span></label>
                            <textarea id="note" name="note" rows="2" maxlength="500" class="form-control form-control-solid"
                                      placeholder="Mis. titip barang, ambil besok...">{{ old('note', $terjual?->note) }}</textarea>
                        </div>

                        <button type="submit" id="btn-submit" class="btn btn-primary btn-lg w-100" disabled>
                            <span class="indicator-label">{{ $submitLabel }}</span>
                            <span class="indicator-progress">Menyimpan...
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                        <div class="text-center text-muted fs-8 mt-3 min-h-20px" id="submit-hint"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

{{-- Template satu baris barang --}}
<template id="row-template">
    <div class="cart-row" data-row>
        <div class="cart-grid">
            <span class="cart-no js-no g-no">1</span>

            <div class="g-item">
                <select class="form-select form-select-solid js-item" name="items[__i__][item]" data-placeholder="Cari barang atau scan barcode..."></select>
                <div class="js-stock-hint fs-8 mt-2"></div>
                <div class="js-row-error text-danger fs-8 mt-1"></div>
            </div>

            <div class="stepper cart-col g-qty">
                <button type="button" class="btn btn-icon btn-sm btn-active-color-primary js-minus" tabindex="-1"><i class="ki-outline ki-minus fs-3"></i></button>
                <input type="text" inputmode="numeric" class="form-control form-control-sm js-qty" name="items[__i__][qty]" value="1">
                <button type="button" class="btn btn-icon btn-sm btn-active-color-primary js-plus" tabindex="-1"><i class="ki-outline ki-plus fs-3"></i></button>
            </div>

            <div class="text-end cart-col g-price">
                <div class="sec-label mb-1">Harga</div>
                <div class="num fw-semibold text-gray-900 js-price">-</div>
            </div>

            <div class="text-end cart-col g-sub">
                <div class="sec-label mb-1">Subtotal</div>
                <div class="num fw-bold text-gray-900 js-subtotal">Rp 0,00</div>
            </div>


            <button type="button" class="btn btn-icon btn-sm btn-light-danger cart-del g-del js-remove" title="Hapus baris" aria-label="Hapus baris">
                <i class="ki-outline ki-trash fs-5"></i>
            </button>
        </div>
    </div>
</template>

@push('scripts')
<script>
(function () {
    const catalog      = @json($catalog);
    const initialRows  = @json($initialRows);
    const serverErrors = @json($errors->messages());
    const byId         = Object.fromEntries(catalog.map(c => [c.id, c]));

    const $form  = $('#form-penjualan');
    const $rows  = $('#cart-rows');
    const rowTpl = document.getElementById('row-template').innerHTML;
    let nextIndex = 0;
    let lastTotal = 0;   // total terakhir, dipakai pintasan F9 (bayar pas)

    const nf      = new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const nfInt   = new Intl.NumberFormat('id-ID');
    const fmt     = n => 'Rp ' + nf.format(n || 0);      // sama dengan ProfitHarga::harga_formatted
    const digits  = s => parseInt(String(s ?? '').replace(/\D/g, ''), 10) || 0;

    // ---------- dropdown barang (select2) ----------
    function buildOptions($select) {
        $select.append('<option></option>');
        const groups = {};
        catalog.forEach(c => (groups[c.group] = groups[c.group] || []).push(c));

        Object.keys(groups).forEach(label => {
            const $g = $('<optgroup>').attr('label', label);
            groups[label].forEach(c => {
                $('<option>').val(c.id).text(c.label)
                    .prop('disabled', c.stock <= 0 || c.price === null)
                    .appendTo($g);
            });
            $select.append($g);
        });
    }

    function usedElsewhere(id, selfEl) {
        let used = false;
        $rows.find('.js-item').each(function () {
            if (this !== selfEl && this.value === id) used = true;
        });
        return used;
    }

    // barang yang sudah dipilih di baris lain disembunyikan dari daftar (aturan: tidak boleh ganda)
    function makeMatcher(selfEl) {
        const match = (params, data) => {
            if (data.children) {
                const kids = data.children.map(ch => match(params, ch)).filter(Boolean);
                return kids.length ? $.extend({}, data, { children: kids }) : null;
            }
            if (data.id && usedElsewhere(data.id, selfEl)) return null;
            const term = $.trim(params.term || '').toLowerCase();
            if (!term) return data;
            return data.text.toLowerCase().indexOf(term) > -1 ? data : null;
        };
        return match;
    }

    function renderOption(opt) {
        const c = byId[opt.id];
        if (!opt.id || !c) return opt.text;

        const $left = $('<div></div>')
            .append($('<div class="fw-semibold"></div>').text(c.name));
        if (c.variant) $left.append($('<div class="text-muted fs-8"></div>').text(c.variant));

        const $right = $('<div class="text-end"></div>')
            .append($('<div class="fs-8"></div>')
                .addClass(c.stock <= 0 ? 'text-danger' : 'text-muted')
                .text(c.stock <= 0 ? 'Habis' : 'Stok ' + nfInt.format(c.stock)))
            .append($('<div class="fs-8 fw-semibold"></div>')
                .text(c.price === null ? 'Harga belum diatur' : fmt(c.price)));

        return $('<div class="d-flex justify-content-between align-items-center gap-4"></div>').append($left, $right);
    }

    // ---------- baris ----------
    function addRow(row, focus) {
        row = row || {};
        const idx = (row.index !== undefined && row.index !== null) ? row.index : nextIndex;
        nextIndex = Math.max(nextIndex, idx + 1);

        const $row = $(rowTpl.replace(/__i__/g, idx));
        $rows.append($row);

        const $sel = $row.find('.js-item');
        buildOptions($sel);
        $sel.select2({
            width: '100%',
            placeholder: 'Cari barang atau scan barcode...',
            matcher: makeMatcher($sel[0]),
            templateResult: renderOption,
            templateSelection: o => o.text,
        });

        if (row.item && byId[row.item]) $sel.val(row.item).trigger('change');
        $row.find('.js-qty').val(row.qty || 1);

        // pesan error dari server untuk baris ini
        const errs = [serverErrors['items.' + idx + '.item'], serverErrors['items.' + idx + '.qty']].filter(Boolean).flat();
        if (errs.length) {
            $row.addClass('is-invalid-row');
            $row.find('.js-row-error').text(errs.join(' '));
        }

        if (focus) $sel.select2('open');
        recalc();
    }

    function renumber() {
        $rows.children('[data-row]').each(function (i) { $(this).find('.js-no').text(i + 1); });
    }

    // ---------- hitung ----------
    function recalc() {
        let subtotal = 0, count = 0, blankRow = false, qtyBad = false;

        $rows.children('[data-row]').each(function () {
            const $r   = $(this);
            const c    = byId[$r.find('.js-item').val()];
            const $qty = $r.find('.js-qty');
            const qty  = digits($qty.val());
            const $hint = $r.find('.js-stock-hint');

            $r.toggleClass('is-blank', !c);

            if (!c) {
                blankRow = true;
                $r.find('.js-price').text('-');
                $r.find('.js-subtotal').text(fmt(0));
                $hint.removeClass('text-danger text-success').addClass('text-muted')
                     .text('Ketik nama barang atau gunakan pemindai barcode');
                return;
            }

            count++;
            const over = qty > c.stock;
            const bad  = over || qty < 1;
            if (bad) qtyBad = true;

            const line = bad ? 0 : c.price * qty;
            subtotal += line;

            $qty.toggleClass('is-invalid', bad);
            $r.find('.js-price').text(fmt(c.price));
            $r.find('.js-subtotal').text(fmt(line));

            const hintText = over ? 'Melebihi stok, tersisa ' + nfInt.format(c.stock)
                                  : 'Stok tersedia ' + nfInt.format(c.stock) + (c.price_locked ? ' · harga transaksi lama' : '');
            $hint.removeClass('text-muted').toggleClass('text-danger', over).toggleClass('text-success', !over)
                 .html('<span class="bullet bullet-dot ' + (over ? 'bg-danger' : 'bg-success') + ' me-2"></span>' + hintText);
        });

        const rowCount = $rows.children('[data-row]').length;
        $('#cart-empty').toggleClass('d-none', rowCount > 0);
        $('#cart-footer').toggleClass('d-none', rowCount === 0);
        $('#cart-count').text(count + ' macam');
        renumber();

        // diskon
        const type = $('input[name=discount_type]:checked').val();
        let dv = digits($('#discount-input').val());
        if (type === 'persen' && dv > 100) { dv = 100; $('#discount-input').val(100); }
        $('#discount-value').val(dv);

        let discount = type === 'persen' ? Math.round(subtotal * dv / 100) : dv;
        const discountTooBig = discount > subtotal;
        if (discountTooBig) discount = subtotal;

        const total = subtotal - discount;
        lastTotal = total;
        const paid  = digits($('#paid-input').val());
        $('#paid-value').val(paid);

        $('#sum-subtotal').text(fmt(subtotal));
        $('#sum-discount').text('- ' + fmt(discount));
        $('#discount-line').toggleClass('d-none', discount <= 0);
        $('#sum-total').text(fmt(total));

        renderQuickPaid(total);

        // kembalian / kurang
        const diff = paid - total;
        const $box = $('#status-box');
        $box.removeClass('status-ok status-bad');
        if (count === 0 || (paid === 0 && total > 0)) {
            $('#status-label').text('Kembalian');
            $('#status-value').text(fmt(0));
        } else if (diff < 0) {
            $box.addClass('status-bad');
            $('#status-label').text('Kurang');
            $('#status-value').text(fmt(-diff));
        } else {
            $box.addClass('status-ok');
            $('#status-label').text(diff === 0 ? 'Uang pas' : 'Kembalian');
            $('#status-value').text(fmt(diff));
        }

        // tombol simpan
        const reasons = [];
        if (count === 0 && !blankRow)        reasons.push('Tambahkan minimal satu barang.');
        if (blankRow)                        reasons.push('Pilih barang di setiap baris, atau hapus baris yang kosong.');
        if (qtyBad)                          reasons.push('Periksa jumlah: minimal 1 dan tidak melebihi stok.');
        if (discountTooBig)                  reasons.push('Diskon melebihi subtotal.');
        if (!$('input[name=payment_method_id]:checked').length) reasons.push('Pilih metode pembayaran.');
        if (count > 0 && !blankRow && !qtyBad && paid < total)  reasons.push('Jumlah bayar masih kurang dari total.');

        $('#btn-submit').prop('disabled', reasons.length > 0);
        $('#submit-hint').text(reasons[0] || '');
    }

    // pecahan uang yang masuk akal: uang pas, lalu pembulatan ke atas
    function renderQuickPaid(total) {
        const $box = $('#quick-paid').empty();
        if (total <= 0) return;

        const set = new Set([total]);
        [10000, 50000, 100000].forEach(step => set.add(Math.ceil(total / step) * step));

        Array.from(set).sort((a, b) => a - b).slice(0, 4).forEach(amount => {
            $('<button type="button" class="btn btn-sm"></button>')
                .toggleClass('is-exact', amount === total)
                .attr('data-amount', amount)
                .text(amount === total ? 'Uang pas' : nfInt.format(amount))
                .appendTo($box);
        });
    }

    // ---------- event ----------
    $(document).on('click', '.js-add-row', () => addRow({}, true));

    $rows.on('change', '.js-item', function () {
        const $r = $(this).closest('[data-row]');
        $r.removeClass('is-invalid-row').find('.js-row-error').text('');

        // ganti barang: qty ikut dibatasi stok barang baru
        const c = byId[this.value];
        const $q = $r.find('.js-qty');
        if (c && c.stock > 0 && digits($q.val()) > c.stock) $q.val(c.stock);
        recalc();
    });

    $rows.on('input', '.js-qty', function () {
        this.value = this.value.replace(/\D/g, '');
        $(this).closest('[data-row]').removeClass('is-invalid-row').find('.js-row-error').text('');
        recalc();
    });
    $rows.on('blur', '.js-qty', function () { if (digits(this.value) < 1) { this.value = 1; recalc(); } });

    $rows.on('click', '.js-plus, .js-minus', function () {
        const $r = $(this).closest('[data-row]');
        const $q = $r.find('.js-qty');
        const c  = byId[$r.find('.js-item').val()];
        let v = digits($q.val()) + ($(this).hasClass('js-plus') ? 1 : -1);
        v = Math.max(1, v);
        if (c) v = Math.min(v, Math.max(1, c.stock));
        $q.val(v);
        recalc();
    });

    $rows.on('click', '.js-remove', function (e) {
        e.preventDefault();
        const $r = $(this).closest('[data-row]');
        // select2('destroy') melempar error kalau belum terpasang; jangan sampai menggagalkan penghapusan baris
        try { $r.find('.js-item').select2('destroy'); } catch (err) { /* abaikan */ }
        $r.remove();   // seluruh baris (nomor, pilihan barang, jumlah, harga, subtotal) ikut terhapus
        recalc();
    });

    // input uang: tampil dengan pemisah ribuan, yang dikirim angka murni (hidden)
    function bindMoney($el) {
        $el.on('input', function () {
            const v = digits(this.value);
            this.value = v ? nfInt.format(v) : '';
            recalc();
        });
        const v0 = digits($el.val());
        $el.val(v0 ? nfInt.format(v0) : '');
    }
    bindMoney($('#paid-input'));
    bindMoney($('#discount-input'));

    // nama metode pembayaran yang dipilih tampil di samping "Jumlah bayar"
    function syncMethodName() {
        $('#paid-method').text($('input[name=payment_method_id]:checked + label .pm-name').text());
    }

    $('input[name=discount_type]').on('change', recalc);
    $('input[name=payment_method_id]').on('change', function () { syncMethodName(); recalc(); });

    $('#quick-paid').on('click', '[data-amount]', function () {
        $('#paid-input').val(nfInt.format($(this).data('amount')));
        recalc();
    });

    // pintasan keyboard: F2 cari/scan barang, F4 diskon, F9 bayar pas
    document.addEventListener('keydown', function (e) {
        if (e.key === 'F2') {
            e.preventDefault();
            const $blank = $rows.children('[data-row]').filter(function () { return !byId[$(this).find('.js-item').val()]; }).first();
            if ($blank.length) $blank.find('.js-item').select2('open');
            else addRow({}, true);
        } else if (e.key === 'F4') {
            e.preventDefault();
            $('#discount-input').trigger('focus').trigger('select');
        } else if (e.key === 'F9') {
            e.preventDefault();
            if (lastTotal > 0) {
                $('#paid-input').val(nfInt.format(lastTotal)).trigger('focus');
                recalc();
            }
        }
    });

    // Enter di kolom isian tidak boleh langsung menyimpan transaksi
    $form.on('keydown', 'input', e => { if (e.key === 'Enter') e.preventDefault(); });

    $form.on('submit', function () {
        const btn = document.getElementById('btn-submit');
        btn.setAttribute('data-kt-indicator', 'on');
        setTimeout(() => { btn.disabled = true; }, 0);   // cegah klik ganda tanpa menghalangi submit
    });

    // ---------- mulai ----------
    initialRows.forEach(r => addRow(r, false));
    syncMethodName();
    recalc();
})();
</script>
@endpush