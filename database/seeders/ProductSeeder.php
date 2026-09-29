<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\SubCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'category' => 'Elektronik',
                'sub_category' => 'Smartphone',
                'name' => 'Smartphone X1',
                'descriptions' => 'Smartphone modern dengan performa tinggi dan desain elegan.',
                'price' => 4999000,
                'stock' => 25,
                'image' => 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'category' => 'Elektronik',
                'sub_category' => 'Laptop',
                'name' => 'Pro Laptop 14',
                'descriptions' => 'Laptop ringan dan powerful untuk pekerjaan, bisnis, dan produktivitas.',
                'price' => 12999000,
                'stock' => 15,
                'image' => 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'category' => 'Elektronik',
                'sub_category' => 'Aksesoris HP',
                'name' => 'Wireless Headset',
                'descriptions' => 'Headset wireless dengan desain nyaman dan kualitas audio jernih.',
                'price' => 799000,
                'stock' => 40,
                'image' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'category' => 'Fashion',
                'sub_category' => "Pakaian Pria",
                'name' => "Classic Men's Jacket",
                'descriptions' => 'Jaket pria dengan desain klasik yang cocok untuk berbagai kesempatan.',
                'price' => 599000,
                'stock' => 30,
                'image' => 'https://images.unsplash.com/photo-1551028719-00167b16eac5?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'category' => 'Fashion',
                'sub_category' => "Pakaian Wanita",
                'name' => "Women's Casual Dress",
                'descriptions' => 'Dress kasual wanita dengan desain modern dan nyaman digunakan.',
                'price' => 449000,
                'stock' => 35,
                'image' => 'https://images.unsplash.com/photo-1496747611176-843222e1e57c?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'category' => 'Rumah Tangga',
                'sub_category' => 'Furniture',
                'name' => 'Modern Sofa',
                'descriptions' => 'Sofa modern dengan desain minimalis untuk ruang keluarga.',
                'price' => 4999000,
                'stock' => 10,
                'image' => 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'category' => 'Rumah Tangga',
                'sub_category' => 'Kitchen',
                'name' => 'Kitchen Cookware Set',
                'descriptions' => 'Set peralatan masak lengkap untuk kebutuhan dapur sehari-hari.',
                'price' => 899000,
                'stock' => 20,
                'image' => 'https://images.unsplash.com/photo-1556911220-bff31c812dba?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'category' => 'Olahraga',
                'sub_category' => 'Fitness',
                'name' => 'Adjustable Dumbbell',
                'descriptions' => 'Dumbbell adjustable untuk latihan kekuatan di rumah.',
                'price' => 749000,
                'stock' => 25,
                'image' => 'https://images.unsplash.com/photo-1583454110551-21f2fa2afe61?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'category' => 'Olahraga',
                'sub_category' => 'Running',
                'name' => 'Running Shoes Pro',
                'descriptions' => 'Sepatu lari ringan dengan desain sporty dan nyaman.',
                'price' => 999000,
                'stock' => 30,
                'image' => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'category' => 'Olahraga',
                'sub_category' => 'Outdoor',
                'name' => 'Hiking Backpack',
                'descriptions' => 'Tas hiking dengan kapasitas besar untuk aktivitas outdoor.',
                'price' => 649000,
                'stock' => 18,
                'image' => 'https://images.unsplash.com/photo-1551632811-561732d1e306?auto=format&fit=crop&w=800&q=80',
            ],
        ];

        foreach ($products as $productData) {
            $category = Category::where('name', $productData['category'])->firstOrFail();

            $subCategory = SubCategory::where('name', $productData['sub_category'])
                ->where('category_id', $category->id)
                ->firstOrFail();

            Product::updateOrCreate(
                [
                    'slug' => Str::slug($productData['name']),
                ],
                [
                    'category_id' => $category->id,
                    'sub_category_id' => $subCategory->id,
                    'name' => $productData['name'],
                    'descriptions' => $productData['descriptions'],
                    'price' => $productData['price'],
                    'stock' => $productData['stock'],
                    'image' => $productData['image'],
                ]
            );
        }
    }
}
