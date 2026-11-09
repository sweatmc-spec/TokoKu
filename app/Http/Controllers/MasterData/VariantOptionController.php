<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Models\PurchaseItem;
use App\Models\VariantOption;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VariantOptionController extends Controller
{
    /** Pengaturan halaman per jenis. Key = type = nama kolom di product_variants. */
    private const META = [
        'color'    => ['title' => 'Warna',  'lower' => 'warna',  'slug' => 'master-warna',  'hex' => true],
        'size'     => ['title' => 'Ukuran', 'lower' => 'ukuran', 'slug' => 'master-ukuran', 'hex' => false],
        'material' => ['title' => 'Bahan',  'lower' => 'bahan',  'slug' => 'master-bahan',  'hex' => false],
        'style'    => ['title' => 'Model',  'lower' => 'model',  'slug' => 'master-model',  'hex' => false],
    ];

    public function index(string $type)
    {
        $meta = $this->meta($type);

        $options = VariantOption::ofType($type)->ordered()->get();

        // berapa varian yang memakai tiap pilihan (kolom $type sudah divalidasi lewat META)
        $usage = ProductVariant::query()
            ->selectRaw("LOWER($type) as k, COUNT(*) as c")
            ->whereNotNull($type)
            ->groupBy(DB::raw("LOWER($type)"))
            ->pluck('c', 'k');

        return view('master-data.variant-options.index', compact('type', 'meta', 'options', 'usage'));
    }

    public function store(Request $request, string $type): JsonResponse
    {
        $meta = $this->meta($type);
        $data = $this->validated($request, $type, $meta);

        VariantOption::create([
            'type'       => $type,
            'name'       => $data['name'],
            'hex'        => $meta['hex'] ? $data['hex'] : null,
            'sort_order' => $data['sort_order'] ?? ((int) VariantOption::ofType($type)->max('sort_order') + 1),
        ]);

        return $this->success($meta['title'] . ' berhasil ditambahkan.');
    }

    // PENTING: urutan argumen mengikuti urutan parameter route. Laravel mengisi argumen berurutan:
    // parameter dari URL ({option}) dulu, baru parameter dari ->defaults() ($type).
    public function update(Request $request, VariantOption $option, string $type): JsonResponse
    {
        $meta = $this->meta($type);
        abort_unless($option->type === $type, 404);

        $data = $this->validated($request, $type, $meta, $option);
        $old  = $option->name;

        DB::transaction(function () use ($option, $data, $meta, $type, $old) {
            // nama baru ikut diterapkan ke varian yang sudah memakai nama lama
            if ($old !== $data['name'] && ! $this->renameInVariants($type, $old, $data['name'])) {
                abort(422, 'Nama tidak bisa diubah karena akan membuat kombinasi varian ganda di salah satu produk.');
            }

            $option->update([
                'name'       => $data['name'],
                'hex'        => $meta['hex'] ? $data['hex'] : null,
                'sort_order' => $data['sort_order'] ?? $option->sort_order,
            ]);
        });

        return $this->success($meta['title'] . ' berhasil diperbarui.');
    }

    public function destroy(VariantOption $option, string $type): JsonResponse
    {
        $meta = $this->meta($type);
        abort_unless($option->type === $type, 404);

        $used = ProductVariant::whereRaw("LOWER($type) = ?", [mb_strtolower($option->name)])->count();

        if ($used > 0) {
            return response()->json([
                'message' => "{$meta['title']} tidak bisa dihapus karena masih dipakai {$used} varian.",
            ], 422);
        }

        $option->delete();

        return $this->success($meta['title'] . ' berhasil dihapus.');
    }

    /* ------------------------------------------------------------------ */

    private function meta(string $type): array
    {
        abort_unless(isset(self::META[$type]), 404);

        return self::META[$type];
    }

    private function validated(Request $request, string $type, array $meta, ?VariantOption $option = null): array
    {
        $rules = [
            'name' => [
                'required', 'string', 'max:50',
                function ($attribute, $value, $fail) use ($type, $option) {
                    $exists = VariantOption::ofType($type)
                        ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim((string) $value))])
                        ->when($option, fn ($q) => $q->where('id', '!=', $option->id))
                        ->exists();

                    if ($exists) {
                        $fail('Nama ini sudah ada.');
                    }
                },
            ],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];

        if ($meta['hex']) {
            $rules['hex'] = ['required', 'regex:/^#[0-9a-fA-F]{6}$/'];
        }

        $data = $request->validate($rules, [
            'name.required'     => 'Nama ' . $meta['lower'] . ' wajib diisi.',
            'name.max'          => 'Nama maksimal 50 karakter.',
            'sort_order.integer' => 'Urutan harus berupa angka.',
            'sort_order.min'    => 'Urutan minimal 0.',
            'sort_order.max'    => 'Urutan maksimal 9999.',
            'hex.required'      => 'Pilih kode warna.',
            'hex.regex'         => 'Kode warna tidak valid.',
        ]);

        $data['name'] = trim((string) preg_replace('/\s+/u', ' ', $data['name']));
        $data['hex']  = isset($data['hex']) ? strtolower($data['hex']) : null;

        return $data;
    }

    /**
     * Ganti nama lama -> baru di semua varian yang memakainya (termasuk variant_key).
     * Mengembalikan false kalau perubahan itu membuat kombinasi ganda dalam satu produk.
     */
    private function renameInVariants(string $type, string $old, string $new): bool
    {
        $variants = ProductVariant::whereRaw("LOWER($type) = ?", [mb_strtolower($old)])->get();

        if ($variants->isEmpty()) {
            return true;
        }

        $newKeys = [];
        foreach ($variants as $v) {
            $attrs = ['size' => $v->size, 'color' => $v->color, 'material' => $v->material, 'style' => $v->style];
            $attrs[$type] = $new;
            $newKeys[$v->id] = ProductVariant::makeKey($attrs['size'], $attrs['color'], $attrs['material'], $attrs['style']);
        }

        foreach ($variants->groupBy('product_id') as $productId => $group) {
            $keys   = $group->map(fn ($v) => $newKeys[$v->id])->values();
            $others = ProductVariant::where('product_id', $productId)
                ->whereNotIn('id', $group->pluck('id'))
                ->pluck('variant_key');

            if ($keys->duplicates()->isNotEmpty() || $keys->intersect($others)->isNotEmpty()) {
                return false;
            }
        }

        foreach ($variants as $v) {
            $v->forceFill([$type => $new, 'variant_key' => $newKeys[$v->id]])->save();

            PurchaseItem::where('product_variant_id', $v->id)->update(['variant_label' => $v->label()]);
        }

        return true;
    }

    private function success(string $message): JsonResponse
    {
        session()->flash('success', $message);

        return response()->json(['message' => $message]);
    }
}
