<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\Inventory;
use App\Models\ProductVariant;

class InventoryController extends Controller
{
    private const LOW_STOCK_THRESHOLD = 5;

    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $stockStatus = $request->input('stock_status');

        $products = Product::query()
            ->with('category')
            ->when($search !== '', function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->when(
                $stockStatus === 'out_of_stock',
                fn ($query) => $query->where('stock', 0)
            )
            ->when(
                $stockStatus === 'low_stock',
                fn ($query) => $query
                    ->where('stock', '>', 0)
                    ->where('stock', '<=', self::LOW_STOCK_THRESHOLD)
            )
            ->when(
                $stockStatus === 'in_stock',
                fn ($query) => $query
                    ->where('stock', '>', self::LOW_STOCK_THRESHOLD)
            )
            ->orderBy('stock')
            ->orderBy('name')
            ->get();

        $stockCounts = [
            'out_of_stock' => Product::where('stock', 0)->count(),

            'low_stock' => Product::where('stock', '>', 0)
                ->where('stock', '<=', self::LOW_STOCK_THRESHOLD)
                ->count(),

            'in_stock' => Product::where(
                'stock',
                '>',
                self::LOW_STOCK_THRESHOLD
            )->count(),
        ];

        return view('admin.inventory.index', [
            'products' => $products,
            'search' => $search,
            'stockStatus' => $stockStatus,
            'stockCounts' => $stockCounts,
            'lowStockThreshold' => self::LOW_STOCK_THRESHOLD,
        ]);
    }

    public function updateStock(Request $request, Product $product)
    {
        $validated = $request->validate([
            'stock' => ['required', 'integer', 'min:0'],
        ], [
            'stock.required' => 'Stok wajib diisi.',
            'stock.integer' => 'Stok harus berupa angka.',
            'stock.min' => 'Stok tidak boleh kurang dari 0.',
        ]);

        $this->persistStockChange(
            $product,
            'set',
            (int) $validated['stock'],
            'Penyesuaian stok dari menu Inventory.'
        );

        return redirect()
            ->route('admin.inventory.index')
            ->with('success', "Stok produk {$product->name} berhasil diperbarui.");
    }

    public function adjustStock(Request $request, Product $product)
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
            'type' => ['required', 'in:add,subtract'],
        ], [
            'quantity.required' => 'Jumlah stok wajib diisi.',
            'quantity.integer' => 'Jumlah stok harus berupa angka.',
            'quantity.min' => 'Jumlah stok minimal 1.',
            'type.required' => 'Tipe perubahan stok wajib dipilih.',
            'type.in' => 'Tipe perubahan stok tidak valid.',
        ]);

        $this->persistStockChange(
            $product,
            $validated['type'],
            (int) $validated['quantity'],
            $validated['type'] === 'add'
                ? 'Penambahan stok dari menu Inventory.'
                : 'Pengurangan stok dari menu Inventory.'
        );

        return redirect()
            ->route('admin.inventory.index')
            ->with('success', "Stok produk {$product->name} berhasil diperbarui.");
    }
    
    /**
     * Sumber utama stok adalah inventories.quantity.
     * products.stock dipertahankan sebagai kolom kompatibilitas
     * untuk katalog lama yang belum memakai inventaris varian.
     */
    private function persistStockChange(
        Product $product,
        string $mode,
        int $amount,
        string $note
    ): void {
        DB::transaction(function () use ($product, $mode, $amount, $note) {
            $lockedProduct = Product::query()
                ->whereKey($product->getKey())
                ->lockForUpdate()
                ->firstOrFail();
    
            // Cari varian default, lalu fallback ke varian pertama.
            $variant = $lockedProduct->variants()
                ->orderByDesc('is_default')
                ->orderBy('id')
                ->lockForUpdate()
                ->first();
    
            // Produk dari seeder mungkin belum memiliki varian.
            if (! $variant) {
                $baseSku = 'LEGACY-' . $lockedProduct->id;
                $sku = $baseSku;
                $suffix = 1;
    
                while (ProductVariant::where('sku', $sku)->exists()) {
                    $sku = $baseSku . '-' . $suffix++;
                }
    
                $variant = $lockedProduct->variants()->create([
                    'sku' => $sku,
                    'name' => $lockedProduct->name,
                    'price' => $lockedProduct->price ?? 0,
                    'is_default' => true,
                    'status' => 'active',
                ]);
            }
    
            $inventory = Inventory::query()
                ->where('product_variant_id', $variant->id)
                ->lockForUpdate()
                ->first();
    
            // Migrasikan stok legacy, bukan menganggap stok awal selalu nol.
            if (! $inventory) {
                $legacyStock = max(0, (int) $lockedProduct->stock);
    
                $inventory = Inventory::create([
                    'product_variant_id' => $variant->id,
                    'quantity' => $legacyStock,
                    'reserved_quantity' => 0,
                    'reorder_level' => 0,
                ]);
    
                if ($legacyStock > 0) {
                    $variant->inventoryMovements()->create([
                        'type' => 'opening_stock',
                        'quantity' => $legacyStock,
                        'note' => 'Migrasi stok legacy produk ke inventaris varian.',
                        'created_by' => auth()->id(),
                    ]);
                }
            }
    
            $currentQuantity = (int) $inventory->quantity;
            $reservedQuantity = (int) $inventory->reserved_quantity;
    
            $newQuantity = match ($mode) {
                'set' => $amount,
                'add' => $currentQuantity + $amount,
                'subtract' => $currentQuantity - $amount,
                default => throw new \LogicException(
                    'Mode perubahan stok tidak valid.'
                ),
            };
    
            $field = $mode === 'set' ? 'stock' : 'quantity';
    
            if ($newQuantity < 0) {
                throw ValidationException::withMessages([
                    $field => 'Stok tidak mencukupi untuk pengurangan tersebut.',
                ]);
            }
    
            if ($newQuantity < $reservedQuantity) {
                throw ValidationException::withMessages([
                    $field => "Stok tidak boleh kurang dari stok yang direservasi ({$reservedQuantity}).",
                ]);
            }
    
            $difference = $newQuantity - $currentQuantity;
    
            if ($difference !== 0) {
                $inventory->update([
                    'quantity' => $newQuantity,
                ]);
    
                $variant->inventoryMovements()->create([
                    'type' => 'adjustment',
                    'quantity' => $difference,
                    'note' => $note,
                    'created_by' => auth()->id(),
                ]);
            }
    
            // Sinkronisasi untuk katalog dan checkout legacy.
            $lockedProduct->update([
                'stock' => $newQuantity,
                'price' => $variant->price,
            ]);
        });
    }
}
