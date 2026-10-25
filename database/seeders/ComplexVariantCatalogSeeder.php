<?php

namespace Database\Seeders;

use App\Models\Attribute as ProductAttribute;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductSpecification;
use App\Models\ProductVariant;
use App\Models\SubCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ComplexVariantCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = [
            [
                'category' => 'Rumah Tangga',
                'subcategory' => 'Peralatan Minum',
                'name' => 'Botol Minum Stainless Steel',
                'description' => 'Botol stainless steel berdinding ganda. Tinggi sekitar 18–30 cm, diameter 6.5–8 cm.',
                'image' => 'https://images.unsplash.com/photo-1602143407151-7111542de6e8?auto=format&fit=crop&w=800&q=80',
                'specs' => [
                    'Material' => 'Stainless steel 304',
                    'Insulasi' => 'Dinding ganda',
                    'Dimensi' => 'Tinggi 18–30 cm; diameter 6.5–8 cm',
                ],
                'attributes' => [
                    'Kapasitas' => [
                        'type' => 'select',
                        'values' => ['350 ml', '500 ml', '750 ml', '1000 ml'],
                    ],
                    'Warna' => [
                        'type' => 'color',
                        'values' => [
                            'Hitam' => '#222222',
                            'Putih' => '#F5F5F5',
                            'Biru' => '#2563EB',
                            'Hijau' => '#15803D',
                        ],
                    ],
                ],
                'variants' => [
                    ['sku' => 'BOT-350-BLK', 'name' => '350 ml / Hitam', 'price' => 79000, 'compare' => 99000, 'cost' => 42000, 'weight' => 0.250, 'stock' => 12, 'attrs' => ['Kapasitas' => '350 ml', 'Warna' => 'Hitam']],
                    ['sku' => 'BOT-350-WHT', 'name' => '350 ml / Putih', 'price' => 79000, 'compare' => 99000, 'cost' => 42000, 'weight' => 0.250, 'stock' => 8, 'attrs' => ['Kapasitas' => '350 ml', 'Warna' => 'Putih']],
                    ['sku' => 'BOT-500-BLK', 'name' => '500 ml / Hitam', 'price' => 99000, 'compare' => 129000, 'cost' => 53000, 'weight' => 0.320, 'stock' => 20, 'attrs' => ['Kapasitas' => '500 ml', 'Warna' => 'Hitam']],
                    ['sku' => 'BOT-500-BLU', 'name' => '500 ml / Biru', 'price' => 99000, 'compare' => 129000, 'cost' => 53000, 'weight' => 0.320, 'stock' => 15, 'attrs' => ['Kapasitas' => '500 ml', 'Warna' => 'Biru']],
                    ['sku' => 'BOT-750-GRN', 'name' => '750 ml / Hijau', 'price' => 119000, 'compare' => 149000, 'cost' => 65000, 'weight' => 0.410, 'stock' => 7, 'attrs' => ['Kapasitas' => '750 ml', 'Warna' => 'Hijau']],
                    ['sku' => 'BOT-1000-BLK', 'name' => '1000 ml / Hitam', 'price' => 149000, 'compare' => 179000, 'cost' => 79000, 'weight' => 0.480, 'stock' => 0, 'attrs' => ['Kapasitas' => '1000 ml', 'Warna' => 'Hitam']],
                ],
            ],
            [
                'category' => 'Olahraga',
                'subcategory' => 'Sepatu',
                'name' => 'Sneakers Urban',
                'description' => 'Sneakers harian dengan upper mesh dan outsole karet.',
                'image' => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=800&q=80',
                'specs' => [
                    'Material upper' => 'Mesh dan sintetis',
                    'Material outsole' => 'Karet',
                    'Dimensi' => 'Panjang sol sekitar 23–29 cm',
                ],
                'attributes' => [
                    'Ukuran' => [
                        'type' => 'select',
                        'values' => ['EU 38', 'EU 40', 'EU 42', 'EU 44'],
                    ],
                    'Warna' => [
                        'type' => 'color',
                        'values' => [
                            'Hitam' => '#222222',
                            'Putih' => '#F5F5F5',
                            'Merah' => '#DC2626',
                        ],
                    ],
                ],
                'variants' => [
                    ['sku' => 'SNK-38-BLK', 'name' => 'EU 38 / Hitam', 'price' => 249000, 'compare' => 299000, 'cost' => 145000, 'weight' => 0.550, 'stock' => 8, 'attrs' => ['Ukuran' => 'EU 38', 'Warna' => 'Hitam']],
                    ['sku' => 'SNK-38-WHT', 'name' => 'EU 38 / Putih', 'price' => 249000, 'compare' => 299000, 'cost' => 145000, 'weight' => 0.550, 'stock' => 5, 'attrs' => ['Ukuran' => 'EU 38', 'Warna' => 'Putih']],
                    ['sku' => 'SNK-40-BLK', 'name' => 'EU 40 / Hitam', 'price' => 269000, 'compare' => 319000, 'cost' => 155000, 'weight' => 0.620, 'stock' => 14, 'attrs' => ['Ukuran' => 'EU 40', 'Warna' => 'Hitam']],
                    ['sku' => 'SNK-40-RED', 'name' => 'EU 40 / Merah', 'price' => 269000, 'compare' => 319000, 'cost' => 155000, 'weight' => 0.620, 'stock' => 4, 'attrs' => ['Ukuran' => 'EU 40', 'Warna' => 'Merah']],
                    ['sku' => 'SNK-42-WHT', 'name' => 'EU 42 / Putih', 'price' => 289000, 'compare' => 349000, 'cost' => 169000, 'weight' => 0.700, 'stock' => 9, 'attrs' => ['Ukuran' => 'EU 42', 'Warna' => 'Putih']],
                    ['sku' => 'SNK-44-BLK', 'name' => 'EU 44 / Hitam', 'price' => 299000, 'compare' => 359000, 'cost' => 175000, 'weight' => 0.780, 'stock' => 3, 'attrs' => ['Ukuran' => 'EU 44', 'Warna' => 'Hitam']],
                ],
            ],
            [
                'category' => 'Fashion',
                'subcategory' => 'Kaos',
                'name' => 'Kaos Oversized Cotton',
                'description' => 'Kaos oversized cotton combed dengan pilihan ukuran dan warna.',
                'image' => 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&w=800&q=80',
                'specs' => [
                    'Material' => 'Cotton combed 24s',
                    'Fit' => 'Oversized',
                    'Dimensi' => 'Lebar dada 52–64 cm; panjang badan 70–80 cm',
                ],
                'attributes' => [
                    'Ukuran' => [
                        'type' => 'select',
                        'values' => ['S', 'M', 'L', 'XL', 'XXL'],
                    ],
                    'Warna' => [
                        'type' => 'color',
                        'values' => [
                            'Hitam' => '#222222',
                            'Putih' => '#F5F5F5',
                            'Navy' => '#1E3A8A',
                            'Abu-abu' => '#9CA3AF',
                        ],
                    ],
                ],
                'variants' => [
                    ['sku' => 'TSH-S-BLK', 'name' => 'S / Hitam', 'price' => 89000, 'compare' => 119000, 'cost' => 39000, 'weight' => 0.180, 'stock' => 20, 'attrs' => ['Ukuran' => 'S', 'Warna' => 'Hitam']],
                    ['sku' => 'TSH-M-WHT', 'name' => 'M / Putih', 'price' => 89000, 'compare' => 119000, 'cost' => 39000, 'weight' => 0.200, 'stock' => 16, 'attrs' => ['Ukuran' => 'M', 'Warna' => 'Putih']],
                    ['sku' => 'TSH-L-NVY', 'name' => 'L / Navy', 'price' => 99000, 'compare' => 129000, 'cost' => 44000, 'weight' => 0.230, 'stock' => 12, 'attrs' => ['Ukuran' => 'L', 'Warna' => 'Navy']],
                    ['sku' => 'TSH-XL-GRY', 'name' => 'XL / Abu-abu', 'price' => 109000, 'compare' => 139000, 'cost' => 49000, 'weight' => 0.260, 'stock' => 10, 'attrs' => ['Ukuran' => 'XL', 'Warna' => 'Abu-abu']],
                    ['sku' => 'TSH-XXL-BLK', 'name' => 'XXL / Hitam', 'price' => 119000, 'compare' => 149000, 'cost' => 54000, 'weight' => 0.300, 'stock' => 6, 'attrs' => ['Ukuran' => 'XXL', 'Warna' => 'Hitam']],
                ],
            ],
            [
                'category' => 'Elektronik',
                'subcategory' => 'Aksesori Komputer',
                'name' => 'Kabel USB-C Braided',
                'description' => 'Kabel USB-C braided untuk pengisian daya dan transfer data.',
                'image' => 'https://images.unsplash.com/photo-1583863788434-e58a36330cf0?auto=format&fit=crop&w=800&q=80',
                'specs' => [
                    'Material' => 'Nylon braided',
                    'Konektor' => 'USB-C ke USB-C',
                    'Dimensi' => 'Panjang 0.5 m, 1 m, atau 2 m',
                ],
                'attributes' => [
                    'Panjang' => [
                        'type' => 'select',
                        'values' => ['0.5 m', '1 m', '2 m'],
                    ],
                    'Warna' => [
                        'type' => 'color',
                        'values' => [
                            'Hitam' => '#222222',
                            'Putih' => '#F5F5F5',
                            'Biru' => '#2563EB',
                        ],
                    ],
                ],
                'variants' => [
                    ['sku' => 'CBL-05-BLK', 'name' => '0.5 m / Hitam', 'price' => 25000, 'compare' => 35000, 'cost' => 9000, 'weight' => 0.030, 'stock' => 35, 'attrs' => ['Panjang' => '0.5 m', 'Warna' => 'Hitam']],
                    ['sku' => 'CBL-1-BLK', 'name' => '1 m / Hitam', 'price' => 35000, 'compare' => 45000, 'cost' => 12000, 'weight' => 0.050, 'stock' => 28, 'attrs' => ['Panjang' => '1 m', 'Warna' => 'Hitam']],
                    ['sku' => 'CBL-1-WHT', 'name' => '1 m / Putih', 'price' => 35000, 'compare' => 45000, 'cost' => 12000, 'weight' => 0.050, 'stock' => 20, 'attrs' => ['Panjang' => '1 m', 'Warna' => 'Putih']],
                    ['sku' => 'CBL-2-BLU', 'name' => '2 m / Biru', 'price' => 59000, 'compare' => 69000, 'cost' => 21000, 'weight' => 0.090, 'stock' => 13, 'attrs' => ['Panjang' => '2 m', 'Warna' => 'Biru']],
                ],
            ],
        ];

        foreach ($catalog as $item) {
            $category = Category::updateOrCreate(
                ['slug' => Str::slug($item['category'])],
                ['name' => $item['category']]
            );

            $subcategory = SubCategory::updateOrCreate(
                [
                    'category_id' => $category->id,
                    'slug' => Str::slug($item['subcategory']),
                ],
                ['name' => $item['subcategory']]
            );

            $attributeValueMap = [];

            foreach ($item['attributes'] as $attributeName => $definition) {
                $attribute = ProductAttribute::updateOrCreate(
                    ['slug' => Str::slug($attributeName)],
                    [
                        'name' => $attributeName,
                        'type' => $definition['type'],
                        'is_variant' => true,
                        'sort_order' => 0,
                    ]
                );

                foreach ($definition['values'] as $key => $color) {
                    if (is_int($key)) {
                        $value = $color;
                        $hexColor = null;
                    } else {
                        $value = $key;
                        $hexColor = $color;
                    }

                    $attributeValue = AttributeValue::updateOrCreate(
                        [
                            'attribute_id' => $attribute->id,
                            'slug' => Str::slug($value),
                        ],
                        [
                            'value' => $value,
                            'hex_color' => $hexColor,
                            'sort_order' => 0,
                        ]
                    );

                    $attributeValueMap[$attributeName][$value] = $attributeValue;
                }
            }

            $product = Product::updateOrCreate(
                ['slug' => Str::slug($item['name'])],
                [
                    'category_id' => $category->id,
                    'sub_category_id' => $subcategory->id,
                    'name' => $item['name'],
                    'description' => $item['description'],
                    'price' => min(array_column($item['variants'], 'price')),
                    'stock' => array_sum(array_column($item['variants'], 'stock')),
                    'image' => $item['image'],
                ]
            );

            foreach ($item['specs'] as $specName => $specValue) {
                ProductSpecification::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'specification_group' => 'Spesifikasi',
                        'specification_name' => $specName,
                    ],
                    [
                        'specification_value' => $specValue,
                        'sort_order' => 0,
                    ]
                );
            }

            foreach ($item['variants'] as $index => $data) {
                $variant = ProductVariant::updateOrCreate(
                    ['sku' => $data['sku']],
                    [
                        'product_id' => $product->id,
                        'name' => $data['name'],
                        'price' => $data['price'],
                        'compare_at_price' => $data['compare'],
                        'cost_price' => $data['cost'],
                        'weight' => $data['weight'],
                        'is_default' => $index === 0,
                        'status' => 'active',
                    ]
                );

                $valueIds = [];

                foreach ($data['attrs'] as $attributeName => $value) {
                    $valueIds[] = $attributeValueMap[$attributeName][$value]->id;
                }

                $variant->attributeValues()->sync($valueIds);

                Inventory::updateOrCreate(
                    ['product_variant_id' => $variant->id],
                    [
                        'quantity' => $data['stock'],
                        'reserved_quantity' => 0,
                        'reorder_level' => 3,
                    ]
                );
            }
        }

        $this->command?->info(
            'Complex variant catalog seeded: 4 products and 21 variants.'
        );
    }
}
