@extends('layouts.app')

@section('title', 'Kategori')

@section('content')
    <div class="card">
        <div class="card-header border-0 pt-6">
            <div class="card-title flex-column align-items-start">
                <h3 class="fw-bold m-0">Daftar Kategori</h3>
                <div class="text-muted fs-7 mt-1">Kategori produk beserta jumlah sales yang terhubung.</div>
            </div>
        </div>

        <div class="card-body py-4">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 gs-0">
                            <th class="w-50px">No</th>
                            <th class="min-w-175px">Nama</th>
                            <th class="min-w-250px">Deskripsi</th>
                            <th class="text-end min-w-125px">Jumlah Sales</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-700 fw-semibold">
                        @forelse ($categories as $category)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td class="text-gray-800 fw-bold">{{ $category->name }}</td>
                                <td class="text-gray-600">{{ $category->description ?: '-' }}</td>
                                <td class="text-end">
                                    <span class="badge badge-light-primary fs-7">{{ $category->sales_count }} Sales</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-15">
                                    <i class="ki-duotone ki-category fs-3x text-gray-400 mb-3">
                                        <span class="path1"></span><span class="path2"></span>
                                        <span class="path3"></span><span class="path4"></span>
                                    </i>
                                    <div class="text-gray-800 fw-bold fs-5">Belum ada kategori</div>
                                    <div class="text-muted fs-7 mt-1">
                                        Jalankan <code>php artisan db:seed --class=CategorySeeder</code>.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection