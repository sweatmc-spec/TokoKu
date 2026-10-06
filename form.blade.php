@extends('layouts.app')

@section('title', $purchase ? 'Edit Pembelian' : 'Tambah Produk')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css" rel="stylesheet">
    <style>
        /* Select2 (single) mengikuti tema dark/light Bootstrap */
        .select2-container--default .select2-selection--single {
            height: 38px;
            background-color: var(--bs-body-bg);
            border: 1px solid var(--bs-border-color);
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: var(--bs-body-color);
            line-height: 36px;
            padding-left: 12px;
        }
        .select2-container--default .select2-selection--single .select2-selection__placeholder {
            color: var(--bs-secondary-color);
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }
        .select2-container--default.select2-container--disabled .select2-selection--single {
            background-color: var(--bs-secondary-bg);
            cursor: not-allowed;
        }
        .select2-dropdown {
            background-color: var(--bs-body-bg);
            border-color: var(--bs-border-color);
            color: var(--bs-body-color);
        }
        .select2-container--default .select2-search--dropdown .select2-search__field {
            background-color: var(--bs-body-bg);
            color: var(--bs-body-color);
            border-color: var(--bs-border-color);
        }
        .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
            background-color: var(--bs-primary);
            color: #fff;
        }
        .select2-container--default .select2-results__option--selected {
            background-color: var(--bs-tertiary-bg);
        }
        .preview-box strong { font-variant-numeric: tabular-nums; }
        /* Profit per pcs (harga jual) dibedakan jelas dari Harga per pcs (harga beli dari sales) */
        .profit-info { padding: .3rem .75rem; border-left: 3px solid var(--bs-success); border-radius: .35rem; background: color-mix(in srgb, var(--bs-success) 12%, transparent); }
        .profit-info strong, .profit-sub { color: var(--bs-success); }
        /* opsi Select2 Profit Harga: harga di kiri, code di kanan */
        .profit-opt { display: flex; justify-content: space-between; align-items: center; gap: 1rem; }
        .profit-opt-price { font-variant-numeric: tabular-nums; }
        .profit-opt-code { color: var(--bs-secondary-color, #9ca3af); font-size: .85em; letter-spacing: .03em; }
        /* pilihan varian: warna -> ukuran -> bahan -> model */
        .variant-box { border-left: 3px solid var(--bs-primary); padding-left: 1rem; }
    </style>
@endpush

@section('content')
    {{-- ============ Card 1: info pembelian + input barang ============ --}}
    <div class="card mb-5">
        <div class="card-header">
            <h5 class="card-title mb-0">
                {{ $purchase ? 'Edit pembelian ' . $purchase->code : 'Pembelian dari sales' }}
            </h5>
        </div>

        <div class="card-body">
            <div class="alert alert-danger d-none" id="formAlert" role="alert"></div>

            <div class="row g-4">
                <div class="col-md-4">
                    <label for="sales_id" class="form-label">Sales <span class="text-danger">*</span></label>
                    <select id="sales_id" @disabled($purchase)>
                        <option value=""></option>
                        @foreach ($config['sales'] as $s)
                            <option value="{{ $s['id'] }}" @selected($purchase && $purchase->sales_id == $s['id'])>{{ $s['name'] }}</option>
                        @endforeach
                    </select>
                    @if ($purchase)
                        <div class="form-text">Sales tidak bisa diganti. Hapus pembelian ini kalau salah pilih sales.</div>
                    @endif
                    <div class="text-danger small mt-1" data-error-for="sales_id"></div>
                </div>

                <div class="col-md-4">
                    <label for="category_id" class="form-label">Kategori <span class="text-danger">*</span></label>
                    <select id="category_id" class="form-select" disabled>
                        <option value="">Pilih sales dulu</option>
                    </select>
                    <div class="form-text" id="categoryHint"></div>
                </div>

                <div class="col-md-4">
                    <label for="purchase_date" class="form-label">Tanggal pembelian <span class="text-danger">*</span></label>
                    <input type="text" id="purchase_date" class="form-control" autocomplete="off">
                    <div class="text-danger small mt-1" data-error-for="purchase_date"></div>
                </div>
            </div>

            <hr class="my-5">

            <div class="row g-4">
                <div class="col-md-6">
                    <label for="product_name" class="form-label">Produk <span class="text-danger">*</span></label>
                    <select id="product_name">
                        <option value=""></option>
                    </select>
                    <div class="form-text" id="productHint">Pilih sales dan kategori dulu.</div>
                </div>

                <div class="col-md-3">
                    <label for="unit" class="form-label">Unit <span class="text-danger">*</span></label>
                    <select id="unit" class="form-select" disabled>
                        <option value="">-</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="unit_qty" class="form-label">Jumlah <span class="text-danger">*</span></label>
                    <input type="text" inputmode="numeric" id="unit_qty" class="form-control" value="1" autocomplete="off">
                </div>
            </div>

            {{-- Khusus produk berkategori Pakaian: pilih varian berurutan (warna, ukuran, bahan, model) --}}
            <div class="variant-box d-none mt-4" id="variantBox">
                <div class="small text-muted mb-3" id="variantHint"></div>
                <div class="row g-4">
                    <div class="col-md-3 d-none" id="vwrap_color">
                        <label for="v_color" class="form-label">Warna <span class="text-danger">*</span></label>
                        <select id="v_color" class="form-select"></select>
                    </div>
                    <div class="col-md-3 d-none" id="vwrap_size">
                        <label for="v_size" class="form-label">Ukuran <span class="text-danger">*</span></label>
                        <select id="v_size" class="form-select"></select>
                    </div>
                    <div class="col-md-3 d-none" id="vwrap_material">
                        <label for="v_material" class="form-label">Bahan <span class="text-danger">*</span></label>
                        <select id="v_material" class="form-select"></select>
                    </div>
                    <div class="col-md-3 d-none" id="vwrap_style">
                        <label for="v_style" class="form-label">Model <span class="text-danger">*</span></label>
                        <select id="v_style" class="form-select"></select>
                    </div>
                </div>
            </div>

            <div class="row g-4 mt-1">
                <div class="col-md-3 d-none" id="packWrap">
                    <label for="pcs_per_unit" class="form-label">Isi per box (pcs) <span class="text-danger">*</span></label>
                    <input type="text" inputmode="numeric" id="pcs_per_unit" class="form-control" placeholder="Contoh: 50" autocomplete="off">
                </div>

                <div class="col-md-4">
                    <label for="unit_price" class="form-label">
                        <span id="priceLabel">Harga per unit</span> <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="text" inputmode="numeric" id="unit_price" class="form-control" placeholder="0" autocomplete="off">
                    </div>
                </div>

                <div class="col-md-5">
                    <label for="profit_harga" class="form-label">Profit Harga <span class="text-muted small">(harga jual per pcs)</span></label>
                    <select id="profit_harga" class="form-select">
                        <option value=""></option>
                        @foreach ($config['profitHarga'] as $ph)
                            <option value="{{ $ph['id'] }}">{{ \App\Support\Rupiah::format($ph['harga']) }}@if ($ph['code']) {{ $ph['code'] }}@endif</option>
                        @endforeach
                    </select>
                    <div class="form-text">Opsional. Cari berdasarkan harga atau code. Kelola di Master Data &gt; Profit Harga.</div>
                </div>
            </div>

            <div class="preview-box small d-flex flex-wrap align-items-center gap-4 mt-4">
                <div>Total pcs: <strong id="pvPcs">0 pcs</strong></div>
                <div>Harga per pcs <span class="text-muted">(beli dari sales)</span>: <strong id="pvPerPcs">Rp 0,00</strong></div>
                <div>Subtotal: <strong id="pvSubtotal">Rp 0,00</strong></div>
                <div class="profit-info d-none" id="pvProfitWrap">
                    Profit per pcs <span class="text-muted">(harga jual)</span>: <strong id="pvProfit">Rp 0,00</strong><span class="text-muted" id="pvProfitCode"></span>
                </div>
            </div>

            <div class="alert alert-warning d-none mt-4 mb-0" id="itemError" role="alert"></div>

            <div class="d-flex gap-2 mt-4">
                <button type="button" class="btn btn-primary" id="btnAddItem">Tambah</button>
                <button type="button" class="btn btn-outline-secondary d-none" id="btnCancelEdit">Batal ubah</button>
            </div>
        </div>
    </div>

    {{-- ============ Card 2: daftar barang + total ============ --}}
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Daftar barang yang dibeli</h5>
        </div>

        <div class="card-body">
            <div class="text-center text-muted py-8" id="emptyState">
                Belum ada barang. Isi form di atas lalu klik "Tambah".
            </div>

            <div class="table-responsive d-none" id="itemsWrap">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th style="width:150px">Aksi</th>
                            <th>Produk</th>
                            <th>Kategori</th>
                            <th>Jumlah</th>
                            <th class="text-end">Harga / unit</th>
                            <th class="text-end">Total pcs</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody"></tbody>
                </table>
            </div>

            <div class="row g-4 mt-2">
                <div class="col-md-7">
                    <label for="note" class="form-label">Catatan</label>
                    <textarea id="note" class="form-control" rows="3" maxlength="500" placeholder="Tuliskan catatan...">{{ $purchase?->note }}</textarea>
                    <div class="text-danger small mt-1" data-error-for="note"></div>
                </div>

                <div class="col-md-5">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total barang</span>
                        <strong id="grandPcs">0 pcs</strong>
                    </div>
                    <div class="d-flex justify-content-between fs-4">
                        <span>Total pembelian</span>
                        <strong id="grandTotal">Rp 0</strong>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-center gap-3 mt-8">
                <a href="{{ $purchase ? route('purchases.show', $purchase) : route('purchases.index') }}"
                   class="btn btn-outline-secondary" id="btnBack">Batal</a>
                <button type="button" class="btn btn-primary" id="btnSubmit" data-loading-text="Menyimpan...">
                    {{ $purchase ? 'Simpan perubahan' : 'Simpan pembelian' }}
                </button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    {{-- Hapus 2 baris flatpickr ini kalau layout sudah memuat flatpickr --}}
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/id.js"></script>

    <script>
        const APP  = @json($config);
        const CSRF = '{{ csrf_token() }}';
    </script>

    @verbatim
    <script>
    $(function () {
        const spinner = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>';
        const nf  = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 });          // angka biasa & isi kolom input
        // Format Rupiah tunggal: "Rp 120.000,00" (sama dengan App\Support\Rupiah di PHP)
        const nfMoney = new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const rp  = (n) => 'Rp ' + nfMoney.format(n || 0);
        const toInt = (v) => parseInt(String(v == null ? '' : v).replace(/\D/g, ''), 10) || 0;
        const cleanName = (s) => String(s || '').replace(/\s+/g, ' ').trim();
        const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

        // ---------- data dari server ----------
        const salesMap = {};
        APP.sales.forEach((s) => { salesMap[s.id] = s; });

        const unitMap = {};
        APP.units.forEach((u) => { unitMap[u.id] = u; });

        const profitMap = {};
        (APP.profitHarga || []).forEach((p) => { profitMap[p.id] = p; });


        // ---------- elemen ----------
        const $sales = $('#sales_id'), $category = $('#category_id'), $product = $('#product_name');
        const $unit = $('#unit'), $qty = $('#unit_qty'), $pack = $('#pcs_per_unit'), $price = $('#unit_price');
        const $profit = $('#profit_harga');

        // ---------- state ----------
        let items = APP.items.slice();
        let editing = null;                       // { item, index } saat sebuah baris sedang diubah
        let currentSales = APP.salesId ? String(APP.salesId) : '';
        let submitting = false;
        let productVariants = [];                 // varian milik produk terpilih
        let variantMode = false;                  // kategori terpilih memakai varian (Pakaian)?
        let currentVariant = null;                // varian yang sudah terpilih lengkap

        // ---------- plugin ----------
        $sales.select2({ width: '100%', placeholder: 'Pilih sales' });

        $product.select2({
            width: '100%',
            placeholder: 'Pilih produk',
            allowClear: true
        });

        // ---------- Profit Harga (Select2): cari lewat harga ATAU code ----------
        const digitsOf = (v) => String(v == null ? '' : v).split(',')[0].replace(/\D/g, '');

        function profitMatcher(params, data) {
            const term = String(params.term || '').trim();
            if (term === '') return data;
            if (!data.id) return null;

            const p = profitMap[data.id];
            if (!p) return null;

            const lower = term.toLowerCase();
            const digits = digitsOf(term);

            if (p.code && p.code.toLowerCase().indexOf(lower) !== -1) return data;                    // code: "xq"
            if (digits !== '' && String(p.harga).indexOf(digits) !== -1) return data;                 // harga: "120", "120000", "120.000", "Rp 120.000,00"
            if (rp(p.harga).toLowerCase().indexOf(lower) !== -1) return data;                         // cocok dengan teks yang terlihat
            return null;
        }

        // harga di kiri (format Rupiah), code di kanan; tanpa code: hanya harga
        function profitTemplate(state) {
            if (!state.id) return state.text;                         // placeholder
            const p = profitMap[state.id];
            if (!p) return state.text;

            const $row = $('<span class="profit-opt"></span>');
            $row.append($('<span class="profit-opt-price"></span>').text(rp(p.harga)));
            if (p.code) $row.append($('<span class="profit-opt-code"></span>').text(p.code));
            return $row;
        }

        $profit.select2({
            width: '100%',
            placeholder: 'Pilih Profit Harga',
            allowClear: true,
            minimumResultsForSearch: 0,                                // kolom pencarian selalu tampil
            matcher: profitMatcher,
            templateResult: profitTemplate,
            templateSelection: profitTemplate,
            language: { noResults: () => 'Profit Harga tidak ditemukan' }
        });

        flatpickr('#purchase_date', {
            locale: (window.flatpickr && flatpickr.l10ns && flatpickr.l10ns.id) ? 'id' : 'default',
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd/m/Y',
            altInputClass: 'form-control',
            defaultDate: APP.date || new Date(),
            disableMobile: true
        });

        // ---------- helpers satuan ----------
        function findCategory(id) {
            const s = salesMap[currentSales];
            if (!s) return null;
            return s.categories.find((c) => String(c.id) === String(id)) || null;
        }

        // Produk yang dijual sales terpilih pada kategori terpilih (dari Master Data > Produk Sales)
        function productsFor(catId) {
            return APP.products.filter((p) =>
                String(p.c) === String(catId) &&
                p.s.map(String).indexOf(String(currentSales)) !== -1);
        }

        function unitsForCategory(catId) {
            return APP.units.filter((u) => u.category_ids.map(String).indexOf(String(catId)) !== -1);
        }

        function unitOptionLabel(u) {
            if (u.pcs_per_unit === 1) return u.name;
            return u.pcs_per_unit ? u.name + ' (' + u.pcs_per_unit + ' pcs)' : u.name + ' (isi manual)';
        }

        function ppu(unitId, pack) {
            const u = unitMap[unitId];
            if (!u) return 0;
            return u.pcs_per_unit ? u.pcs_per_unit : toInt(pack);
        }

        function ensureOption($sel, value, text) {
            const exists = $sel.find('option').filter(function () { return this.value === String(value); }).length;
            if (!exists) $sel.append(new Option(text, value));
        }

        // ---------- kategori / satuan / produk mengikuti sales ----------
        function buildCategoryOptions() {
            const s = salesMap[currentSales];
            $category.empty();

            if (!s) {
                $category.append('<option value="">Pilih sales dulu</option>').prop('disabled', true);
                $('#categoryHint').text('');
                onCategoryChange();
                return;
            }

            const cats = s.categories;

            if (cats.length === 0) {
                $category.append('<option value="">Sales belum punya kategori</option>').prop('disabled', true);
                $('#categoryHint').text('Atur kategori sales di Master Data > Nama Sales.');
            } else if (cats.length === 1) {
                $category.append(new Option(cats[0].name, cats[0].id, true, true)).prop('disabled', true);
                $('#categoryHint').text('Terisi otomatis dari kategori sales.');
            } else {
                $category.append('<option value="">Pilih kategori</option>');
                cats.forEach((c) => $category.append(new Option(c.name, c.id)));
                $category.prop('disabled', false);
                $('#categoryHint').text('Sales ini menjual beberapa kategori. Pilih kategori barang.');
            }

            onCategoryChange();
        }

        // ---------- varian: warna -> ukuran -> bahan -> model ----------
        const VSTEPS = ['color', 'size', 'material', 'style'];
        const NONE = '__none__';                                         // nilai kosong (varian tanpa bahan / model)
        const variantCats = (APP.variantCategories || []).map(String);
        const vOrder = APP.variantOrder || {};
        const vKey = { color: 'co', size: 'si', material: 'ma', style: 'st' };
        const stepLabel = { color: 'Warna', size: 'Ukuran', material: 'Bahan', style: 'Model' };
        const noneLabel = { material: 'Tanpa bahan', style: 'Tanpa model' };
        const valOf = (v, step) => (v[vKey[step]] === null || v[vKey[step]] === undefined) ? NONE : v[vKey[step]];

        function variantLabelOf(v) {
            return VSTEPS.map((s) => valOf(v, s)).filter((x) => x !== NONE).join(' / ');
        }

        // urutan mengikuti Master Data (mis. S, M, L); "tanpa ..." selalu paling akhir
        function orderValues(step, values) {
            const order = (vOrder[step] || []).map((x) => String(x).toLowerCase());
            return values.slice().sort((a, b) => {
                if (a === NONE) return 1;
                if (b === NONE) return -1;
                const ia = order.indexOf(a.toLowerCase()), ib = order.indexOf(b.toLowerCase());
                if (ia !== -1 && ib !== -1) return ia - ib;
                if (ia !== -1) return -1;
                if (ib !== -1) return 1;
                return a.localeCompare(b);
            });
        }

        // Tampilkan langkah satu per satu. Langkah berikut hanya muncul setelah langkah sebelumnya dipilih,
        // pilihannya menyempit sesuai varian yang ada, dan langkah yang tidak punya pilihan dilewati.
        function refreshVariantSteps(preset) {
            if (!variantMode || !productVariants.length) {
                VSTEPS.forEach((s) => { $('#vwrap_' + s).addClass('d-none'); $('#v_' + s).empty(); });
                currentVariant = null;
                return;
            }

            const chosen = {};
            let blocked = false;

            VSTEPS.forEach((step) => {
                const $wrap = $('#vwrap_' + step), $sel = $('#v_' + step);
                const prev = (preset && preset[step] !== undefined) ? preset[step] : $sel.val();

                if (blocked) { $wrap.addClass('d-none'); $sel.empty(); return; }

                const cands = productVariants.filter((v) => Object.keys(chosen).every((s) => valOf(v, s) === chosen[s]));
                const values = [];
                cands.forEach((v) => { const val = valOf(v, step); if (values.indexOf(val) === -1) values.push(val); });

                // semua varian tidak punya bahan / model: langkah ini dilewati
                if (values.length === 1 && values[0] === NONE) {
                    chosen[step] = NONE;
                    $wrap.addClass('d-none');
                    $sel.empty();
                    return;
                }

                const ordered = orderValues(step, values);
                $sel.empty();
                if (ordered.length > 1) $sel.append('<option value="">Pilih ' + stepLabel[step].toLowerCase() + '</option>');
                ordered.forEach((val) => $sel.append(new Option(val === NONE ? (noneLabel[step] || '-') : val, val)));

                let pick = (prev && ordered.indexOf(prev) !== -1) ? prev : '';
                if (!pick && ordered.length === 1) pick = ordered[0];          // satu-satunya pilihan: terpilih otomatis
                $sel.val(pick);
                $wrap.removeClass('d-none');

                if (pick === '') { blocked = true; } else { chosen[step] = pick; }
            });

            currentVariant = null;
            if (!blocked) {
                const matches = productVariants.filter((v) => VSTEPS.every((s) => valOf(v, s) === chosen[s]));
                currentVariant = matches.length === 1 ? matches[0] : null;
            }
        }

        function onProductChange() {
            const pid = $product.val();
            const catId = $category.val();
            variantMode = !!catId && variantCats.indexOf(String(catId)) !== -1;

            const p = pid ? APP.products.find((x) => String(x.id) === String(pid)) : null;
            productVariants = (variantMode && p) ? (p.v || []) : [];

            VSTEPS.forEach((s) => { $('#v_' + s).empty(); });
            refreshVariantSteps();

            if (!variantMode) {
                $('#variantBox').addClass('d-none');
                $('#variantHint').text('');
                return;
            }

            $('#variantBox').removeClass('d-none');
            $('#variantHint').text(!p
                ? 'Pilih produk untuk memilih variannya.'
                : (productVariants.length
                    ? 'Pilih varian: warna, lalu ukuran, bahan, dan model.'
                    : 'Produk ini belum punya varian. Buat dulu di Master Data > Produk Sales (klik nama produknya).'));
        }

        $product.on('change', onProductChange);
        // Mengganti satu langkah mengosongkan langkah-langkah sesudahnya, jadi pilihan selalu berurutan
        $('#v_color, #v_size, #v_material, #v_style').on('change', function () {
            const idx = VSTEPS.indexOf(this.id.replace('v_', ''));
            const reset = {};
            VSTEPS.slice(idx + 1).forEach((st) => { reset[st] = ''; });
            refreshVariantSteps(reset);
        });

        function onCategoryChange() {
            const catId = $category.val();

            // satuan sesuai kategori
            const list = catId ? unitsForCategory(catId) : [];
            $unit.empty();
            if (list.length) {
                list.forEach((u) => $unit.append(new Option(unitOptionLabel(u), u.id)));
                $unit.prop('disabled', false);
            } else {
                $unit.append('<option value="">' + (catId ? 'Belum ada unit untuk kategori ini' : '-') + '</option>').prop('disabled', true);
            }

            // produk milik sales ini pada kategori tsb
            const products = catId ? productsFor(catId) : [];
            $product.empty().append('<option value=""></option>');
            products.forEach((p) => $product.append(new Option(p.n, p.id)));
            $product.val(null).trigger('change');
            $('#productHint').text(!catId
                ? 'Pilih sales dan kategori dulu.'
                : (products.length ? '' : 'Belum ada produk untuk sales dan kategori ini. Tambahkan di Master Data > Produk Sales.'));

            onUnitChange();
        }

        function onUnitChange() {
            const u = unitMap[$unit.val()];
            const manual = !!u && !u.pcs_per_unit;

            $('#packWrap').toggleClass('d-none', !manual);
            if (!manual) $pack.val('');

            $('#priceLabel').text('Harga per ' + (u ? u.name.toLowerCase() : 'unit'));
            updatePreview();
        }

        function updatePreview() {
            const p = ppu($unit.val(), $pack.val());
            const q = toInt($qty.val());
            const price = toInt($price.val());

            $('#pvPcs').text(nf.format(q * p) + ' pcs');
            $('#pvPerPcs').text(p ? rp(price / p) : rp(0));
            $('#pvSubtotal').text(rp(q * price));

            // Profit per pcs = Profit Harga terpilih (harga jual per pcs).
            // Tampil terpisah (hijau) dari Harga per pcs yang merupakan harga beli dari sales.
            const profit = profitMap[$profit.val()];
            $('#pvProfitWrap').toggleClass('d-none', !profit);
            if (profit) {
                $('#pvProfit').text(rp(profit.harga));
                $('#pvProfitCode').text(profit.code ? ' (' + profit.code + ')' : '');
            }
        }

        // ---------- input angka ----------
        $qty.add($pack).on('input', function () {
            this.value = this.value.replace(/\D/g, '');
            updatePreview();
        });

        $price.on('input', function () {
            const n = toInt(this.value);
            this.value = n ? nf.format(n) : '';
            updatePreview();
        });

        $category.on('change', onCategoryChange);
        $profit.on('change', updatePreview);
        $unit.on('change', onUnitChange);

        $sales.on('change', function () {
            const next = this.value;
            if (next === currentSales) return;

            if (items.length && !window.confirm('Mengganti sales akan mengosongkan daftar barang. Lanjutkan?')) {
                $sales.val(currentSales).trigger('change.select2');
                return;
            }

            items = [];
            editing = null;
            resetEditUi();
            currentSales = next;
            buildCategoryOptions();
            renderItems();
        });

        // ---------- tambah / perbarui barang ----------
        function showItemError(msg) { $('#itemError').removeClass('d-none').text(msg); }
        function hideItemError() { $('#itemError').addClass('d-none').text(''); }

        function validateItemInput() {
            if (!currentSales) return 'Pilih sales terlebih dahulu.';
            if (!$category.val()) return 'Pilih kategori barang.';
            if (!$product.val()) return 'Pilih produk.';
            if (variantMode) {
                if (!productVariants.length) return 'Produk ini belum punya varian. Buat dulu di Master Data > Produk Sales.';
                if (!currentVariant) return 'Pilih varian sampai lengkap (warna, ukuran, bahan, model).';
            }
            if (!$unit.val()) return 'Pilih unit.';
            if (ppu($unit.val(), $pack.val()) < 1) return 'Isi jumlah pcs per box.';
            if (toInt($qty.val()) < 1) return 'Jumlah minimal 1.';
            if (toInt($price.val()) < 1) return 'Isi harga barang.';
            return null;
        }

        function resetItemInputs() {
            $product.val(null).trigger('change');
            $profit.val(null).trigger('change');
            $qty.val('1');
            $price.val('');
            updatePreview();
        }

        function resetEditUi() {
            $('#btnAddItem').text('Tambah');
            $('#btnCancelEdit').addClass('d-none');
        }

        $('#btnAddItem').on('click', function () {
            const err = validateItemInput();
            if (err) { showItemError(err); return; }
            hideItemError();

            const cat = findCategory($category.val()) || (editing ? {
                id: editing.item.category_id, name: editing.item.category_name, slug: editing.item.category_slug
            } : null);
            const key = $unit.val();

            const item = {
                id: editing ? editing.item.id : null,
                received: editing ? editing.item.received : false,
                category_id: cat.id,
                category_name: cat.name,
                category_slug: cat.slug,
                product_id: parseInt($product.val(), 10),
                product_name: cleanName($product.find('option:selected').text()),
                product_variant_id: currentVariant ? currentVariant.id : null,
                variant_label: currentVariant ? variantLabelOf(currentVariant) : null,
                profit_harga_id: toInt($profit.val()) || null,
                unit_id: parseInt(key, 10),
                unit_name: unitMap[key].name,
                unit_qty: toInt($qty.val()),
                unit_price: toInt($price.val()),
                pcs_per_unit: ppu(key, $pack.val())
            };

            if (editing) {
                items.splice(editing.index, 0, item);
                editing = null;
                resetEditUi();
            } else {
                items.push(item);
            }

            resetItemInputs();
            renderItems();
        });

        $('#btnCancelEdit').on('click', function () {
            if (!editing) return;
            items.splice(editing.index, 0, editing.item);
            editing = null;
            resetEditUi();
            hideItemError();
            resetItemInputs();
            renderItems();
        });

        function startEdit(i) {
            if (editing) return;
            const it = items[i];
            editing = { item: it, index: i };
            items.splice(i, 1);

            ensureOption($category, it.category_id, it.category_name);
            $category.val(String(it.category_id));
            onCategoryChange();

            if (it.unit_id) {
                ensureOption($unit, it.unit_id, unitMap[it.unit_id] ? unitOptionLabel(unitMap[it.unit_id]) : it.unit_name);
                $unit.val(String(it.unit_id));
            }

            ensureOption($product, it.product_id, it.product_name);
            $product.val(String(it.product_id)).trigger('change');

            if (variantMode && it.product_variant_id) {
                const ev = productVariants.find((v) => String(v.id) === String(it.product_variant_id));
                if (ev) {
                    refreshVariantSteps({
                        color: valOf(ev, 'color'), size: valOf(ev, 'size'),
                        material: valOf(ev, 'material'), style: valOf(ev, 'style')
                    });
                }
            }

            $qty.val(it.unit_qty);
            $price.val(nf.format(it.unit_price));
            $profit.val(it.profit_harga_id ? String(it.profit_harga_id) : '').trigger('change');
            onUnitChange();

            const u = unitMap[it.unit_id];
            if (!u || !u.pcs_per_unit) $pack.val(it.pcs_per_unit);
            updatePreview();

            $('#btnAddItem').text('Perbarui');
            $('#btnCancelEdit').removeClass('d-none');
            hideItemError();
            renderItems();
            $('html, body').animate({ scrollTop: 0 }, 200);
        }

        // ---------- tabel barang ----------
        function profitSubline(it) {
            const p = it.profit_harga_id ? profitMap[it.profit_harga_id] : null;
            if (!p) return '';
            return '<div class="small fw-normal profit-sub">Profit per pcs: ' + esc(rp(p.harga)) +
                (p.code ? ' (' + esc(p.code) + ')' : '') + '</div>';
        }

        function unitLabelOf(it) {
            return it.unit_name || '-';
        }

        function renderItems() {
            const $body = $('#itemsBody').empty();
            let totalPcs = 0, total = 0;
            const lock = editing ? ' disabled' : '';

            items.forEach((it, i) => {
                const pcs = it.unit_qty * it.pcs_per_unit;
                const sub = it.unit_qty * it.unit_price;
                totalPcs += pcs;
                total += sub;

                $body.append(
                    '<tr>' +
                    '<td class="text-nowrap">' +
                        '<button type="button" class="btn btn-sm btn-outline-warning btn-edit" data-index="' + i + '"' + lock + '>Ubah</button> ' +
                        '<button type="button" class="btn btn-sm btn-outline-danger btn-remove" data-index="' + i + '"' + lock + '>Hapus</button>' +
                    '</td>' +
                    '<td class="fw-semibold">' + esc(it.product_name) +
                        (it.variant_label ? '<div class="small text-muted fw-normal">' + esc(it.variant_label) + '</div>' : '') +
                        profitSubline(it) +
                        (it.received ? ' <span class="badge bg-success">Sudah datang</span>' : '') + '</td>' +
                    '<td>' + esc(it.category_name) + '</td>' +
                    '<td>' + nf.format(it.unit_qty) + ' ' + esc(unitLabelOf(it)) +
                        (it.pcs_per_unit > 1 ? ' <small class="text-muted">(isi ' + it.pcs_per_unit + ' pcs)</small>' : '') + '</td>' +
                    '<td class="text-end">' + rp(it.unit_price) + '</td>' +
                    '<td class="text-end">' + nf.format(pcs) + '</td>' +
                    '<td class="text-end fw-semibold">' + rp(sub) + '</td>' +
                    '</tr>'
                );
            });

            $('#itemsWrap').toggleClass('d-none', items.length === 0);
            $('#emptyState').toggleClass('d-none', items.length !== 0);
            $('#grandPcs').text(nf.format(totalPcs) + ' pcs');
            $('#grandTotal').text(rp(total));
        }

        $('#itemsBody').on('click', '.btn-edit', function () { startEdit(parseInt($(this).data('index'), 10)); });
        $('#itemsBody').on('click', '.btn-remove', function () {
            if (editing) return;
            items.splice(parseInt($(this).data('index'), 10), 1);
            renderItems();
        });

        // ---------- simpan ----------
        const $submit = $('#btnSubmit');

        function setSubmitting(on) {
            submitting = on;
            if (on) {
                $submit.data('original', $submit.html())
                    .prop('disabled', true)
                    .html(spinner + $submit.data('loading-text'));
                $('#btnBack').addClass('disabled').attr('aria-disabled', 'true');
            } else {
                $submit.prop('disabled', false).html($submit.data('original'));
                $('#btnBack').removeClass('disabled').removeAttr('aria-disabled');
            }
        }

        function clearErrors() {
            $('#formAlert').addClass('d-none').empty();
            $('[data-error-for]').text('');
        }

        function showFormAlert(lines) {
            const html = lines.length === 1
                ? esc(lines[0])
                : '<ul class="mb-0 ps-4">' + lines.map((l) => '<li>' + esc(l) + '</li>').join('') + '</ul>';
            $('#formAlert').removeClass('d-none').html(html);
            $('html, body').animate({ scrollTop: 0 }, 200);
        }

        function handleError(xhr) {
            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                const lines = [];
                $.each(xhr.responseJSON.errors, function (key, msgs) {
                    const m = key.match(/^items\.(\d+)\.(.+)$/);
                    if (m) {
                        lines.push('Barang ke-' + (parseInt(m[1], 10) + 1) + ': ' + msgs[0]);
                        return;
                    }
                    const $slot = $('[data-error-for="' + key + '"]');
                    if ($slot.length) { $slot.text(msgs[0]); } else { lines.push(msgs[0]); }
                });
                if (lines.length) showFormAlert(lines);
                return;
            }

            const msg = (xhr.responseJSON && xhr.responseJSON.message)
                ? xhr.responseJSON.message
                : 'Terjadi kesalahan. Silakan coba lagi.';
            showFormAlert([msg]);
        }

        $submit.on('click', function () {
            if (submitting) return;
            clearErrors();

            if (editing) { showFormAlert(['Selesaikan dulu barang yang sedang diubah: klik "Perbarui" atau "Batal ubah".']); return; }
            if (!APP.isEdit && !currentSales) { showFormAlert(['Pilih sales terlebih dahulu.']); return; }
            if (!items.length) { showFormAlert(['Tambahkan minimal satu barang.']); return; }

            const payload = {
                purchase_date: $('#purchase_date').val(),
                note: $('#note').val(),
                items: items.map((it) => ({
                    id: it.id,
                    category_id: it.category_id,
                    product_id: it.product_id,
                    product_variant_id: it.product_variant_id || null,
                    profit_harga_id: it.profit_harga_id || null,
                    unit_id: it.unit_id,
                    unit_qty: it.unit_qty,
                    unit_price: it.unit_price,
                    pcs_per_unit: it.pcs_per_unit
                }))
            };
            if (!APP.isEdit) payload.sales_id = currentSales;

            setSubmitting(true);

            $.ajax({
                url: APP.submitUrl,
                method: APP.method,
                contentType: 'application/json',
                dataType: 'json',
                headers: { 'X-CSRF-TOKEN': CSRF },
                data: JSON.stringify(payload)
            }).done(function (res) {
                // tombol tetap terkunci sampai halaman pindah
                $submit.html(spinner + 'Berhasil, mengalihkan...');
                window.location.href = res.redirect;
            }).fail(function (xhr) {
                setSubmitting(false);
                handleError(xhr);
            });
        });

        // ---------- init ----------
        buildCategoryOptions();
        renderItems();
    });
    </script>
    @endverbatim
@endpush
