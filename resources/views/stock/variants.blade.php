@extends('layouts.app')

@section('title', $product->name . ' - Rincian Varian')

@section('content')
    {{-- ============ Ringkasan produk ============ --}}
    <div class="card mb-6">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-5">
                <div>
                    <a href="{{ route('stok.' . $category->slug) }}" class="text-gray-500 text-hover-primary fs-7">
                        &larr; Stok {{ $category->name }}
                    </a>
                    <h2 class="fw-bold mt-1 mb-3">{{ $product->name }}</h2>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge badge-light-primary">{{ $category->name }}</span>
                        @foreach ($product->sales as $sale)
                            <span class="badge badge-light">{{ $sale->name }}</span>
                        @endforeach
                    </div>
                </div>

                <div class="d-flex flex-wrap">
                    <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-4 mb-2 text-center">
                        <div class="fs-2 fw-bold">{{ number_format($product->stock, 0, ',', '.') }}</div>
                        <div class="fw-semibold fs-7 text-gray-500">Total stok (pcs)</div>
                    </div>
                    <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-4 mb-2 text-center">
                        <div class="fs-2 fw-bold">{{ number_format($summary['variant_stock'], 0, ',', '.') }}</div>
                        <div class="fw-semibold fs-7 text-gray-500">Stok di varian</div>
                    </div>
                    <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-4 mb-2 text-center">
                        <div class="fs-2 fw-bold">{{ number_format($summary['waiting'], 0, ',', '.') }}</div>
                        <div class="fw-semibold fs-7 text-gray-500">Menunggu datang</div>
                    </div>
                    <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 mb-2 text-center">
                        <div class="fs-2 fw-bold">{{ $summary['variants'] }}</div>
                        <div class="fw-semibold fs-7 text-gray-500">Varian</div>
                    </div>
                </div>
            </div>

            @if ($summary['unassigned'] > 0)
                <div class="fs-7 text-muted mt-4">
                    Belum dirinci ke varian: {{ number_format($summary['unassigned'], 0, ',', '.') }} pcs
                    (stok yang masuk sebelum varian dipakai di pembelian).
                </div>
            @endif
        </div>
    </div>

    {{-- ============ Filter + daftar varian ============ --}}
    <div class="card">
        <div class="card-header border-0 pt-6">
            <h3 class="card-title fw-bold mb-0">Daftar varian</h3>
        </div>

        <div class="card-body pt-0">
            {{-- Pilihan filter berasal dari Master Data > Variasi Pakaian; angka = jumlah varian produk ini --}}
            <form method="GET" action="{{ url()->current() }}" class="row g-4 align-items-end mb-6">
                @foreach ([['color', 'Warna'], ['size', 'Ukuran'], ['material', 'Bahan'], ['style', 'Model']] as [$key, $label])
                    <div class="col-lg-2 col-md-4 col-sm-6">
                        <label class="form-label fs-7 text-gray-600 mb-1">{{ $label }}</label>
                        <select name="{{ $key }}" class="form-select form-select-solid" onchange="this.form.submit()">
                            <option value="">Semua</option>
                            @foreach ($filterOptions[$key] as $opt)
                                <option value="{{ $opt['value'] }}"
                                        @selected($filters[$key] === $opt['value'])
                                        @disabled($opt['count'] === 0 && $filters[$key] !== $opt['value'])>
                                    {{ $opt['label'] }} ({{ $opt['count'] }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endforeach

                <div class="col-lg-2 col-md-4 col-sm-6">
                    <label class="form-label fs-7 text-gray-600 mb-1">Stok</label>
                    <select name="stock" class="form-select form-select-solid" onchange="this.form.submit()">
                        <option value="" @selected($filters['stock'] === '')>Semua</option>
                        <option value="available" @selected($filters['stock'] === 'available')>Ada stok</option>
                        <option value="empty" @selected($filters['stock'] === 'empty')>Stok habis</option>
                        <option value="waiting" @selected($filters['stock'] === 'waiting')>Menunggu datang</option>
                    </select>
                </div>

                <div class="col-lg-2 col-md-4 col-sm-6 d-flex gap-2">
                    <noscript><button type="submit" class="btn btn-primary">Terapkan</button></noscript>
                    @if ($filtersActive)
                        <a href="{{ url()->current() }}" class="btn btn-light">Reset filter</a>
                    @endif
                </div>
            </form>

            <div class="fs-7 text-gray-500 mb-3">
                Menampilkan <strong>{{ $variants->count() }}</strong> dari {{ $all->count() }} varian
            </div>

            <div class="table-responsive">
                <table class="table table-row-dashed align-middle text-center gy-5 mb-0">
                    <thead>
                        <tr class="fw-bold text-gray-500 text-uppercase fs-7">
                            <th class="text-center">Warna</th>
                            <th class="text-center">Ukuran</th>
                            <th class="text-center">Bahan</th>
                            <th class="text-center">Model</th>
                            <th class="text-center">Stok (pcs)</th>
                            <th class="text-center">Menunggu datang (pcs)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($variants as $variant)
                            @php $vWaiting = $pendingVariants[$variant->id] ?? 0; @endphp
                            <tr>
                                <td>
                                    <span class="d-inline-block rounded-circle align-middle me-1"
                                          style="width:12px;height:12px;border:1px solid rgba(128,128,128,.55);background: {{ $colorHex[mb_strtolower($variant->color)] ?? 'transparent' }}"></span>
                                    {{ $variant->color }}
                                </td>
                                <td class="fw-semibold">{{ $variant->size }}</td>
                                <td>{{ $variant->material ?: '-' }}</td>
                                <td>{{ $variant->style ?: '-' }}</td>
                                <td class="fw-semibold">
                                    @if ($variant->stock > 0)
                                        {{ number_format($variant->stock, 0, ',', '.') }}
                                    @else
                                        <span class="badge badge-light-danger">Habis</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($vWaiting > 0)
                                        <span class="badge badge-light-warning fs-7">+{{ number_format($vWaiting, 0, ',', '.') }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-10">
                                    @if ($all->isEmpty())
                                        Belum ada varian yang masuk stok. Varian muncul di sini setelah barangnya dicek di Cek Paket.
                                    @else
                                        Tidak ada varian yang cocok dengan filter.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                    @if ($variants->isNotEmpty())
                        <tfoot>
                            <tr class="fw-bold">
                                <td colspan="4" class="text-end">
                                    {{ $filtersActive ? 'Total hasil filter' : 'Total' }}
                                </td>
                                <td>{{ number_format($variants->sum('stock'), 0, ',', '.') }}</td>
                                <td>{{ number_format($variants->sum(fn ($v) => $pendingVariants[$v->id] ?? 0), 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
@endsection
