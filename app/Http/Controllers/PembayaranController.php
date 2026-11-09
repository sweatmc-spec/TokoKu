<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePembayaranRequest;
use App\Models\PaymentMethod;
use App\Services\TerjualService;

class PembayaranController extends Controller
{
    public function __construct(private TerjualService $service)
    {
    }

    /** Halaman kasir: form transaksi baru. */
    public function create()
    {
        return view('Penjualan.Pembayaran.create', [
            'catalog'        => $this->service->catalog(),
            'paymentMethods' => PaymentMethod::orderBy('id')->get(['id', 'name']),
        ]);
    }

    /** Simpan transaksi + kurangi stok (satu DB::transaction di service). */
    public function store(StorePembayaranRequest $request)
    {
        $terjual = $this->service->store($request->validated(), $request->user()->id);

        return redirect()
            ->route('penjualan.terjual.index')
            ->with('success', "Transaksi {$terjual->code} berhasil disimpan.");
    }
}
