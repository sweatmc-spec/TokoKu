@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
    <div class="card">
        <div class="card-body">
            <h2 class="fs-2hx fw-bold text-gray-900 mb-2">Selamat datang, {{ auth()->user()->name }} 👋</h2>
            <p class="text-gray-600 fs-6 mb-0">
                Ini halaman dashboard sementara — stat cards dan grafik akan kita tambahkan di step berikutnya.
            </p>
        </div>
    </div>
@endsection