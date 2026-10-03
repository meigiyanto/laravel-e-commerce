<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SubCategorySeeder extends Seeder
{
    public function run(): void
    {
        $subCategories = [
            [
                'category' => 'Elektronik',
                'name' => 'Smartphone',
                'image' => 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?auto=format&fit=crop&w=800&q=80',
                'description' => 'Berbagai jenis smartphone dan perangkat mobile.',
            ],
            [
                'category' => 'Elektronik',
                'name' => 'Laptop',
                'image' => 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?auto=format&fit=crop&w=800&q=80',
                'description' => '',
            ],
            [
                'category' => 'Elektronik',
                'name' => 'Audio',
                'image' => '',
                'description' => 'Headphone, headset, speaker dan perangkat audio.',
            ],
            [
                'category' => 'Elektronik',
                'name' => 'Aksesoris HP',
                'image' => 'https://images.unsplash.com/photo-1601524909162-ae8725290836?auto=format&fit=crop&w=800&q=80',
                'description' => 'Aksesoris smartphone seperti kabel dan charger.',
            ],
            [
                'category' => 'Fashion',
                'name' => 'Sepatu',
                'image' => '',
                'description' => 'Berbagai jenis sepatu pria dan wanita.',
            ],
            [
                'category' => 'Fashion',
                'name' => 'Tas',
                'image' => '',
                'description' => 'Tas selempang, backpack dan tas sehari-hari.',
            ],
            [
                'category' => 'Fashion',
                'name' => 'Jam Tangan',
                'image' => '',
                'description' => 'Jam tangan casual dan formal.',
            ],
            [
                'category' => 'Fashion',
                'name' => 'Pakaian Pria',
                'image' => 'https://images.unsplash.com/photo-1617137968427-85924c800a22?auto=format&fit=crop&w=800&q=80',
                'description' => '',
            ],
            [
                'category' => 'Fashion',
                'name' => 'Pakaian Wanita',
                'image' => 'https://images.unsplash.com/photo-1617137968427-85924c800a22?auto=format&fit=crop&w=800&q=80',
                'description' => '',
            ],
            [
                'category' => 'Rumah Tangga',
                'name' => 'Dapur',
                'image' => '',
                'description' => 'Peralatan dan perlengkapan dapur.',
            ],
            [
                'category' => 'Rumah Tangga',
                'name' => 'Peralatan Rumah',
                'image' => '',
                'description' => 'Berbagai perlengkapan untuk kebutuhan rumah.',
            ],
            [
                'category' => 'Komputer',
                'name' => 'Keyboard',
                'image' => '',
                'description' => 'Keyboard mekanikal dan keyboard komputer.',
            ],
            [
                'category' => 'Komputer',
                'name' => 'Mouse',
                'image' => '',
                'description' => 'Mouse wireless dan gaming.',
            ],
            [
                'category' => 'Olahraga',
                'name' => 'Fitness',
                'image' => 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?auto=format&fit=crop&w=800&q=80',
                'description' => '',
            ],
            [
                'category' => 'Olahraga',
                'name' => 'Running',
                'image' => 'https://images.unsplash.com/photo-1552674605-db6ffd4facb5?auto=format&fit=crop&w=800&q=80',
                'description' => '',
            ],
            [
                'category' => 'Olahraga',
                'name' => 'Outdoor',
                'image' => 'https://images.unsplash.com/photo-1551632811-561732d1e306?auto=format&fit=crop&w=800&q=80',
                'description' => '',
            ],
        ];

        foreach ($subCategories as $subCategory) {

            $category = Category::where('slug', Str::slug($subCategory['category']))->firstOrFail();

            SubCategory::updateOrCreate(
                [
                    'category_id' => $category->id,
                    'name' => $subCategory['name'],
                    'slug' => Str::slug($subCategory['name']),
                    'image' => $subCategory['image'],
                    'description' => $subCategory['description'],
                ]
            );
        }
    }
}
