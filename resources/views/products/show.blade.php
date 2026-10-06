@extends('layouts.app')

@section('title', $product->name)

@push('styles')
    <style>
        /* ---------- Hero ---------- */
        .pv-hero {
            position: relative;
            overflow: hidden;
            border-radius: 1rem;
            padding: 1.75rem 2rem;
            color: #fff;
            background: linear-gradient(135deg, var(--bs-primary) 0%, color-mix(in srgb, var(--bs-primary) 45%, #0b1020) 100%);
        }
        .pv-hero::after {
            content: '';
            position: absolute;
            right: -70px;
            top: -70px;
            width: 240px;
            height: 240px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .08);
        }
        .pv-hero > * { position: relative; z-index: 1; }
        .pv-back { color: rgba(255, 255, 255, .8); text-decoration: none; font-size: .85rem; }
        .pv-back:hover { color: #fff; }
        .pv-title { color: #fff; font-weight: 700; letter-spacing: -.01em; margin: .35rem 0 .75rem; }
        .pv-pill { display: inline-block; padding: .2rem .7rem; border-radius: 999px; background: rgba(255, 255, 255, .22); font-size: .78rem; }
        .pv-pill-soft { background: rgba(255, 255, 255, .1); }
        .pv-stat { min-width: 92px; padding: .6rem .9rem; border-radius: .75rem; background: rgba(255, 255, 255, .14); text-align: center; }
        .pv-stat-num { font-size: 1.5rem; font-weight: 700; line-height: 1.1; font-variant-numeric: tabular-nums; }
        .pv-stat-label { font-size: .7rem; text-transform: uppercase; letter-spacing: .06em; opacity: .85; }

        /* ---------- Composer ---------- */
        .pv-composer { border-top: 3px solid var(--bs-primary); }
        .pv-label { display: flex; justify-content: space-between; align-items: baseline; font-weight: 600; margin-bottom: .5rem; }
        .pv-hint { font-weight: 400; font-size: .78rem; color: var(--bs-secondary-color, #9ca3af); }
        .pv-chips { display: flex; flex-wrap: wrap; gap: .4rem; }
        .pv-chip {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .3rem .85rem;
            border-radius: 999px;
            border: 1px solid var(--bs-border-color, #d1d5db);
            background: var(--bs-body-bg);
            color: var(--bs-body-color);
            font-size: .85rem;
            line-height: 1.4;
            cursor: pointer;
            transition: background-color .12s, border-color .12s, color .12s;
        }
        .pv-chip:hover { border-color: var(--bs-primary); }
        .pv-chip[aria-pressed="true"] { background: var(--bs-primary); border-color: var(--bs-primary); color: #fff; }
        .pv-dot {
            flex: 0 0 auto;
            display: inline-block;
            width: .85rem;
            height: .85rem;
            border-radius: 50%;
            border: 1px solid rgba(128, 128, 128, .55);
            vertical-align: -1px;
        }
        .pv-dot-none { background: repeating-linear-gradient(45deg, transparent 0 3px, rgba(128, 128, 128, .5) 3px 4px); }
        .pv-preview {
            padding: .6rem .9rem;
            border-radius: .6rem;
            font-size: .85rem;
            background: var(--bs-tertiary-bg, rgba(128, 128, 128, .12));
        }

        /* ---------- Tabel ---------- */
        .pv-stock {
            display: inline-block;
            min-width: 2.4rem;
            padding: .15rem .55rem;
            border-radius: .4rem;
            text-align: center;
            font-weight: 600;
            font-variant-numeric: tabular-nums;
            background: var(--bs-tertiary-bg, rgba(128, 128, 128, .12));
            cursor: help;
        }
        .pv-stock-zero { color: var(--bs-secondary-color, #9ca3af); }
        .pv-row-editing { outline: 2px solid var(--bs-primary); outline-offset: -2px; }
        .pv-row-new { background: color-mix(in srgb, var(--bs-success, #16a34a) 9%, transparent); }
    </style>
@endpush

@section('content')
    @include('master-data.partials.flash')

    {{-- ============ Hero ============ --}}
    <div class="pv-hero mb-5">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-4">
            <div>
                <a href="{{ route('master-data.products.index') }}" class="pv-back">&larr; Produk Sales</a>
                <h2 class="pv-title">{{ $product->name }}</h2>
                <div class="d-flex flex-wrap gap-2">
                    <span class="pv-pill">{{ $product->category->name }}</span>
                    @foreach ($product->sales as $sale)
                        <span class="pv-pill pv-pill-soft">{{ $sale->name }}</span>
                    @endforeach
                </div>
            </div>

            <div class="d-flex flex-wrap gap-3">
                <div class="pv-stat"><div class="pv-stat-num" id="statVariants">0</div><div class="pv-stat-label">Varian</div></div>
                <div class="pv-stat"><div class="pv-stat-num" id="statSizes">0</div><div class="pv-stat-label">Ukuran</div></div>
                <div class="pv-stat"><div class="pv-stat-num" id="statColors">0</div><div class="pv-stat-label">Warna</div></div>
                <div class="pv-stat"><div class="pv-stat-num" id="statStock">0</div><div class="pv-stat-label">Total stok</div></div>
            </div>
        </div>
    </div>

    <div class="row g-5">
        @can('master-produk-sales.edit')
            {{-- ============ Kiri: racik varian ============ --}}
            <div class="col-xl-5">
                <div class="card pv-composer">
                    <div class="card-header">
                        <div>
                            <h5 class="card-title mb-0" id="composerTitle">Racik varian</h5>
                            <div class="text-muted small">Pilih satu atau banyak ukuran dan warna, semua kombinasinya ditambahkan sekaligus.</div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="alert d-none" id="composerMsg" role="alert"></div>

                        <div class="mb-5">
                            <div class="pv-label">
                                <span>Ukuran <span class="text-danger">*</span></span>
                                <span class="pv-hint" id="sizeCount"></span>
                            </div>
                            <div class="pv-chips" id="sizeChips"></div>
                            <div class="input-group input-group-sm mt-3">
                                <input type="text" id="sizeCustom" class="form-control" maxlength="30" autocomplete="off" placeholder="Ukuran lain, mis. 30 atau 5-6 th">
                                <button type="button" class="btn btn-outline-secondary" id="sizeCustomAdd">+ Pilihan</button>
                            </div>
                        </div>

                        <div class="mb-5">
                            <div class="pv-label">
                                <span>Warna <span class="text-danger">*</span></span>
                                <span class="pv-hint" id="colorCount"></span>
                            </div>
                            <div class="pv-chips" id="colorChips"></div>
                            <div class="input-group input-group-sm mt-3">
                                <input type="text" id="colorCustom" class="form-control" maxlength="40" autocomplete="off" placeholder="Warna lain, mis. Tosca">
                                <button type="button" class="btn btn-outline-secondary" id="colorCustomAdd">+ Pilihan</button>
                            </div>
                        </div>

                        <div class="row g-4 mb-5">
                            <div class="col-md-6">
                                <label for="material" class="form-label">Bahan</label>
                                <input type="text" id="material" class="form-control" list="materialList" maxlength="50" autocomplete="off" placeholder="Opsional">
                                <datalist id="materialList">
                                    @foreach ($materials as $m)
                                        <option value="{{ $m }}"></option>
                                    @endforeach
                                </datalist>
                            </div>
                            <div class="col-md-6">
                                <label for="style" class="form-label">Model</label>
                                <input type="text" id="style" class="form-control" list="styleList" maxlength="50" autocomplete="off" placeholder="Opsional">
                                <datalist id="styleList">
                                    @foreach ($styles as $s)
                                        <option value="{{ $s }}"></option>
                                    @endforeach
                                </datalist>
                            </div>
                        </div>

                        <div class="mb-5">
                            <label for="price" class="form-label">Harga jual per pcs</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" inputmode="numeric" id="price" class="form-control" placeholder="Opsional" autocomplete="off">
                            </div>
                        </div>

                        <div class="pv-preview mb-4" id="composerPreview">Pilih ukuran dan warna dulu.</div>

                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-primary flex-grow-1" id="btnAdd">Tambah ke daftar</button>
                            <button type="button" class="btn btn-outline-secondary d-none" id="btnCancelEdit">Batal ubah</button>
                            <button type="button" class="btn btn-outline-secondary" id="btnReset">Reset</button>
                        </div>
                    </div>
                </div>
            </div>
        @endcan

        {{-- ============ Kanan: daftar varian ============ --}}
        <div class="{{ auth()->user()->can('master-produk-sales.edit') ? 'col-xl-7' : 'col-12' }}">
            <div class="card">
                <div class="card-header">
                    <div>
                        <h5 class="card-title mb-0">Daftar varian</h5>
                        <div class="text-muted small">Stok tidak bisa diisi manual. Stok bertambah lewat Tambah Produk.</div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="alert alert-danger d-none" id="formAlert" role="alert"></div>

                    <div class="text-center text-muted py-10" id="emptyState">
                        Belum ada varian.
                        @can('master-produk-sales.edit')
                            Pilih ukuran dan warna di sebelah kiri, lalu klik "Tambah ke daftar".
                        @endcan
                    </div>

                    <div class="table-responsive d-none" id="tableWrap">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Ukuran</th>
                                    <th>Warna</th>
                                    <th>Bahan</th>
                                    <th>Model</th>
                                    <th class="text-end">Harga jual</th>
                                    <th class="text-end">Stok</th>
                                    @can('master-produk-sales.edit')
                                        <th class="text-end">Aksi</th>
                                    @endcan
                                </tr>
                            </thead>
                            <tbody id="variantBody"></tbody>
                        </table>
                    </div>
                </div>

                <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <a href="{{ route('master-data.products.index') }}" class="btn btn-outline-secondary" id="btnBack">Kembali</a>

                    @can('master-produk-sales.edit')
                        <div class="d-flex align-items-center gap-3">
                            <span class="small text-muted" id="dirtyText">Tidak ada perubahan</span>
                            <button type="button" class="btn btn-primary" id="btnSave" data-loading-text="Menyimpan..." disabled>Simpan</button>
                        </div>
                    @endcan
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const APP  = @json($config);
        const CSRF = '{{ csrf_token() }}';
    </script>

    @verbatim
    <script>
    $(function () {
        const spinner = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>';
        const nf = new Intl.NumberFormat('id-ID');
        const rp = (n) => 'Rp ' + nf.format(n || 0);
        const toInt = (v) => parseInt(String(v == null ? '' : v).replace(/\D/g, ''), 10) || 0;
        const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        const collapse = (s) => String(s == null ? '' : s).replace(/\s+/g, ' ').trim();
        const titleCase = (s) => s.toLowerCase().replace(/(^|\s)(\S)/g, (m, a, b) => a + b.toUpperCase());
        const normText = (s) => titleCase(collapse(s));
        const canEdit = !!APP.canEdit;

        // ---------- data ----------
        const colorHex = {};
        Object.keys(APP.colors || {}).forEach((n) => { colorHex[n.toLowerCase()] = APP.colors[n]; });

        const makeRow = (v) => ({
            id: v.id, size: v.size, color: v.color, material: v.material || '', style: v.style || '',
            price: v.price || null, stock: v.stock || 0, isNew: false,
            orig: { size: v.size, color: v.color, material: v.material || '', style: v.style || '', price: v.price || null }
        });

        let rows = (APP.variants || []).map(makeRow);
        let removed = [];
        let editing = null;            // baris yang sedang diubah (referensi)
        let selSizes = [];
        let selColors = [];
        let saving = false;

        const sizeOptions = (APP.sizes || []).slice();
        const colorOptions = Object.keys(APP.colors || {});
        function ensureOption(list, value) {
            if (!list.some((o) => o.toLowerCase() === value.toLowerCase())) list.push(value);
        }
        rows.forEach((r) => { ensureOption(sizeOptions, r.size); ensureOption(colorOptions, r.color); });

        const keyOf = (r) => [r.size, r.color, r.material, r.style].map((x) => String(x || '').toLowerCase()).join('|');
        const isChanged = (r) => !r.isNew && (
            r.size !== r.orig.size || r.color !== r.orig.color || r.material !== r.orig.material ||
            r.style !== r.orig.style || (r.price || 0) !== (r.orig.price || 0));

        function dotHtml(color) {
            const hex = colorHex[String(color).toLowerCase()];
            return hex
                ? '<span class="pv-dot" style="background:' + esc(hex) + '"></span>'
                : '<span class="pv-dot pv-dot-none"></span>';
        }

        // ---------- normalisasi input ----------
        function normSize(s) {
            s = collapse(s);
            const hit = sizeOptions.find((o) => o.toLowerCase() === s.toLowerCase());
            if (hit) return hit;
            return /^[a-z]{1,4}$/i.test(s) ? s.toUpperCase() : s;
        }
        function normColor(s) {
            s = collapse(s);
            const hit = colorOptions.find((o) => o.toLowerCase() === s.toLowerCase());
            return hit || titleCase(s);
        }

        // ---------- statistik + tombol simpan ----------
        function updateStats() {
            $('#statVariants').text(rows.length);
            $('#statSizes').text(new Set(rows.map((r) => r.size.toLowerCase())).size);
            $('#statColors').text(new Set(rows.map((r) => r.color.toLowerCase())).size);
            $('#statStock').text(nf.format(rows.reduce((a, r) => a + r.stock, 0)));
        }

        function changeCount() {
            return rows.filter((r) => r.isNew).length + rows.filter(isChanged).length + removed.length;
        }

        function updateDirty() {
            const n = changeCount();
            $('#dirtyText').text(n ? n + ' perubahan belum disimpan' : 'Tidak ada perubahan');
            $('#btnSave').prop('disabled', saving || n === 0);
        }

        // ---------- tabel ----------
        function renderTable() {
            const $body = $('#variantBody').empty();
            const lock = editing ? ' disabled' : '';

            rows.forEach((r, i) => {
                const cls = (r === editing ? 'pv-row-editing ' : '') + (r.isNew ? 'pv-row-new' : '');
                const badge = r.isNew
                    ? ' <span class="badge bg-success">Baru</span>'
                    : (isChanged(r) ? ' <span class="badge bg-warning text-dark">Diubah</span>' : '');
                const dash = '<span class="text-muted">-</span>';

                let actions = '';
                if (canEdit) {
                    const stockLocked = r.id && r.stock > 0;
                    actions = '<td class="text-end text-nowrap">' +
                        '<button type="button" class="btn btn-sm btn-outline-warning btn-edit" data-index="' + i + '"' + lock + '>Ubah</button> ' +
                        '<button type="button" class="btn btn-sm btn-outline-danger btn-remove" data-index="' + i + '"' +
                            (lock || (stockLocked ? ' disabled title="Stok masih ada, tidak bisa dihapus"' : '')) + '>Hapus</button>' +
                        '</td>';
                }

                $body.append(
                    '<tr class="' + cls + '">' +
                    '<td class="fw-semibold">' + esc(r.size) + badge + '</td>' +
                    '<td>' + dotHtml(r.color) + ' ' + esc(r.color) + '</td>' +
                    '<td>' + (r.material ? esc(r.material) : dash) + '</td>' +
                    '<td>' + (r.style ? esc(r.style) : dash) + '</td>' +
                    '<td class="text-end">' + (r.price ? rp(r.price) : dash) + '</td>' +
                    '<td class="text-end"><span class="pv-stock ' + (r.stock > 0 ? '' : 'pv-stock-zero') +
                        '" title="Stok bertambah otomatis lewat Tambah Produk">' + nf.format(r.stock) + '</span></td>' +
                    actions + '</tr>'
                );
            });

            $('#tableWrap').toggleClass('d-none', rows.length === 0);
            $('#emptyState').toggleClass('d-none', rows.length !== 0);
            updateStats();
            updateDirty();
        }

        // ---------- composer ----------
        function chipHtml(value, selected, kind) {
            return '<button type="button" class="pv-chip" data-kind="' + kind + '" data-value="' + esc(value) + '" aria-pressed="' + (selected ? 'true' : 'false') + '">' +
                (kind === 'color' ? dotHtml(value) : '') + esc(value) + '</button>';
        }

        function comboStats() {
            const material = normText($('#material').val());
            const style = normText($('#style').val());
            const keys = new Set(rows.map(keyOf));
            let fresh = 0, dup = 0;
            selSizes.forEach((sz) => selColors.forEach((cl) => {
                if (keys.has(keyOf({ size: sz, color: cl, material: material, style: style }))) dup++; else fresh++;
            }));
            return { fresh: fresh, dup: dup, material: material, style: style };
        }

        function updatePreview() {
            $('#sizeCount').text(selSizes.length ? selSizes.length + ' dipilih' : '');
            $('#colorCount').text(selColors.length ? selColors.length + ' dipilih' : '');

            let text;
            if (editing) {
                text = 'Mengubah 1 varian. Pilih satu ukuran dan satu warna.';
            } else if (!selSizes.length || !selColors.length) {
                text = 'Pilih ukuran dan warna dulu.';
            } else {
                const s = comboStats();
                text = 'Akan menambah <strong>' + s.fresh + '</strong> varian (' + selSizes.length + ' ukuran &times; ' + selColors.length + ' warna)' +
                    (s.dup ? ', ' + s.dup + ' sudah ada dan dilewati' : '') + '.';
            }
            $('#composerPreview').html(text);
        }

        function renderChips() {
            $('#sizeChips').html(sizeOptions.map((o) => chipHtml(o, selSizes.indexOf(o) !== -1, 'size')).join(''));
            $('#colorChips').html(colorOptions.map((o) => chipHtml(o, selColors.indexOf(o) !== -1, 'color')).join(''));
            updatePreview();
        }

        function showMsg(type, html) {
            $('#composerMsg').removeClass('d-none alert-success alert-warning alert-danger').addClass('alert-' + type).html(html);
        }
        function hideMsg() { $('#composerMsg').addClass('d-none').empty(); }

        function resetComposer() {
            editing = null;
            selSizes = []; selColors = [];
            $('#material, #style, #price, #sizeCustom, #colorCustom').val('');
            $('#composerTitle').text('Racik varian');
            $('#btnAdd').text('Tambah ke daftar');
            $('#btnCancelEdit').addClass('d-none');
            hideMsg();
            renderChips();
            renderTable();
        }

        function toggleChip(kind, value) {
            const list = kind === 'size' ? selSizes : selColors;
            const i = list.indexOf(value);
            let next;
            if (editing) { next = [value]; }                       // saat mengubah: pilih satu saja
            else if (i === -1) { next = list.concat([value]); }
            else { next = list.filter((x) => x !== value); }
            if (kind === 'size') selSizes = next; else selColors = next;
            renderChips();
        }

        function addCustom(kind) {
            const $input = kind === 'size' ? $('#sizeCustom') : $('#colorCustom');
            const value = kind === 'size' ? normSize($input.val()) : normColor($input.val());
            if (!value) return;
            ensureOption(kind === 'size' ? sizeOptions : colorOptions, value);
            const opt = (kind === 'size' ? sizeOptions : colorOptions).find((o) => o.toLowerCase() === value.toLowerCase());
            $input.val('');
            const list = kind === 'size' ? selSizes : selColors;
            if (editing) { if (kind === 'size') selSizes = [opt]; else selColors = [opt]; }
            else if (list.indexOf(opt) === -1) { if (kind === 'size') selSizes = list.concat([opt]); else selColors = list.concat([opt]); }
            renderChips();
        }

        if (canEdit) {
            $('#sizeChips, #colorChips').on('click', '.pv-chip', function () {
                toggleChip($(this).attr('data-kind'), $(this).attr('data-value'));   // attr(): jaga "30" tetap teks
            });
            $('#sizeCustomAdd').on('click', () => addCustom('size'));
            $('#colorCustomAdd').on('click', () => addCustom('color'));
            $('#sizeCustom').on('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); addCustom('size'); } });
            $('#colorCustom').on('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); addCustom('color'); } });
            $('#material, #style').on('input', updatePreview);
            $('#price').on('input', function () {
                const n = toInt(this.value);
                this.value = n ? nf.format(n) : '';
            });

            $('#btnReset').on('click', resetComposer);
            $('#btnCancelEdit').on('click', resetComposer);

            $('#btnAdd').on('click', function () {
                hideMsg();
                if (!selSizes.length) { showMsg('warning', 'Pilih minimal satu ukuran.'); return; }
                if (!selColors.length) { showMsg('warning', 'Pilih minimal satu warna.'); return; }

                const material = normText($('#material').val());
                const style = normText($('#style').val());
                const price = toInt($('#price').val()) || null;

                if (editing) {
                    const candidate = { size: selSizes[0], color: selColors[0], material: material, style: style };
                    const clash = rows.some((r) => r !== editing && keyOf(r) === keyOf(candidate));
                    if (clash) { showMsg('danger', 'Kombinasi ukuran, warna, bahan, dan model ini sudah ada di daftar.'); return; }
                    Object.assign(editing, candidate, { price: price });
                    resetComposer();
                    return;
                }

                const keys = new Set(rows.map(keyOf));
                let added = 0, skipped = 0;
                selSizes.forEach((sz) => selColors.forEach((cl) => {
                    const r = { id: null, size: sz, color: cl, material: material, style: style, price: price, stock: 0, isNew: true, orig: null };
                    const k = keyOf(r);
                    if (keys.has(k)) { skipped++; return; }
                    keys.add(k);
                    rows.push(r);
                    added++;
                }));

                if (!added) { showMsg('warning', 'Semua kombinasi itu sudah ada di daftar.'); return; }

                resetComposer();      // form di atas kosong lagi, siap isi berikutnya
                showMsg('success', '<strong>' + added + '</strong> varian ditambahkan ke daftar.' +
                    (skipped ? ' ' + skipped + ' dilewati karena sudah ada.' : ''));
            });

            // ---------- aksi baris ----------
            $('#variantBody').on('click', '.btn-edit', function () {
                if (editing) return;
                const r = rows[parseInt($(this).data('index'), 10)];
                editing = r;
                ensureOption(sizeOptions, r.size); ensureOption(colorOptions, r.color);
                selSizes = [r.size]; selColors = [r.color];
                $('#material').val(r.material); $('#style').val(r.style);
                $('#price').val(r.price ? nf.format(r.price) : '');
                $('#composerTitle').text('Ubah varian');
                $('#btnAdd').text('Perbarui');
                $('#btnCancelEdit').removeClass('d-none');
                hideMsg();
                renderChips();
                renderTable();
                $('html, body').animate({ scrollTop: 0 }, 200);
            });

            $('#variantBody').on('click', '.btn-remove', function () {
                if (editing) return;
                const i = parseInt($(this).data('index'), 10);
                const r = rows[i];
                if (r.id && r.stock > 0) return;
                if (r.id) removed.push(r.id);
                rows.splice(i, 1);
                renderChips();
                renderTable();
            });

            // ---------- simpan ----------
            const $save = $('#btnSave');

            function setSaving(on) {
                saving = on;
                if (on) {
                    $save.data('original', $save.html()).prop('disabled', true).html(spinner + $save.data('loading-text'));
                    $('#btnBack').addClass('disabled').attr('aria-disabled', 'true');
                } else {
                    $save.html($save.data('original'));
                    $('#btnBack').removeClass('disabled').removeAttr('aria-disabled');
                    updateDirty();
                }
            }

            function showFormAlert(lines) {
                const html = lines.length === 1 ? esc(lines[0]) : '<ul class="mb-0 ps-4">' + lines.map((l) => '<li>' + esc(l) + '</li>').join('') + '</ul>';
                $('#formAlert').removeClass('d-none').html(html);
                $('html, body').animate({ scrollTop: 0 }, 200);
            }

            $save.on('click', function () {
                if (saving) return;
                $('#formAlert').addClass('d-none').empty();

                if (editing) { showFormAlert(['Selesaikan dulu varian yang sedang diubah: klik "Perbarui" atau "Batal ubah".']); return; }

                setSaving(true);

                $.ajax({
                    url: APP.saveUrl,
                    method: 'PUT',
                    contentType: 'application/json',
                    dataType: 'json',
                    headers: { 'X-CSRF-TOKEN': CSRF },
                    data: JSON.stringify({
                        variants: rows.map((r) => ({
                            id: r.id, size: r.size, color: r.color,
                            material: r.material || null, style: r.style || null, price: r.price || null
                        }))
                    })
                }).done(function (res) {
                    $save.html(spinner + 'Berhasil, memuat ulang...');
                    window.location.href = res.redirect;      // tombol tetap terkunci sampai halaman pindah
                }).fail(function (xhr) {
                    setSaving(false);

                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        const lines = [];
                        $.each(xhr.responseJSON.errors, function (key, msgs) {
                            const m = key.match(/^variants\.(\d+)\.(.+)$/);
                            if (m) {
                                const r = rows[parseInt(m[1], 10)];
                                lines.push('Varian ' + (r ? r.size + ' / ' + r.color : 'ke-' + (parseInt(m[1], 10) + 1)) + ': ' + msgs[0]);
                            } else {
                                lines.push(msgs[0]);
                            }
                        });
                        showFormAlert(lines);
                        return;
                    }

                    showFormAlert([(xhr.responseJSON && xhr.responseJSON.message) || 'Terjadi kesalahan. Silakan coba lagi.']);
                });
            });

            // peringatan kalau meninggalkan halaman dengan perubahan yang belum disimpan
            window.addEventListener('beforeunload', function (e) {
                if (!saving && changeCount() > 0) { e.preventDefault(); e.returnValue = ''; }
            });
        }

        // ---------- init ----------
        renderChips();
        renderTable();
    });
    </script>
    @endverbatim
@endpush
