{{--
    Badge persen naik / turun dibanding periode sebelumnya.
    Variabel: $pct (?float), $naikBaik (bool: true kalau kenaikan itu kabar baik, mis. pemasukan; false untuk pengeluaran)
--}}
@if ($pct === null)
    <span class="text-muted fs-8">vs periode lalu: -</span>
@else
    @php
        $naik = $pct >= 0;
        $baik = $naik === $naikBaik;
    @endphp
    <span class="badge {{ $baik ? 'badge-light-success' : 'badge-light-danger' }} fs-8">
        <i class="ki-outline {{ $naik ? 'ki-arrow-up' : 'ki-arrow-down' }} fs-8"></i>
        {{ number_format(abs($pct), 1, ',', '.') }}%
    </span>
    <span class="text-muted fs-8">vs periode lalu</span>
@endif
