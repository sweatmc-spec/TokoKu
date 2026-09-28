$(function () {
    const SNAP = 0.25;
    const config = window.DENAH;
    let unplacedKamars = config.unplacedKamars || [];
    let floors = config.floors || [];
    let currentFloorId = floors.length ? floors[0].id : null;

    let mode = 'select'; // 'select' | 'place' | 'area'
    let selectedKamarId = null; // id of the *existing* unplaced kamar chosen from the palette
    let selectedKind = null; // 'kamar' | 'area'
    let selectedId = null;
    let dragCtx = null;
    let areaDrawCtx = null;
    let scale = 1;
    let canvasOffset = { left: 0, top: 0 };
    let saveTimers = {}; // one debounce timer per floor id (keyed by String(floorId))

    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    // Non-blocking toast — avoids native alert() freezing the whole tab.
    function showToast(message) {
        const $toast = $('<div class="denah-toast"></div>').text(message);
        $('body').append($toast);
        setTimeout(() => $toast.addClass('show'), 10);
        setTimeout(() => { $toast.removeClass('show'); setTimeout(() => $toast.remove(), 300); }, 2500);
    }

    function snap(v) { return Math.round(v / SNAP) * SNAP; }
    function clamp(v, min, max) { return Math.max(min, Math.min(max, v)); }

    // Mirrors app/Support/PolygonContainment.php — keep both in sync.
    function pointOnSegment(px, py, a, b) {
        const cross = (b.x - a.x) * (py - a.y) - (b.y - a.y) * (px - a.x);
        if (Math.abs(cross) > 1e-6) return false;
        return px >= Math.min(a.x, b.x) - 1e-9 && px <= Math.max(a.x, b.x) + 1e-9
            && py >= Math.min(a.y, b.y) - 1e-9 && py <= Math.max(a.y, b.y) + 1e-9;
    }

    // A point lying exactly on a polygon edge counts as inside — a room/area
    // placed flush against a wall is a normal, valid placement here.
    function pointInPolygon(px, py, polygon) {
        for (let i = 0, j = polygon.length - 1; i < polygon.length; j = i++) {
            if (pointOnSegment(px, py, polygon[i], polygon[j])) return true;
        }

        let inside = false;
        for (let i = 0, j = polygon.length - 1; i < polygon.length; j = i++) {
            const xi = polygon[i].x, yi = polygon[i].y;
            const xj = polygon[j].x, yj = polygon[j].y;
            const intersects = ((yi > py) !== (yj > py))
                && (px < (xj - xi) * (py - yi) / (yj - yi) + xi);
            if (intersects) inside = !inside;
        }
        return inside;
    }

    function orientation(p, q, r) {
        const val = (q.y - p.y) * (r.x - q.x) - (q.x - p.x) * (r.y - q.y);
        if (Math.abs(val) < 1e-9) return 0;
        return val > 0 ? 1 : 2;
    }

    // Strict transversal crossing only — deliberately excludes any
    // collinear/touching case, since a rectangle edge running flush along a
    // polygon edge (e.g. a room placed right against a wall) is the normal,
    // valid case, not a violation.
    function segmentsIntersect(p1, q1, p2, q2) {
        const o1 = orientation(p1, q1, p2);
        const o2 = orientation(p1, q1, q2);
        const o3 = orientation(p2, q2, p1);
        const o4 = orientation(p2, q2, q1);
        return o1 !== 0 && o2 !== 0 && o3 !== 0 && o4 !== 0 && o1 !== o2 && o3 !== o4;
    }

    // A rect lies fully inside a simple polygon only if all 4 corners are
    // inside AND no polygon edge slices through the middle of any rect side
    // (the corner check alone misses a concave notch cutting through a side
    // without ever crossing a corner).
    function rectInPolygon(x, y, w, h, polygon) {
        const corners = [
            { x: x, y: y },
            { x: x + w, y: y },
            { x: x + w, y: y + h },
            { x: x, y: y + h },
        ];

        for (const c of corners) {
            if (!pointInPolygon(c.x, c.y, polygon)) return false;
        }

        for (let i = 0; i < 4; i++) {
            const rectP1 = corners[i];
            const rectP2 = corners[(i + 1) % 4];
            for (let j = 0; j < polygon.length; j++) {
                const polyP1 = polygon[j];
                const polyP2 = polygon[(j + 1) % polygon.length];
                if (segmentsIntersect(rectP1, rectP2, polyP1, polyP2)) return false;
            }
        }

        return true;
    }

    // True if the rect fits within the floor: falls back to plain bounding-box
    // containment when the floor has no custom shape.
    function rectFitsFloor(x, y, w, h, floor) {
        if (!floor.shape || floor.shape.length < 3) {
            return x >= 0 && y >= 0 && x + w <= floor.panjang && y + h <= floor.lebar;
        }
        return rectInPolygon(x, y, w, h, floor.shape);
    }

    // rot cycles through 4 quarter-turns (0°, 90°, 180°, 270°) so a room's
    // internal features (door/window/km) can face any of the 4 sides.
    // Footprint only depends on parity: odd rot swaps panjang/lebar.
    function kamarFootprint(tipeKamar, rot) {
        return (rot % 2 === 1) ? { w: tipeKamar.lebar, h: tipeKamar.panjang } : { w: tipeKamar.panjang, h: tipeKamar.lebar };
    }

    // Transform a feature's rect (defined relative to the un-rotated
    // panjang × lebar tipe kamar) into the frame for a given quarter-turn.
    function rotateFeatureRect(f, tipeKamar, rot) {
        const P = tipeKamar.panjang, L = tipeKamar.lebar;
        switch (rot % 4) {
            case 1: return { x: L - f.y - f.h, y: f.x, w: f.h, h: f.w };
            case 2: return { x: P - f.x - f.w, y: L - f.y - f.h, w: f.w, h: f.h };
            case 3: return { x: f.y, y: P - f.x - f.w, w: f.h, h: f.w };
            default: return { x: f.x, y: f.y, w: f.w, h: f.h };
        }
    }

    function currentFloor() {
        return floors.find(f => f.id === currentFloorId) || null;
    }

    // Looks a floor up by id explicitly (rather than assuming "the floor
    // that's currently on screen") — needed by the autosave code below,
    // which must always target the floor a change actually happened on.
    function findFloor(floorId) {
        return floors.find(f => f.id === floorId) || floors.find(f => String(f.id) === String(floorId)) || null;
    }

    function findUnplacedKamar(id) {
        return unplacedKamars.find(k => k.id === id) || null;
    }

    function computeScale(floor) {
        const $canvas = $('#denahCanvas');
        const areaW = $canvas.parent().width() || 700;
        const areaH = 500;
        return Math.min(areaW / floor.panjang, areaH / floor.lebar);
    }

    // ── Palette: existing unplaced kamars only, grouped by tipe kamar ──
    function renderPalette() {
        const $list = $('#paletteList').empty();

        if (!unplacedKamars.length) {
            $list.append('<p class="text-muted fs-8">Semua kamar sudah ditempatkan di denah.</p>');
            return;
        }

        const groups = {};
        unplacedKamars.forEach(k => {
            const groupName = k.tipe_kamar ? k.tipe_kamar.name : 'Tanpa Tipe';
            (groups[groupName] = groups[groupName] || []).push(k);
        });

        Object.keys(groups).forEach(groupName => {
            $list.append($('<div class="palette-group-title"></div>').text(groupName));
            groups[groupName].forEach(k => {
                const hasUkuran = k.tipe_kamar && k.tipe_kamar.panjang && k.tipe_kamar.lebar;
                const $item = $('<div class="palette-kamar border rounded p-2 mb-2"></div>')
                    .toggleClass('active', mode === 'place' && selectedKamarId === k.id)
                    .toggleClass('disabled', !hasUkuran)
                    .data('id', k.id)
                    .attr('title', hasUkuran ? '' : 'Atur ukuran tipe kamar dulu di Master Tipe Kamar')
                    .html(`<div class="fw-bold fs-8">${k.nomor}</div>`);
                $list.append($item);
            });
        });
    }

    function updateHint() {
        let hint = 'Klik kamar/lorong untuk memilih.';
        if (mode === 'place') hint = 'Klik kanvas untuk menempatkan kamar.';
        if (mode === 'area') hint = 'Seret di kanvas untuk menandai lorong.';
        $('#modeHint').text(hint);
        $('#btnToggleArea').toggleClass('active', mode === 'area');
    }

    // ── Floor tabs ──
    function renderFloorTabs() {
        const $tabs = $('#floorTabs').empty();
        floors.forEach(f => {
            const $btn = $('<button type="button" class="btn btn-sm"></button>')
                .addClass(f.id === currentFloorId ? 'btn-primary' : 'btn-light')
                .text(f.nama)
                .data('id', f.id);
            $tabs.append($btn);
        });
    }

    // ── Canvas render ──
    function renderCanvas() {
        const floor = currentFloor();
        const $canvas = $('#denahCanvas').empty();
        if (!floor) return;

        scale = computeScale(floor);
        $canvas.css({
            width: floor.panjang * scale + 'px',
            height: floor.lebar * scale + 'px',
            backgroundSize: scale + 'px ' + scale + 'px',
        });

        if (floor.shape && floor.shape.length >= 3) {
            const pointsAttr = floor.shape.map(p => `${p.x * scale},${p.y * scale}`).join(' ');
            const $svg = $(
                `<svg class="denah-shape-outline" style="position:absolute;left:0;top:0;pointer-events:none;">
                    <polygon points="${pointsAttr}" fill="none" stroke="#4a7fd4" stroke-width="2"/>
                </svg>`
            ).attr({ width: floor.panjang * scale, height: floor.lebar * scale });
            $canvas.append($svg);
        }

        // Preview ghost that follows the cursor while a kamar is selected for
        // placement — lets the user see where/whether the room will fit
        // before clicking, instead of guessing.
        $canvas.append('<div id="placementGhost" class="denah-placement-ghost" style="display:none;"></div>');

        (floor.areas || []).forEach(area => {
            const $a = $('<div class="denah-area"></div>')
                .attr('data-id', area.id)
                .attr('data-kind', 'area')
                .toggleClass('selected', selectedKind === 'area' && selectedId === area.id)
                .css({
                    left: area.x * scale + 'px',
                    top: area.y * scale + 'px',
                    width: area.w * scale + 'px',
                    height: area.h * scale + 'px',
                })
                .text(area.label);
            $a.append('<div class="resize-handle"></div>');
            $canvas.append($a);
        });

        (floor.kamars || []).forEach(kamar => {
            const rt = kamar.tipe_kamar;
            if (!rt || rt.panjang == null || rt.lebar == null) return;
            const { w, h } = kamarFootprint(rt, kamar.rot);
            const $k = $('<div class="denah-kamar"></div>')
                .attr('data-id', kamar.id)
                .attr('data-kind', 'kamar')
                .addClass('status-' + kamar.status)
                .toggleClass('selected', selectedKind === 'kamar' && selectedId === kamar.id)
                .css({
                    left: kamar.x * scale + 'px',
                    top: kamar.y * scale + 'px',
                    width: w * scale + 'px',
                    height: h * scale + 'px',
                });

            (rt.features || []).forEach(f => {
                const fr = rotateFeatureRect(f, rt, kamar.rot);
                $k.append(
                    $('<div class="denah-feature-mini"></div>')
                        .addClass('feature-' + f.type)
                        .css({
                            left: fr.x * scale + 'px',
                            top: fr.y * scale + 'px',
                            width: fr.w * scale + 'px',
                            height: fr.h * scale + 'px',
                        })
                );
            });

            $k.append($('<span class="denah-kamar-label"></span>').text(kamar.nomor));
            $canvas.append($k);
        });

        const luas = (floor.panjang * floor.lebar).toFixed(2);
        $('#denahCaption').text(`${floor.nama}: ${floor.panjang} × ${floor.lebar} m (${luas} m²) · ${(floor.kamars || []).length} kamar · ${(floor.areas || []).length} lorong/area`);
    }

    // ── Detail panel ──
    const STATUS_LABEL = { empty: 'Kosong', full: 'Terisi', maintenance: 'Maintenance' };

    function renderDetailPanel() {
        const $panel = $('#detailPanel');
        if (!selectedId) {
            $panel.html('<p class="text-muted fs-8">Klik kamar atau lorong untuk melihat detail.</p>');
            return;
        }

        const floor = currentFloor();

        if (selectedKind === 'kamar') {
            const kamar = floor.kamars.find(k => k.id === selectedId);
            if (!kamar) return;
            const tipeNama = kamar.tipe_kamar ? kamar.tipe_kamar.name : '-';
            $panel.html(`
                <div class="mb-2">
                    <div class="fw-bold fs-7">${kamar.nomor}</div>
                    <div class="text-muted fs-8">${tipeNama}</div>
                </div>
                <div class="mb-3">
                    <span class="badge badge-light-${kamar.status === 'full' ? 'primary' : (kamar.status === 'maintenance' ? 'warning' : 'success')}">${STATUS_LABEL[kamar.status] || kamar.status}</span>
                </div>
                <button type="button" class="btn btn-sm btn-light-primary w-100 mb-1" id="btnRotate">Putar (${(kamar.rot || 0) * 90}°)</button>
                <button type="button" class="btn btn-sm btn-light-danger w-100" id="btnLepasKamar">Lepas dari Denah</button>
            `);
        } else if (selectedKind === 'area') {
            const area = floor.areas.find(a => a.id === selectedId);
            if (!area) return;
            $panel.html(`
                <div class="mb-2">
                    <label class="form-label fs-8">Label</label>
                    <input type="text" class="form-control form-control-sm" id="detailAreaLabel" value="${area.label}">
                </div>
                <button type="button" class="btn btn-sm btn-light-danger w-100" id="btnDeleteArea">Hapus Lorong</button>
            `);
        }
    }

    function renderAll() {
        renderPalette();
        updateHint();
        renderFloorTabs();
        renderCanvas();
        renderDetailPanel();
    }

    renderAll();

    // ── Autosave (debounced per floor) ──────────────────────────────────
    // Each floor gets its OWN debounce timer, and which floor a save
    // targets is decided the moment the change happens (the caller always
    // passes currentFloorId in explicitly) — never resolved lazily off of
    // "whatever floor happens to be on screen when the timer fires".
    //
    // That lazy resolution was the actual bug: scheduleSave()/saveFloor()
    // used to read currentFloor() only once the 500ms timer fired. Placing
    // a kamar on Floor 2 and switching to the Floor 1 tab within that
    // window meant the save fired for Floor 1 (a no-op) — Floor 2's change
    // was silently never sent, so it reset on reload.
    function buildPayload(floor) {
        return {
            // Every kamar here always carries its real DB id — the canvas
            // never fabricates one, so there is nothing to distinguish here
            // (unlike the source feature this was ported from).
            kamars: (floor.kamars || []).map(k => ({
                id: k.id,
                x: k.x,
                y: k.y,
                rot: k.rot,
            })),
            areas: (floor.areas || []).map(a => ({
                id: typeof a.id === 'number' && a.id > 0 && !a._isNew ? a.id : null,
                x: a.x,
                y: a.y,
                w: a.w,
                h: a.h,
                label: a.label,
            })),
        };
    }

    function scheduleSave(floorId) {
        floorId = floorId != null ? floorId : currentFloorId;
        const key = String(floorId);
        clearTimeout(saveTimers[key]);
        saveTimers[key] = setTimeout(function () {
            delete saveTimers[key];
            saveFloor(floorId);
        }, 500);
    }

    function saveFloor(floorId) {
        floorId = floorId != null ? floorId : currentFloorId;
        const floor = findFloor(floorId);
        if (!floor) return;

        const url = config.routes.save.replace('__FLOOR__', floor.id);
        return $.post(url, buildPayload(floor)).fail(function (xhr) {
            console.error('Gagal menyimpan denah', xhr.responseJSON);
            showToast('Gagal menyimpan denah, coba lagi.');
        });
    }

    // Forces a floor's pending save to run right now instead of waiting out
    // the debounce. Used when switching away from that floor's tab, so a
    // change never sits waiting on a timer that might not get to fire.
    function flushSave(floorId) {
        const key = String(floorId);
        if (!(key in saveTimers)) return;
        clearTimeout(saveTimers[key]);
        delete saveTimers[key];
        saveFloor(floorId);
    }

    // Best-effort save for when the page is actually being left/closed: a
    // normal $.post can get cancelled mid-flight by the browser during
    // unload, so this uses sendBeacon instead, which is designed to survive it.
    function flushAllSavesOnUnload() {
        Object.keys(saveTimers).forEach(function (key) {
            clearTimeout(saveTimers[key]);
            delete saveTimers[key];
            const floor = findFloor(key);
            if (!floor) return;

            const url = config.routes.save.replace('__FLOOR__', floor.id);
            if (navigator.sendBeacon) {
                const payload = buildPayload(floor);
                payload._token = $('meta[name="csrf-token"]').attr('content');
                navigator.sendBeacon(url, new Blob([$.param(payload)], { type: 'application/x-www-form-urlencoded' }));
            } else {
                $.post(url, buildPayload(floor));
            }
        });
    }

    $(window).on('pagehide beforeunload', flushAllSavesOnUnload);

    // ── Floor tab switching ──
    $('#floorTabs').on('click', 'button', function () {
        const newFloorId = $(this).data('id');
        if (newFloorId === currentFloorId) return;
        flushSave(currentFloorId); // persist any pending change before leaving this floor
        currentFloorId = newFloorId;
        selectedId = null;
        selectedKind = null;
        renderAll();
    });

    // ── Palette: pick an existing kamar to place ──
    $('#paletteList').on('click', '.palette-kamar', function () {
        if ($(this).hasClass('disabled')) return;
        selectedKamarId = $(this).data('id');
        mode = 'place';
        renderAll();
    });

    $('#btnToggleArea').on('click', function () {
        mode = mode === 'area' ? 'select' : 'area';
        renderAll();
    });

    // ── Placement ghost preview: follows the cursor while a kamar is selected ──
    $('#denahCanvas').on('mousemove', function (e) {
        if (mode !== 'place' || !selectedKamarId || dragCtx || areaDrawCtx) {
            $('#placementGhost').hide();
            return;
        }
        const floor = currentFloor();
        const kamar = findUnplacedKamar(selectedKamarId);
        const rt = kamar ? kamar.tipe_kamar : null;
        if (!floor || !rt) return;

        const offset = $(this).offset();
        const cursorX = (e.pageX - offset.left) / scale;
        const cursorY = (e.pageY - offset.top) / scale;
        const x = snap(clamp(cursorX - rt.panjang / 2, 0, floor.panjang - rt.panjang));
        const y = snap(clamp(cursorY - rt.lebar / 2, 0, floor.lebar - rt.lebar));
        const valid = rectFitsFloor(x, y, rt.panjang, rt.lebar, floor);

        $('#placementGhost')
            .css({
                left: x * scale + 'px',
                top: y * scale + 'px',
                width: rt.panjang * scale + 'px',
                height: rt.lebar * scale + 'px',
                display: 'block',
            })
            .toggleClass('invalid', !valid);
    });

    $('#denahCanvas').on('mouseleave', function () {
        $('#placementGhost').hide();
    });

    // ── Place kamar / draw area / deselect ──
    $('#denahCanvas').on('mousedown', function (e) {
        if (e.target !== this) return;
        const floor = currentFloor();
        if (!floor) return;
        const offset = $(this).offset();
        canvasOffset = offset;

        if (mode === 'area') {
            const startX = snap(clamp((e.pageX - offset.left) / scale, 0, floor.panjang));
            const startY = snap(clamp((e.pageY - offset.top) / scale, 0, floor.lebar));
            areaDrawCtx = { startX, startY };
            const $preview = $('<div class="denah-area" id="areaPreview"></div>').css({
                left: startX * scale, top: startY * scale, width: 0, height: 0,
            });
            $(this).append($preview);
        }
    });

    $('#denahCanvas').on('click', function (e) {
        if (e.target !== this) return;
        const floor = currentFloor();
        if (!floor) return;

        if (mode === 'place' && selectedKamarId) {
            const kamar = findUnplacedKamar(selectedKamarId);
            if (!kamar || !kamar.tipe_kamar) return;
            const rt = kamar.tipe_kamar;
            const offset = $(this).offset();
            const clickX = (e.pageX - offset.left) / scale;
            const clickY = (e.pageY - offset.top) / scale;
            const x = snap(clamp(clickX - rt.panjang / 2, 0, floor.panjang - rt.panjang));
            const y = snap(clamp(clickY - rt.lebar / 2, 0, floor.lebar - rt.lebar));

            if (!rectFitsFloor(x, y, rt.panjang, rt.lebar, floor)) {
                showToast('Posisi di luar batas bentuk lantai.');
                return;
            }

            // Move the real kamar object from the unplaced palette straight
            // onto the floor — its id is never replaced or fabricated.
            unplacedKamars = unplacedKamars.filter(k => k.id !== kamar.id);
            kamar.x = x;
            kamar.y = y;
            kamar.rot = 0;
            floor.kamars = floor.kamars || [];
            floor.kamars.push(kamar);

            mode = 'select';
            selectedKamarId = null;
            renderAll();
            scheduleSave(currentFloorId);
        } else {
            selectedId = null;
            selectedKind = null;
            renderCanvas();
            renderDetailPanel();
        }
    });

    // ── Select + drag kamar/area ──
    $('#denahCanvas').on('mousedown', '.denah-kamar, .denah-area', function (e) {
        const kind = $(this).attr('data-kind');
        const idAttr = $(this).attr('data-id');
        const id = isNaN(idAttr) ? idAttr : parseInt(idAttr);
        const floor = currentFloor();
        const offset = $('#denahCanvas').offset();
        canvasOffset = offset;

        const isResize = $(e.target).hasClass('resize-handle');
        const item = kind === 'kamar'
            ? floor.kamars.find(k => k.id === id)
            : floor.areas.find(a => a.id === id);

        dragCtx = {
            kind, id, mode: isResize ? 'resize' : 'move',
            dxMeters: isResize ? 0 : (e.pageX - offset.left) / scale - item.x,
            dyMeters: isResize ? 0 : (e.pageY - offset.top) / scale - item.y,
            lastValidX: item.x, lastValidY: item.y, lastValidW: item.w, lastValidH: item.h,
        };
        e.stopPropagation();
    });

    $('#denahCanvas').on('click', '.denah-kamar, .denah-area', function (e) {
        e.stopPropagation();
        selectedKind = $(this).attr('data-kind');
        const idAttr = $(this).attr('data-id');
        selectedId = isNaN(idAttr) ? idAttr : parseInt(idAttr);
        renderCanvas();
        renderDetailPanel();
    });

    $(document).on('mousemove', function (e) {
        const floor = currentFloor();
        if (!floor) return;

        if (dragCtx) {
            const item = dragCtx.kind === 'kamar'
                ? floor.kamars.find(k => k.id === dragCtx.id)
                : floor.areas.find(a => a.id === dragCtx.id);
            if (!item) return;

            if (dragCtx.mode === 'move') {
                const mouseMeterX = (e.pageX - canvasOffset.left) / scale;
                const mouseMeterY = (e.pageY - canvasOffset.top) / scale;

                let w, h;
                if (dragCtx.kind === 'kamar') {
                    ({ w, h } = kamarFootprint(item.tipe_kamar, item.rot));
                } else {
                    w = item.w;
                    h = item.h;
                }

                item.x = clamp(snap(mouseMeterX - dragCtx.dxMeters), 0, floor.panjang - w);
                item.y = clamp(snap(mouseMeterY - dragCtx.dyMeters), 0, floor.lebar - h);
            } else if (dragCtx.mode === 'resize' && dragCtx.kind === 'area') {
                const mouseMeterW = (e.pageX - canvasOffset.left) / scale - item.x;
                const mouseMeterH = (e.pageY - canvasOffset.top) / scale - item.y;
                item.w = clamp(snap(mouseMeterW), 0.25, floor.panjang - item.x);
                item.h = clamp(snap(mouseMeterH), 0.25, floor.lebar - item.y);
            }
            renderCanvas();
        }

        if (areaDrawCtx) {
            const offset = canvasOffset;
            const curX = snap(clamp((e.pageX - offset.left) / scale, 0, floor.panjang));
            const curY = snap(clamp((e.pageY - offset.top) / scale, 0, floor.lebar));
            const x = Math.min(areaDrawCtx.startX, curX);
            const y = Math.min(areaDrawCtx.startY, curY);
            const w = Math.abs(curX - areaDrawCtx.startX);
            const h = Math.abs(curY - areaDrawCtx.startY);
            $('#areaPreview').css({ left: x * scale, top: y * scale, width: w * scale, height: h * scale });
            areaDrawCtx.current = { x, y, w, h };
        }
    });

    $(document).on('mouseup', function () {
        if (dragCtx) {
            const floor = currentFloor();
            const item = dragCtx.kind === 'kamar'
                ? floor.kamars.find(k => k.id === dragCtx.id)
                : floor.areas.find(a => a.id === dragCtx.id);

            let w, h;
            if (dragCtx.kind === 'kamar') {
                ({ w, h } = kamarFootprint(item.tipe_kamar, item.rot));
            } else {
                w = item.w;
                h = item.h;
            }

            if (rectFitsFloor(item.x, item.y, w, h, floor)) {
                scheduleSave(currentFloorId);
            } else {
                // Revert to the last known-valid position/size — the floor's
                // custom shape rejects this move/resize.
                item.x = dragCtx.lastValidX;
                item.y = dragCtx.lastValidY;
                if (dragCtx.mode === 'resize') {
                    item.w = dragCtx.lastValidW;
                    item.h = dragCtx.lastValidH;
                }
                renderCanvas();
            }
            dragCtx = null;
        }
        if (areaDrawCtx) {
            $('#areaPreview').remove();
            const rect = areaDrawCtx.current;
            if (rect && rect.w > 0.1 && rect.h > 0.1) {
                const floor = currentFloor();
                if (!rectFitsFloor(rect.x, rect.y, rect.w, rect.h, floor)) {
                    showToast('Lorong di luar batas bentuk lantai.');
                } else {
                    floor.areas = floor.areas || [];
                    floor.areas.push({
                        id: 'new-' + Date.now(), _isNew: true,
                        x: rect.x, y: rect.y, w: rect.w, h: rect.h, label: 'Lorong',
                    });
                    renderCanvas();
                    scheduleSave(currentFloorId);
                }
            }
            areaDrawCtx = null;
        }
    });

    // ── Detail panel interactions ──
    $('#detailPanel').on('input change', '#detailAreaLabel', function () {
        const floor = currentFloor();
        const area = floor.areas.find(a => a.id === selectedId);
        if (!area) return;
        area.label = $('#detailAreaLabel').val();
        renderCanvas();
        scheduleSave(currentFloorId);
    });

    $('#detailPanel').on('click', '#btnRotate', function () {
        const floor = currentFloor();
        const kamar = floor.kamars.find(k => k.id === selectedId);
        if (!kamar) return;
        const rt = kamar.tipe_kamar;

        const prevRot = kamar.rot, prevX = kamar.x, prevY = kamar.y;

        kamar.rot = (kamar.rot + 1) % 4;
        const { w, h } = kamarFootprint(rt, kamar.rot);
        kamar.x = clamp(kamar.x, 0, floor.panjang - w);
        kamar.y = clamp(kamar.y, 0, floor.lebar - h);

        if (rectFitsFloor(kamar.x, kamar.y, w, h, floor)) {
            renderCanvas();
            renderDetailPanel();
            scheduleSave(currentFloorId);
        } else {
            showToast('Tidak bisa diputar di posisi ini, keluar dari batas bentuk lantai.');
            kamar.rot = prevRot;
            kamar.x = prevX;
            kamar.y = prevY;
            renderCanvas();
        }
    });

    $('#detailPanel').on('click', '#btnLepasKamar', function () {
        const floor = currentFloor();
        const idx = floor.kamars.findIndex(k => k.id === selectedId);
        if (idx === -1) return;
        const [kamar] = floor.kamars.splice(idx, 1);
        kamar.x = null;
        kamar.y = null;
        kamar.rot = 0;
        unplacedKamars.push(kamar);
        selectedId = null;
        selectedKind = null;
        renderAll();
        scheduleSave(currentFloorId);
    });

    $('#detailPanel').on('click', '#btnDeleteArea', function () {
        const floor = currentFloor();
        floor.areas = floor.areas.filter(a => a.id !== selectedId);
        selectedId = null;
        selectedKind = null;
        renderCanvas();
        renderDetailPanel();
        scheduleSave(currentFloorId);
    });

    $(window).on('resize', function () {
        renderCanvas();
    });
});