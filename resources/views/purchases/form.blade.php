@extends('layouts.app')

@section('title', $purchase ? 'Edit Pembelian' : 'Tambah Produk')

@push('styles')
    <style>
        /* Select2 mengikuti tema dark/light Metronic */
        body .select2-container--default .select2-selection--single {
            height: 44px;
            background-color: var(--bs-body-bg);
            border: 1px solid var(--bs-border-color);
            border-radius: .475rem;
        }
        body .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: var(--bs-body-color);
            line-height: 42px;
            padding-left: 14px;
        }
        body .select2-container--default .select2-selection--single .select2-selection__placeholder { color: var(--bs-secondary-color); }
        body .select2-container--default .select2-selection--single .select2-selection__arrow { height: 42px; right: 6px; }
        body .select2-container--default.select2-container--disabled .select2-selection--single {
            background-color: var(--bs-secondary-bg);
            cursor: not-allowed;
        }
        body .select2-dropdown { background-color: var(--bs-body-bg); border-color: var(--bs-border-color); color: var(--bs-body-color); }
        body .select2-container--default .select2-search--dropdown .select2-search__field {
            background-color: var(--bs-body-bg); color: var(--bs-body-color); border-color: var(--bs-border-color);
        }
        body .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable { background-color: var(--bs-primary); color: #fff; }
        body .select2-container--default .select2-results__option--selected { background-color: var(--bs-tertiary-bg); }
        #purchase_date + .form-control, #unit_qty, .form-select, .input-group .form-control { min-height: 44px; }
        .stat-value { font-variant-numeric: tabular-nums; }
        .profit-info { padding: .3rem .75rem; border-left: 3px solid var(--bs-success); border-radius: .35rem; background: color-mix(in srgb, var(--bs-success) 12%, transparent); }
        .profit-info strong, .profit-sub { color: var(--bs-success); }
        .profit-opt { display: flex; justify-content: space-between; align-items: center; gap: 1rem; }
        .profit-opt-price { font-variant-numeric: tabular-nums; }
        .profit-opt-code { color: var(--bs-secondary-color, #9ca3af); font-size: .85em; letter-spacing: .03em; }
        .variant-box { border-left: 3px solid var(--bs-primary); padding-left: 1rem; }
    </style>
@endpush

@section('content')
    {{-- ============ Card 1: info pembelian + input barang ============ --}}
    <div class="card mb-6">
        <div class="card-header">
            <div class="d-flex align-items-center gap-4 py-5">
                <i class="ki-outline ki-notepad-edit fs-2x text-primary"></i>
                <div>
                    <h3 class="fw-bold mb-0">{{ $purchase ? 'Edit pembelian' : 'Pembelian dari sales' }}</h3>
                    <div class="text-muted fs-7">Masukkan detail produk yang dipasok langsung dari sales supplier</div>
                </div>
            </div>
            @if ($purchase)
                <div class="d-flex align-items-center">
                    <span class="badge badge-light-primary fs-7">ID: {{ $purchase->code }}</span>
                </div>
            @endif
        </div>

        <div class="card-body">
            <div class="alert alert-danger d-none" id="formAlert" role="alert"></div>

            <div class="row g-5">
                <div class="col-md-4">
                    <label for="sales_id" class="form-label required">Sales</label>
                    <select id="sales_id" class="w-100" @disabled($purchase)>
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
                    <label class="form-label required">Kategori</label>
                    <select id="category_id" class="d-none" disabled>
                        <option value="">Otomatis dari produk</option>
                    </select>
                    <div id="categoryChips" class="form-control d-flex flex-wrap align-items-center gap-2 bg-body-secondary" style="min-height:44px">
                        <span class="text-muted">Pilih sales dulu</span>
                    </div>
                    <div class="form-text" id="categoryHint"></div>
                </div>

                <div class="col-md-4">
                    <label for="purchase_date" class="form-label required">Tanggal pembelian</label>
                    <input type="text" id="purchase_date" class="form-control" placeholder="dd/mm/yyyy" autocomplete="off">
                    <div class="text-danger small mt-1" data-error-for="purchase_date"></div>
                </div>
            </div>

            <div class="separator separator-dashed my-6"></div>

            <div class="row g-5">
                <div class="col-md-4">
                    <label for="product_name" class="form-label required">Produk</label>
                    <select id="product_name" class="w-100">
                        <option value=""></option>
                    </select>
                    <div class="form-text" id="productHint">Pilih sales dulu.</div>
                </div>

                <div class="col-md-4">
                    <label for="unit" class="form-label required">Unit / satuan kemasan</label>
                    <select id="unit" class="form-select" disabled>
                        <option value="">-</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="unit_qty" class="form-label required">Jumlah pesan</label>
                    <div class="input-group">
                        <button type="button" class="btn btn-light" id="qtyMinus" aria-label="Kurangi"><i class="ki-outline ki-minus fs-4 p-0"></i></button>
                        <input type="text" inputmode="numeric" id="unit_qty" class="form-control text-center" value="1" autocomplete="off">
                        <button type="button" class="btn btn-light" id="qtyPlus" aria-label="Tambah"><i class="ki-outline ki-plus fs-4 p-0"></i></button>
                    </div>
                </div>
            </div>

            {{-- Khusus produk berkategori Pakaian: varian dipilih berurutan (warna, ukuran, bahan, model) --}}
            <div class="variant-box d-none mt-5" id="variantBox">
                <div class="text-muted fs-7 mb-3" id="variantHint"></div>
                <div class="row g-5">
                    <div class="col-md-3 d-none" id="vwrap_color">
                        <label for="v_color" class="form-label required">Warna</label>
                        <select id="v_color" class="form-select"></select>
                    </div>
                    <div class="col-md-3 d-none" id="vwrap_size">
                        <label for="v_size" class="form-label required">Ukuran</label>
                        <select id="v_size" class="form-select"></select>
                    </div>
                    <div class="col-md-3 d-none" id="vwrap_material">
                        <label for="v_material" class="form-label required">Bahan</label>
                        <select id="v_material" class="form-select"></select>
                    </div>
                    <div class="col-md-3 d-none" id="vwrap_style">
                        <label for="v_style" class="form-label required">Model</label>
                        <select id="v_style" class="form-select"></select>
                    </div>
                </div>
            </div>

            <div class="row g-5 mt-0">
                <div class="col-lg-5">
                    <div class="mb-5 d-none" id="packWrap">
                        <label for="pcs_per_unit" class="form-label required">Isi per box (pcs)</label>
                        <input type="text" inputmode="numeric" id="pcs_per_unit" class="form-control" placeholder="Contoh: 50" autocomplete="off">
                    </div>

                    <label for="unit_price" class="form-label required">
                        <span id="priceLabel">Harga per unit</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="text" inputmode="numeric" id="unit_price" class="form-control" placeholder="0" autocomplete="off">
                    </div>

                    <div class="mt-5">
                        <label for="profit_harga" class="form-label">Profit Harga <span class="text-muted fs-8">(harga jual per pcs)</span></label>
                        <select id="profit_harga" class="w-100">
                            <option value=""></option>
                            @foreach ($config['profitHarga'] as $ph)
                                <option value="{{ $ph['id'] }}">{{ $ph['harga_formatted'] }}@if ($ph['code']) {{ $ph['code'] }}@endif</option>
                            @endforeach
                        </select>
                        <div class="form-text">Opsional. Cari berdasarkan harga atau code. Kelola di Master Data &gt; Profit Harga.</div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="bg-light rounded p-5 h-100">
                        <div class="row g-4">
                            <div class="col-4">
                                <div class="text-muted fs-8 mb-1">Kuantitas total</div>
                                <div class="fw-bold fs-5 stat-value" id="pvPcs">0 pcs</div>
                            </div>
                            <div class="col-4">
                                <div class="text-muted fs-8 mb-1">Harga per pcs</div>
                                <div class="fw-bold fs-5 stat-value" id="pvPerPcs">Rp 0</div>
                            </div>
                            <div class="col-4">
                                <div class="text-muted fs-8 mb-1">Subtotal item</div>
                                <div class="fw-bold fs-5 text-primary stat-value" id="pvSubtotal">Rp 0</div>
                            </div>
                        </div>

                        <div class="profit-info d-none mt-4" id="pvProfitWrap">
                            Profit per pcs <span class="text-muted">(harga jual)</span>: <strong id="pvProfit">Rp 0,00</strong><span class="text-muted" id="pvProfitCode"></span>
                        </div>

                        <div class="alert alert-warning d-none mt-4 mb-0 py-3" id="itemError" role="alert"></div>

                        <div class="d-flex gap-2 mt-5">
                            <button type="button" class="btn btn-primary" id="btnAddItem">
                                <i class="ki-outline ki-plus fs-3"></i><span id="btnAddText">Tambah ke daftar</span>
                            </button>
                            <button type="button" class="btn btn-light d-none" id="btnCancelEdit">Batal ubah</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============ Card 2: daftar barang + total ============ --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title gap-3">
                <h3 class="fw-bold mb-0">Daftar barang yang dibeli</h3>
                <span class="badge badge-light-success" id="itemCount">0 item</span>
            </div>
            <div class="d-flex align-items-center text-muted fs-7">Periksa kembali pesanan sebelum disimpan</div>
        </div>

        <div class="card-body">
            <div class="text-center text-muted py-10" id="emptyState">
                Belum ada barang. Isi form di atas lalu klik "Tambah ke daftar".
            </div>

            <div class="table-responsive d-none" id="itemsWrap">
                <table class="table table-row-dashed align-middle gs-0 gy-4">
                    <thead>
                        <tr class="fw-bold text-muted fs-7">
                            <th style="width:50px">No</th>
                            <th>Nama produk</th>
                            <th>Kategori</th>
                            <th>Unit</th>
                            <th class="text-center">Jumlah</th>
                            <th class="text-end">Total pcs</th>
                            <th class="text-end">Harga / unit</th>
                            <th class="text-end">Subtotal</th>
                            <th class="text-end" style="width:110px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody"></tbody>
                </table>
            </div>

            <div class="separator separator-dashed my-6"></div>

            <div class="row g-6">
                <div class="col-lg-7">
                    <label for="note" class="form-label">Catatan transaksi</label>
                    <textarea id="note" class="form-control" rows="5" maxlength="500" placeholder="Nomor resi, termin pembayaran tempo, atau kondisi kemasan...">{{ $purchase?->note }}</textarea>
                    <div class="text-danger small mt-1" data-error-for="note"></div>
                </div>

                <div class="col-lg-5">
                    <div class="bg-light rounded p-5">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Total jenis barang</span>
                            <strong id="grandTypes">0 macam</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-4">
                            <span class="text-muted">Total kuantitas masuk</span>
                            <strong id="grandPcs">0 pcs</strong>
                        </div>
                        <div class="separator separator-dashed mb-4"></div>
                        <div class="d-flex justify-content-between align-items-center mb-5">
                            <span class="fw-semibold">Total pembelian</span>
                            <span class="fw-bold fs-2 text-primary stat-value" id="grandTotal">Rp 0</span>
                        </div>
                        <div class="d-flex gap-3">
                            <a href="{{ $purchase ? route('purchases.show', $purchase) : route('purchases.index') }}"
                               class="btn btn-light" id="btnBack">Batal</a>
                            <button type="button" class="btn btn-primary flex-grow-1" id="btnSubmit" data-loading-text="Menyimpan...">
                                {{ $purchase ? 'Simpan perubahan' : 'Simpan pembelian' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {{-- jQuery, Select2, dan flatpickr sudah ada di plugins.bundle.js (layout), jadi tidak dimuat ulang --}}
    <script>
        // Tampilkan error JS di kotak merah halaman (sementara, untuk diagnosa)
        window.addEventListener('error', function (e) {
            var box = document.getElementById('formAlert');
            if (box) { box.classList.remove('d-none'); box.textContent = 'Error JS: ' + e.message + ' (baris ' + e.lineno + ')'; }
        });
        window.APP  = @json($config);
        window.CSRF = '{{ csrf_token() }}';
    </script>

    @verbatim
    <script>
    $(function () {
        const spinner = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>';
        const nf  = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 });
        // Format Rupiah tunggal: "Rp 120.000,00" (sama dengan ProfitHarga::harga_formatted di PHP)
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
        const catMap = {};
        APP.sales.forEach((s) => s.categories.forEach((c) => { catMap[c.id] = c; }));

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
        let variantMode = false;                  // produk terpilih memakai varian (kategori Pakaian)?
        let currentVariant = null;                // varian yang sudah terpilih lengkap

        // ---------- plugin ----------
        $sales.select2({ width: '100%', theme: 'default', placeholder: 'Pilih sales' });
        $product.select2({
            width: '100%',
            theme: 'default',
            placeholder: 'Pilih produk',
            allowClear: true,
            templateResult: function (o) {
                if (!o.id) return o.text;
                const c = catMap[$(o.element).attr('data-cat')];
                return $('<span></span>').text(o.text).append(c ? $('<span class="text-muted fs-8 ms-2"></span>').text(c.name) : '');
            }
        });

        // ---------- Profit Harga (Select2): cari lewat harga ATAU code ----------
        const digitsOf = (v) => String(v == null ? '' : v).split(',')[0].replace(/\D/g, '');

        function profitMatcher(params, data) {
            const term = String(params.term || '').trim();
            if (term === '') return data;
            if (!data.id) return null;
            const pr = profitMap[data.id];
            if (!pr) return null;
            const lower = term.toLowerCase();
            const digits = digitsOf(term);
            if (pr.code && pr.code.toLowerCase().indexOf(lower) !== -1) return data;                  // code: bagian mana pun
            // harga: awalan angka. "120" -> 120.000 (bukan 112.000); "120.000" dan "Rp 120.000,00" juga dikenali
            if (digits !== '' && String(pr.harga).indexOf(digits) === 0) return data;
            if (digits === '' && 'rp'.indexOf(lower.replace(/\s+/g, '')) === 0) return data;         // mengetik "Rp" saja: semua tampil
            return null;
        }

        // harga di kiri (format Rupiah), code di kanan; tanpa code: hanya harga
        function profitTemplate(state) {
            if (!state.id) return state.text;
            const pr = profitMap[state.id];
            if (!pr) return state.text;
            const $row = $('<span class="profit-opt"></span>');
            $row.append($('<span class="profit-opt-price"></span>').text(rp(pr.harga)));
            if (pr.code) $row.append($('<span class="profit-opt-code"></span>').text(pr.code));
            return $row;
        }

        $profit.select2({
            width: '100%',
            theme: 'default',
            placeholder: 'Pilih Profit Harga',
            allowClear: true,
            minimumResultsForSearch: 0,
            matcher: profitMatcher,
            templateResult: profitTemplate,
            templateSelection: profitTemplate,
            language: { noResults: () => 'Profit Harga tidak ditemukan' }
        });

        const dateFp = flatpickr('#purchase_date', {
            locale: (window.flatpickr && flatpickr.l10ns && flatpickr.l10ns.id) ? 'id' : 'default',
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd/m/Y',
            altInputClass: 'form-control',
            defaultDate: APP.date || null,        // kosong saat tambah baru -> placeholder tampil
            disableMobile: true
        });
        if (dateFp && dateFp.altInput) dateFp.altInput.setAttribute('placeholder', 'dd/mm/yyyy');

        // ---------- helpers satuan ----------
        function findCategory(id) { return catMap[id] || null; }

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

        // ---------- varian: warna -> ukuran -> bahan -> model ----------
        const VSTEPS = ['color', 'size', 'material', 'style'];
        const NONE = '__none__';                                         // varian tanpa bahan / model
        const variantCats = (APP.variantCategories || []).map(String);
        const vOrder = APP.variantOrder || {};
        const vKey = { color: 'co', size: 'si', material: 'ma', style: 'st' };
        const stepLabel = { color: 'Warna', size: 'Ukuran', material: 'Bahan', style: 'Model' };
        const noneLabel = { material: 'Tanpa bahan', style: 'Tanpa model' };
        const valOf = (v, step) => (v[vKey[step]] === null || v[vKey[step]] === undefined) ? NONE : v[vKey[step]];

        function variantLabelOf(v) {
            return VSTEPS.map((st) => valOf(v, st)).filter((x) => x !== NONE).join(' / ');
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

        // Langkah muncul satu per satu: langkah berikut hanya tampil setelah langkah sebelumnya dipilih,
        // pilihannya menyempit sesuai varian yang ada, dan langkah tanpa pilihan dilewati.
        function refreshVariantSteps(preset) {
            if (!variantMode || !productVariants.length) {
                VSTEPS.forEach((st) => { $('#vwrap_' + st).addClass('d-none'); $('#v_' + st).empty(); });
                currentVariant = null;
                return;
            }

            const chosen = {};
            let blocked = false;

            VSTEPS.forEach((step) => {
                const $wrap = $('#vwrap_' + step), $sel = $('#v_' + step);
                const prev = (preset && preset[step] !== undefined) ? preset[step] : $sel.val();

                if (blocked) { $wrap.addClass('d-none'); $sel.empty(); return; }

                const cands = productVariants.filter((v) => Object.keys(chosen).every((k) => valOf(v, k) === chosen[k]));
                const values = [];
                cands.forEach((v) => { const val = valOf(v, step); if (values.indexOf(val) === -1) values.push(val); });

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
                const matches = productVariants.filter((v) => VSTEPS.every((k) => valOf(v, k) === chosen[k]));
                currentVariant = matches.length === 1 ? matches[0] : null;
            }
        }

        function onProductChange() {
            const pid = $product.val();
            const catId = $category.val();
            variantMode = !!pid && !!catId && variantCats.indexOf(String(catId)) !== -1;

            const p = pid ? APP.products.find((x) => String(x.id) === String(pid)) : null;
            productVariants = (variantMode && p) ? (p.v || []) : [];

            VSTEPS.forEach((st) => { $('#v_' + st).empty(); });
            refreshVariantSteps();

            if (!variantMode) {
                $('#variantBox').addClass('d-none');
                $('#variantHint').text('');
                return;
            }

            $('#variantBox').removeClass('d-none');
            $('#variantHint').text(productVariants.length
                ? 'Pilih varian: warna, lalu ukuran, bahan, dan model.'
                : 'Produk ini belum punya varian. Buat dulu di Master Data > Produk Sales (klik nama produknya).');
        }

        // Mengganti satu langkah mengosongkan langkah sesudahnya, jadi pilihan selalu berurutan
        $('#v_color, #v_size, #v_material, #v_style').on('change', function () {
            const idx = VSTEPS.indexOf(this.id.replace('v_', ''));
            const reset = {};
            VSTEPS.slice(idx + 1).forEach((st) => { reset[st] = ''; });
            refreshVariantSteps(reset);
        });

        // ---------- produk mengikuti sales; kategori & unit mengikuti produk ----------
        function setCategory(catId) {
            const c = catId ? findCategory(catId) : null;
            $category.empty();
            if (c) {
                $category.append(new Option(c.name, c.id, true, true));
            } else {
                $category.append('<option value="">Otomatis dari produk</option>');
            }
            $category.prop('disabled', true);

            const list = c ? unitsForCategory(c.id) : [];
            $unit.empty();
            if (list.length) {
                list.forEach((u) => $unit.append(new Option(unitOptionLabel(u), u.id)));
                $unit.prop('disabled', false);
            } else {
                $unit.append('<option value="">' + (c ? 'Belum ada unit untuk kategori ini' : '-') + '</option>').prop('disabled', true);
            }
            renderCategoryChips();
            onUnitChange();
        }

        // Kategori sales tampil sebagai tag; yang aktif = kategori produk terpilih
        function renderCategoryChips() {
            const sl = salesMap[currentSales];
            const active = String($category.val() || '');
            const $box = $('#categoryChips').empty();
            if (!sl) {
                $box.append('<span class="text-muted">Pilih sales dulu</span>');
                $('#categoryHint').text('');
                return;
            }
            if (!sl.categories.length) {
                $box.append('<span class="text-muted">Sales belum punya kategori</span>');
                $('#categoryHint').text('Atur kategori sales di Master Data > Nama Sales.');
                return;
            }
            sl.categories.forEach((c) => {
                $box.append($('<span class="badge fs-7 fw-semibold py-2 px-3"></span>')
                    .addClass(String(c.id) === active ? 'badge-primary' : 'badge-light')
                    .text(c.name));
            });
            $('#categoryHint').text('Kategori sales ini. Yang menyala mengikuti produk yang dipilih.');
        }

        function buildProducts() {
            const products = currentSales
                ? APP.products.filter((p) => p.s.map(String).indexOf(String(currentSales)) !== -1)
                : [];
            $product.empty().append('<option value=""></option>');
            products.forEach((p) => $product.append($('<option></option>').val(p.id).text(p.n).attr('data-cat', p.c)));
            $product.prop('disabled', !products.length).val(null).trigger('change');
            $('#productHint').text(!currentSales
                ? 'Pilih sales dulu.'
                : (products.length ? '' : 'Belum ada produk untuk sales ini. Tambahkan di Master Data > Produk Sales.'));
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

            // Profit per pcs = Profit Harga terpilih (harga jual per pcs); terpisah dari harga beli dari sales
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

        $('#qtyMinus').on('click', function () { $qty.val(Math.max(1, toInt($qty.val()) - 1)); updatePreview(); });
        $('#qtyPlus').on('click', function () { $qty.val(toInt($qty.val()) + 1); updatePreview(); });

        $price.on('input', function () {
            const n = toInt(this.value);
            this.value = n ? nf.format(n) : '';
            updatePreview();
        });

        $product.on('change', function () {
            if (this.value) setCategory($product.find('option:selected').attr('data-cat'));
            onProductChange();
        });
        $unit.on('change', onUnitChange);
        $profit.on('change', updatePreview);

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
            setCategory(null);
            buildProducts();
            renderItems();
        });

        // ---------- tambah / perbarui barang ----------
        function showItemError(msg) { $('#itemError').removeClass('d-none').text(msg); }
        function hideItemError() { $('#itemError').addClass('d-none').text(''); }

        function validateItemInput() {
            if (!currentSales) return 'Pilih sales terlebih dahulu.';
            if (!$product.val()) return 'Pilih produk.';
            if (!$category.val()) return 'Kategori belum terisi. Pilih produk dulu.';
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

        // reset input barang; sales, kategori, dan tanggal tetap
        function resetItemInputs() {
            $product.val(null).trigger('change');
            $profit.val(null).trigger('change');
            $unit.prop('selectedIndex', 0);
            $pack.val('');
            $qty.val('1');
            $price.val('');
            onUnitChange();
        }

        function resetEditUi() {
            $('#btnAddText').text('Tambah ke daftar');
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
            if (items[i].received) return;
            const it = items[i];
            editing = { item: it, index: i };
            items.splice(i, 1);

            catMap[it.category_id] = catMap[it.category_id] || { id: it.category_id, name: it.category_name, slug: it.category_slug };
            ensureOption($product, it.product_id, it.product_name);
            $product.find('option').filter(function () { return this.value === String(it.product_id); }).attr('data-cat', it.category_id);
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

            if (it.unit_id) {
                ensureOption($unit, it.unit_id, unitMap[it.unit_id] ? unitOptionLabel(unitMap[it.unit_id]) : it.unit_name);
                $unit.val(String(it.unit_id));
            }

            $qty.val(it.unit_qty);
            $price.val(nf.format(it.unit_price));
            $profit.val(it.profit_harga_id ? String(it.profit_harga_id) : '').trigger('change');
            onUnitChange();

            const u = unitMap[it.unit_id];
            if (!u || !u.pcs_per_unit) $pack.val(it.pcs_per_unit);
            updatePreview();

            $('#btnAddText').text('Perbarui');
            $('#btnCancelEdit').removeClass('d-none');
            hideItemError();
            renderItems();
            $('html, body').animate({ scrollTop: 0 }, 200);
        }

        // ---------- tabel barang ----------
        function profitSubline(it) {
            const pr = it.profit_harga_id ? profitMap[it.profit_harga_id] : null;
            if (!pr) return '';
            return '<div class="fs-8 fw-normal profit-sub">Profit per pcs: ' + esc(rp(pr.harga)) +
                (pr.code ? ' (' + esc(pr.code) + ')' : '') + '</div>';
        }

        // Barang yang sudah dicek (sudah masuk stok) tidak bisa diubah / dihapus di sini
        const lock = (it, label) => it.received
            ? ' disabled title="Sudah dicek dan masuk stok. Batalkan centang di halaman detail kalau perlu mengubahnya."'
            : (editing ? ' disabled' : '') + ' title="' + label + '"';

        function renderItems() {
            const $body = $('#itemsBody').empty();
            let totalPcs = 0, total = 0;

            items.forEach((it, i) => {
                const pcs = it.unit_qty * it.pcs_per_unit;
                const sub = it.unit_qty * it.unit_price;
                totalPcs += pcs;
                total += sub;

                $body.append(
                    '<tr>' +
                    '<td class="text-muted">' + (i + 1) + '</td>' +
                    '<td class="fw-semibold">' + esc(it.product_name) +
                        (it.variant_label ? '<div class="text-muted fs-8 fw-normal">' + esc(it.variant_label) + '</div>' : '') +
                        profitSubline(it) +
                        (it.received ? ' <span class="badge badge-light-success ms-1">Sudah datang</span>' : '') + '</td>' +
                    '<td><span class="badge badge-light-primary">' + esc(it.category_name) + '</span></td>' +
                    '<td>' + esc(it.unit_name || '-') +
                        (it.pcs_per_unit > 1 ? ' <span class="text-muted fs-8">(isi ' + it.pcs_per_unit + ' pcs)</span>' : '') + '</td>' +
                    '<td class="text-center fw-bold">' + nf.format(it.unit_qty) + '</td>' +
                    '<td class="text-end">' + nf.format(pcs) + '</td>' +
                    '<td class="text-end">' + rp(it.unit_price) + '</td>' +
                    '<td class="text-end fw-bold">' + rp(sub) + '</td>' +
                    '<td class="text-end text-nowrap">' +
                        '<button type="button" class="btn btn-icon btn-sm btn-light-warning btn-edit me-1" data-index="' + i + '"' + lock(it, 'Ubah') + '><i class="ki-outline ki-pencil fs-4"></i></button>' +
                        '<button type="button" class="btn btn-icon btn-sm btn-light-danger btn-remove" data-index="' + i + '"' + lock(it, 'Hapus') + '><i class="ki-outline ki-trash fs-4"></i></button>' +
                    '</td>' +
                    '</tr>'
                );
            });

            $('#itemsWrap').toggleClass('d-none', items.length === 0);
            $('#emptyState').toggleClass('d-none', items.length !== 0);
            $('#itemCount').text(items.length + ' item');
            $('#grandTypes').text(items.length + ' macam');
            $('#grandPcs').text(nf.format(totalPcs) + ' pcs');
            $('#grandTotal').text(rp(total));
        }

        $('#itemsBody').on('click', '.btn-edit', function () { startEdit(parseInt($(this).data('index'), 10)); });
        $('#itemsBody').on('click', '.btn-remove', function () {
            if (editing) return;
            if (items[parseInt($(this).data('index'), 10)].received) return;
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
            if (!$('#purchase_date').val()) { showFormAlert(['Pilih tanggal pembelian.']); return; }
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
                $submit.html(spinner + 'Berhasil, mengalihkan...');
                window.location.href = res.redirect;
            }).fail(function (xhr) {
                setSubmitting(false);
                handleError(xhr);
            });
        });

        // ---------- init ----------
        setCategory(null);
        buildProducts();
        renderItems();
    });
    </script>
    @endverbatim
@endpush