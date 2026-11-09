<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateTerjualRequest;
use App\Models\PaymentMethod;
use App\Models\Terjual;
use App\Services\TerjualService;
use Illuminate\Http\Request;

class TerjualController extends Controller
{
    public function __construct(private TerjualService $service)
    {
    }

    public function index(Request $request)
    {
        $request->validate([
            'dari'   => ['nullable', 'date'],
            'sampai' => ['nullable', 'date'],
        ]);

        $search = trim((string) $request->query('q'));
        $dari   = $request->query('dari');
        $sampai = $request->query('sampai');

        $base = Terjual::query()->search($search)->between($dari, $sampai);

        // ringkasan mengikuti filter yang sedang aktif
        $summary = [
            'count'   => (clone $base)->count(),
            'revenue' => (int) (clone $base)->sum('total'),
        ];

        $terjuals = $base
            ->with('paymentMethod:id,name')
            ->withCount('items')
            ->orderByDesc('sold_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('Penjualan.Terjual.index', compact('terjuals', 'search', 'dari', 'sampai', 'summary'));
    }

    /** Isi modal "Detail". Hanya dipanggil lewat fetch dari index, hasilnya potongan HTML. */
    public function show(Request $request, Terjual $terjual)
    {
        abort_unless($request->ajax(), 404);

        $terjual->load(['items', 'user:id,name', 'paymentMethod:id,name']);

        return view('Penjualan.Terjual.partials._detail', compact('terjual'));
    }

    public function edit(Terjual $terjual)
    {
        $terjual->load('items');

        return view('Penjualan.Terjual.edit', [
            'terjual'        => $terjual,
            'catalog'        => $this->service->catalog($terjual),
            'paymentMethods' => PaymentMethod::orderBy('id')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateTerjualRequest $request, Terjual $terjual)
    {
        $this->service->update($terjual, $request->validated());

        return redirect()
            ->route('penjualan.terjual.index')
            ->with('success', "Transaksi {$terjual->code} berhasil diperbarui.");
    }

    public function destroy(Terjual $terjual)
    {
        $code = $terjual->code;

        $this->service->destroy($terjual);

        return redirect()
            ->route('penjualan.terjual.index')
            ->with('success', "Transaksi {$code} dihapus dan stok barangnya dikembalikan.");
    }
}
