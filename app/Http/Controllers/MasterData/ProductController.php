<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\PurchaseItem;
use App\Models\Sales;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q'));

        $products = Product::with(['category:id,name,slug', 'sales:id,name'])
            ->withCount('variants')
            ->when($search !== '', fn ($q) => $q->whereLike('name', '%' . $search . '%'))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $categories = Category::orderBy('name')->get(['id', 'name']);

        // dipakai JS untuk membatasi pilihan sales sesuai kategori produk
        $salesList = Sales::with('categories:id')->orderBy('name')->get()
            ->map(fn ($s) => [
                'id'           => $s->id,
                'name'         => $s->name,
                'category_ids' => $s->categories->pluck('id')->values(),
            ])->values();

        return view('master-data.products.index', compact('products', 'categories', 'salesList', 'search'));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($data) {
            $product = Product::create([
                'category_id' => $data['category_id'],
                'name'        => $data['name'],
                'stock'       => 0,
            ]);
            $product->sales()->sync($data['sales_ids']);
        });

        return $this->success('Produk berhasil ditambahkan.');
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $data = $this->validated($request, $product);

        // Kategori tidak boleh berubah kalau produk sudah punya stok, varian, atau riwayat pembelian
        if ((int) $data['category_id'] !== (int) $product->category_id
            && ($product->stock > 0 || $product->variants()->exists() || $product->purchaseItems()->exists())) {
            return response()->json([
                'message' => 'Kategori tidak bisa diubah karena produk ini sudah punya stok, varian, atau riwayat pembelian.',
            ], 422);
        }

        // Sales yang dicabut tidak boleh masih punya pembelian berjalan berisi produk ini
        $removed = $product->sales()->pluck('sales.id')
            ->map(fn ($id) => (int) $id)
            ->diff($data['sales_ids']);

        if ($removed->isNotEmpty() && PurchaseItem::where('product_id', $product->id)
            ->whereHas('purchase', fn ($q) => $q->whereNull('completed_at')->whereIn('sales_id', $removed))
            ->exists()) {
            return response()->json([
                'message' => 'Sales yang dicabut masih punya pembelian yang belum selesai berisi produk ini. Selesaikan atau ubah pembelian itu dulu.',
            ], 422);
        }

        DB::transaction(function () use ($data, $product) {
            $product->update([
                'category_id' => $data['category_id'],
                'name'        => $data['name'],
            ]);
            $product->sales()->sync($data['sales_ids']);

            // nama di riwayat pembelian ikut mengikuti nama terbaru
            PurchaseItem::where('product_id', $product->id)->update(['product_name' => $data['name']]);
        });

        return $this->success('Produk berhasil diperbarui.');
    }

    public function destroy(Product $product): JsonResponse
    {
        if ($product->stock > 0) {
            return response()->json([
                'message' => 'Produk tidak bisa dihapus karena stoknya masih ada.',
            ], 422);
        }

        if ($product->variants()->where('stock', '>', 0)->exists()) {
            return response()->json([
                'message' => 'Produk tidak bisa dihapus karena masih ada varian yang punya stok.',
            ], 422);
        }

        $inPending = PurchaseItem::where('product_id', $product->id)
            ->whereHas('purchase', fn ($q) => $q->whereNull('completed_at'))
            ->exists();

        if ($inPending) {
            return response()->json([
                'message' => 'Produk tidak bisa dihapus karena dipakai di pembelian yang belum selesai.',
            ], 422);
        }

        // Riwayat pembelian yang sudah selesai tetap aman: nama produk tersimpan di item.
        $product->delete();

        return $this->success('Produk berhasil dihapus.');
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:150',
                Rule::unique('products', 'name')
                    ->where('category_id', $request->input('category_id'))
                    ->ignore($product?->id),
            ],
            'category_id'  => ['required', 'integer', 'exists:categories,id'],
            'sales_ids'    => ['required', 'array', 'min:1'],
            'sales_ids.*'  => ['integer', 'exists:sales,id'],
        ], [
            'name.required'        => 'Nama produk wajib diisi.',
            'name.max'             => 'Nama produk maksimal 150 karakter.',
            'name.unique'          => 'Produk dengan nama ini sudah ada di kategori tersebut.',
            'category_id.required' => 'Pilih kategori produk.',
            'category_id.exists'   => 'Kategori yang dipilih tidak valid.',
            'sales_ids.required'   => 'Pilih minimal satu sales.',
            'sales_ids.min'        => 'Pilih minimal satu sales.',
            'sales_ids.*.exists'   => 'Sales yang dipilih tidak valid.',
        ]);

        $ids = array_values(array_unique(array_map('intval', $data['sales_ids'])));

        // Setiap sales harus memang bekerja di kategori produk ini
        $valid = Sales::whereIn('id', $ids)
            ->whereHas('categories', fn ($q) => $q->where('categories.id', $data['category_id']))
            ->count();

        if ($valid !== count($ids)) {
            throw ValidationException::withMessages([
                'sales_ids' => 'Ada sales yang tidak bekerja di kategori yang dipilih.',
            ]);
        }

        $data['category_id'] = (int) $data['category_id'];
        $data['sales_ids']   = $ids;

        return $data;
    }

    private function success(string $message): JsonResponse
    {
        session()->flash('success', $message);

        return response()->json(['message' => $message]);
    }
}
