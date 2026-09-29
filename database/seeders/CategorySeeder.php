<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Elektronik',
                'image' => 'https://images.unsplash.com/photo-1498049794561-7780e7231661?auto=format&fit=crop&w=800&q=80',
                'description' => 'Berbagai macam produk elektronik dan aksesoris teknologi.',
            ],
            [
                'name' => 'Fashion',
                'image' => 'https://images.unsplash.com/photo-1445205170230-053b83016050?auto=format&fit=crop&w=800&q=80',
                'description' => 'Produk fashion pria dan wanita.',
            ],
            [
                'name' => 'Rumah Tangga',
                'image' => 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=800&q=80',
                'description' => 'Berbagai kebutuhan rumah tangga sehari-hari.',
            ],
            [
                'name' => 'Komputer',
                'image' => 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=800&q=80',
                'description' => 'Perangkat komputer dan aksesoris pendukungnya.',
            ],
            [
                'name' => 'Olahraga',
                'image' => 'https://images.unsplash.com/photo-1461896836934-ffe607ba8211?auto=format&fit=crop&w=800&q=80',
                'description' => '',
            ],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                [
                    'name' => $category['name'],
                    'slug' => Str::slug($category['name']),
                    'image' => $category['image'],
                    'description' => $category['description'],
                ]
            );
        }
    }
}
