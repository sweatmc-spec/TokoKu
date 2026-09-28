$(function () {
    const SCALE = 60; // px per meter, fixed for the type editor
    const SNAP = 0.1;

    const state = window.TIPE_KAMAR_DENAH_DATA || { panjang: 3, lebar: 3, features: [] };
    let selectedFeatureIndex = null;
    let dragCtx = null;
    let canvasOffset = { left: 0, top: 0 };

    function snap(v) { return Math.round(v / SNAP) * SNAP; }
    function clamp(v, min, max) { return Math.max(min, Math.min(max, v)); }

    function renderCanvas() {
        const $canvas = $('#typeCanvas').empty();

        $canvas.css({ width: state.panjang * SCALE + 'px', height: state.lebar * SCALE + 'px' });

        state.features.forEach((f, index) => {
            const $f = $('<div class="type-feature"></div>')
                .addClass('feature-' + f.type)
                .attr('data-index', index)
                .css({
                    left: f.x * SCALE + 'px',
                    top: f.y * SCALE + 'px',
                    width: f.w * SCALE + 'px',
                    height: f.h * SCALE + 'px',
                })
                .text(f.type.toUpperCase());

            if (index === selectedFeatureIndex) $f.addClass('selected');

            $f.append('<div class="resize-handle"></div>');
            $canvas.append($f);
        });
    }

    function renderFeaturePanel() {
        const feature = selectedFeatureIndex !== null ? state.features[selectedFeatureIndex] : null;
        if (!feature) {
            $('#featurePanel').hide();
            return;
        }
        $('#featurePanel').show();
        $('#featureW').val(feature.w);
        $('#featureH').val(feature.h);
    }

    renderCanvas();
    renderFeaturePanel();

    // ── Ukuran (panjang/lebar) inputs ──
    $('#inputPanjang, #inputLebar').on('input', function () {
        state.panjang = parseFloat($('#inputPanjang').val()) || 0.1;
        state.lebar = parseFloat($('#inputLebar').val()) || 0.1;

        // Re-clamp all features within the (possibly shrunk) new bounds.
        state.features.forEach(f => {
            f.x = clamp(f.x, 0, Math.max(0, state.panjang - f.w));
            f.y = clamp(f.y, 0, Math.max(0, state.lebar - f.h));
        });

        renderCanvas();
    });

    // ── Add feature buttons ──
    function addFeature(type) {
        const w = type === 'km' ? 1.2 : 0.8;
        const h = type === 'km' ? 1.5 : 0.2;
        state.features.push({
            type,
            x: clamp(0.4, 0, Math.max(0, state.panjang - w)),
            y: clamp(0.4, 0, Math.max(0, state.lebar - h)),
            w,
            h,
        });
        renderCanvas();
    }
    $('#btnAddKm').on('click', () => addFeature('km'));
    $('#btnAddPintu').on('click', () => addFeature('pintu'));
    $('#btnAddJendela').on('click', () => addFeature('jendela'));

    // ── Feature manual W/H inputs ──
    $('#featureW, #featureH').on('input', function () {
        if (selectedFeatureIndex === null) return;
        const f = state.features[selectedFeatureIndex];
        f.w = clamp(parseFloat($('#featureW').val()) || 0.1, 0.1, state.panjang);
        f.h = clamp(parseFloat($('#featureH').val()) || 0.1, 0.1, state.lebar);
        f.x = clamp(f.x, 0, state.panjang - f.w);
        f.y = clamp(f.y, 0, state.lebar - f.h);
        renderCanvas();
    });

    $('#btnDeleteFeature').on('click', function () {
        if (selectedFeatureIndex === null) return;
        state.features.splice(selectedFeatureIndex, 1);
        selectedFeatureIndex = null;
        renderCanvas();
        renderFeaturePanel();
    });

    // ── Drag / resize on canvas ──
    $('#typeCanvas').on('mousedown', '.type-feature', function (e) {
        const featureIndex = parseInt($(this).attr('data-index'));
        selectedFeatureIndex = featureIndex;
        renderFeaturePanel();
        $('.type-feature').removeClass('selected');
        $(this).addClass('selected');

        const isResize = $(e.target).hasClass('resize-handle');
        const offset = $('#typeCanvas').offset();
        canvasOffset = offset;

        const f = state.features[featureIndex];

        dragCtx = {
            mode: isResize ? 'resize' : 'move',
            featureIndex,
            dxMeters: isResize ? 0 : (e.pageX - offset.left) / SCALE - f.x,
            dyMeters: isResize ? 0 : (e.pageY - offset.top) / SCALE - f.y,
        };
        e.preventDefault();
    });

    $(document).on('mousemove', function (e) {
        if (!dragCtx) return;
        const f = state.features[dragCtx.featureIndex];

        if (dragCtx.mode === 'move') {
            const mouseMeterX = (e.pageX - canvasOffset.left) / SCALE;
            const mouseMeterY = (e.pageY - canvasOffset.top) / SCALE;
            f.x = clamp(snap(mouseMeterX - dragCtx.dxMeters), 0, state.panjang - f.w);
            f.y = clamp(snap(mouseMeterY - dragCtx.dyMeters), 0, state.lebar - f.h);
        } else if (dragCtx.mode === 'resize') {
            const mouseMeterW = (e.pageX - canvasOffset.left) / SCALE - f.x;
            const mouseMeterH = (e.pageY - canvasOffset.top) / SCALE - f.y;
            f.w = clamp(snap(mouseMeterW), 0.1, state.panjang - f.x);
            f.h = clamp(snap(mouseMeterH), 0.1, state.lebar - f.y);
        }
        renderCanvas();
        renderFeaturePanel();
    });

    $(document).on('mouseup', function () {
        dragCtx = null;
    });

    // ── Serialize features into the hidden input right before submit ──
    $('#formDenah').on('submit', function () {
        $('#inputFeatures').val(JSON.stringify(state.features));
        const btn = document.getElementById('btnSaveType');
        if (btn) {
            btn.setAttribute('data-kt-indicator', 'on');
            btn.disabled = true;
        }
    });
});
