{{--
    Filter periode bersama untuk semua laporan. Harus berada di dalam <form method="GET" id="form-laporan">.
    Variabel: $periode (App\Support\Laporan\PeriodeLaporan)

    Mengirim: periode=<preset>, dan dari + sampai (hanya kalau preset = rentang).
    Rentang tanggal memakai daterangepicker bawaan Metronic kalau tersedia di bundle;
    kalau tidak, dua input tanggal biasa tetap berfungsi.
--}}
@php $rentang = $periode->key === 'rentang'; @endphp

<div>
    <label class="form-label fs-8 fw-semibold text-muted mb-1" for="lp-periode">Periode</label>
    <select name="periode" id="lp-periode" class="form-select form-select-sm w-175px">
        @foreach (\App\Support\Laporan\PeriodeLaporan::PRESETS as $kode => $label)
            <option value="{{ $kode }}" @selected($periode->key === $kode)>{{ $label }}</option>
        @endforeach
    </select>
</div>

<div id="lp-rentang" class="{{ $rentang ? '' : 'd-none' }}">
    <label class="form-label fs-8 fw-semibold text-muted mb-1">Rentang tanggal</label>

    {{-- cadangan: dua input tanggal biasa (disembunyikan kalau daterangepicker tersedia) --}}
    <div id="lp-native" class="d-flex align-items-center gap-2">
        <input type="date" name="dari" id="lp-dari" value="{{ $rentang ? $periode->fromDate() : '' }}"
               class="form-control form-control-sm w-150px" title="Dari tanggal" @disabled(! $rentang)>
        <span class="text-muted">s/d</span>
        <input type="date" name="sampai" id="lp-sampai" value="{{ $rentang ? $periode->toDate() : '' }}"
               class="form-control form-control-sm w-150px" title="Sampai tanggal" @disabled(! $rentang)>
    </div>

    <input type="text" id="lp-picker" class="form-control form-control-sm w-225px d-none" readonly
           placeholder="Pilih rentang tanggal">
</div>

@push('scripts')
<script>
(function () {
    const $periode = $('#lp-periode');
    const $range   = $('#lp-rentang');
    const $form    = $('#form-laporan');

    function toggleRentang() {
        const aktif = $periode.val() === 'rentang';
        $range.toggleClass('d-none', !aktif);
        $range.find('input[name=dari], input[name=sampai]').prop('disabled', !aktif);   // tidak ikut terkirim kalau bukan rentang
    }

    $periode.on('change', function () {
        toggleRentang();
        if ($periode.val() !== 'rentang') $form.trigger('submit');     // preset: langsung terapkan
    });
    toggleRentang();

    // daterangepicker bawaan Metronic (jQuery + moment), kalau ada di bundle
    if ($.fn.daterangepicker && window.moment) {
        const $dari = $('#lp-dari'), $sampai = $('#lp-sampai'), $picker = $('#lp-picker');
        const start = $dari.val() ? moment($dari.val()) : moment().startOf('month');
        const end   = $sampai.val() ? moment($sampai.val()) : moment();

        $('#lp-native').addClass('d-none');
        $picker.removeClass('d-none');
        $dari.val(start.format('YYYY-MM-DD'));
        $sampai.val(end.format('YYYY-MM-DD'));

        $picker.daterangepicker({
            startDate: start,
            endDate: end,
            autoApply: true,
            locale: { format: 'DD/MM/YYYY', separator: ' - ' },
        }, function (a, b) {
            $dari.val(a.format('YYYY-MM-DD'));
            $sampai.val(b.format('YYYY-MM-DD'));
        });
    }
})();
</script>
@endpush
