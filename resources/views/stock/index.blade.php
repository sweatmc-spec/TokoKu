@extends('layouts.app')

@section('title', 'Stok ' . $category->name)

@section('content')
    <div class="card">
        <div class="card-header border-0 pt-6 d-flex justify-content-between align-items-center flex-wrap gap-3">
            <h3 class="card-title fw-bold mb-0">Stok {{ $category->name }}</h3>

            <form method="GET" action="{{ url()->current() }}" class="d-flex align-items-center gap-2">
                <div class="position-relative">
                    <i class="ki-outline ki-magnifier fs-3 text-gray-500 position-absolute top-50 translate-middle-y ms-4"></i>
                    <input type="text" name="q" value="{{ $search }}" class="form-control form-control-solid w-250px ps-12"
                        placeholder="Cari produk..." autocomplete="off">
                </div>
                <button type="submit" class="btn btn-primary">Cari</button>
                @if ($search !== '')
                    <a href="{{ url()->current() }}" class="btn btn-light">Reset</a>
                @endif
            </form>
        </div>

        <div class="card-body pt-0">
            <div class="table-responsive">
                <table class="table table-row-dashed align-middle text-center gy-5 mb-0">
                    <thead>
                        <tr class="fw-bold text-gray-500 text-uppercase fs-7">
                            <th class="w-60px text-center">No</th>
                            <th class="text-center">Produk</th>
                            <th class="text-center">Stok (pcs)</th>
                            <th class="text-center">Menunggu datang (pcs)</th>
                            <th class="text-center">Harga Jual</th>
                            <th class="text-center">Terakhir masuk</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($products as $product)
                            @php
                                $waiting      = $pending[$product->id] ?? 0;
                                $variantCount = $supportsVariants ? (int) $product->variants_count : 0;
                                $detailRoute  = 'stok.' . $category->slug . '.show';
                            @endphp
                            <tr>
                                <td>{{ $products->firstItem() + $loop->index }}</td>
                                <td>
                                    <span class="fw-semibold">{{ $product->name }}</span>
                                    @if ($variantCount > 0)
                                        @if (\Illuminate\Support\Facades\Route::has($detailRoute))
                                            {{-- rincian varian dibuka di halaman sendiri --}}
                                            <a href="{{ route($detailRoute, $product) }}" class="btn btn-sm btn-link p-0 d-block mx-auto">
                                                Rincian {{ $variantCount }} varian
                                            </a>
                                        @else
                                            {{-- route belum ada di web.php: jumlah varian tetap tampil (bukan hilang diam-diam) --}}
                                            <span class="badge badge-light-warning d-table mx-auto mt-1"
                                                  title="Route {{ $detailRoute }} belum didaftarkan di routes/web.php">{{ $variantCount }} varian</span>
                                        @endif
                                    @elseif ($supportsVariants)
                                        <span class="badge badge-light-secondary text-muted d-table mx-auto mt-1">Belum ada varian</span>
                                    @endif
                                </td>
                                <td class="fw-semibold">{{ number_format($product->stock, 0, ',', '.') }}</td>
                                <td>
                                    @if ($waiting > 0)
                                        <span class="badge badge-light-warning fs-7">+{{ number_format($waiting, 0, ',', '.') }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    {{-- Harga Jual = Profit Harga yang dipilih saat Tambah Produk (relasi stok -> profit harga) --}}
                                    @if ($product->profitHarga)
                                        <span class="fw-semibold">{{ $product->profitHarga->harga_formatted }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($product->last_received_at)
                                        {{ $product->last_received_at->format('d/m/Y') }}
                                    @elseif ($waiting > 0)
                                        <span class="badge badge-light-secondary text-muted">Belum datang</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-10">
                                    @if ($search !== '')
                                        Tidak ada produk yang cocok dengan "{{ $search }}".
                                    @else
                                        Belum ada stok {{ $category->name }}. Produk muncul di sini setelah barangnya dicek
                                        (dicentang datang) di Cek Paket.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-5">
                {{ $products->links() }}
            </div>
        </div>
    </div>
@endsection
