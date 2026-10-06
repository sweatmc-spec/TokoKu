@extends('layouts.app')

@section('title', 'Pembayaran')

@section('content')
    @include('Penjualan.Pembayaran.partials._form', [
        'terjual'     => null,
        'action'      => route('penjualan.pembayaran.store'),
        'method'      => 'POST',
        'submitLabel' => 'Proses pembayaran',
    ])
@endsection
