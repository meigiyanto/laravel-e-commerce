<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\SubCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AdditionalCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = [

            /*
            |--------------------------------------------------------------------------
            | 1. KECANTIKAN
            |--------------------------------------------------------------------------
            */
            [
                'category' => [
                    'name' => 'Kecantikan',
                    'image' => 'https://images.unsplash.com/photo-1596462502278-27bfdc403348?auto=format&fit=crop&w=800&q=80',
                    'description' => 'Produk perawatan kulit, rambut, makeup, dan kebutuhan kecantikan sehari-hari.',
                ],

                'sub_categories' => [
                    [
                        'name' => 'Skincare',
                        'description' => 'Produk perawatan kulit untuk kebutuhan sehari-hari.',
                    ],
                    [
                        'name' => 'Makeup',
                        'description' => 'Berbagai produk makeup untuk tampilan sehari-hari dan acara khusus.',
                    ],
                    [
                        'name' => 'Perawatan Rambut',
                        'description' => 'Produk untuk menjaga kesehatan dan penampilan rambut.',
                    ],
                ],

                'products' => [
                    [
                        'sub_category' => 'Skincare',
                        'name' => 'Hydrating Facial Serum',
                        'description' => 'Serum wajah dengan formula ringan untuk membantu menjaga kelembapan dan membuat kulit terasa lebih halus.',
                        'price' => 129000,
                        'stock' => 45,
                        'image' => 'https://images.unsplash.com/photo-1620916566398-39f1143ab7be?auto=format&fit=crop&w=800&q=80',
                    ],
                    [
                        'sub_category' => 'Skincare',
                        'name' => 'Daily Facial Cleanser',
                        'description' => 'Pembersih wajah lembut untuk membantu mengangkat kotoran dan minyak tanpa membuat kulit terasa kering.',
                        'price' => 89000,
                        'stock' => 60,
                        'image' => 'https://images.unsplash.com/photo-1556228578-8c89e6adf883?auto=format&fit=crop&w=800&q=80',
                    ],
                    [
                        'sub_category' => 'Makeup',
                        'name' => 'Velvet Matte Lip Cream',
                        'description' => 'Lip cream dengan tekstur lembut dan hasil akhir matte yang nyaman digunakan sepanjang hari.',
                        'price' => 99000,
                        'stock' => 50,
                        'image' => 'https://images.unsplash.com/photo-1586495777744-4413f21062fa?auto=format&fit=crop&w=800&q=80',
                    ],
                    [
                        'sub_category' => 'Makeup',
                        'name' => 'Natural Tone Face Palette',
                        'description' => 'Palet makeup dengan pilihan warna natural untuk tampilan sehari-hari.',
                        'price' => 179000,
                        'stock' => 30,
                        'image' => 'https://images.unsplash.com/photo-1512496015851-a90fb38ba796?auto=format&fit=crop&w=800&q=80',
                    ],
                    [
                        'sub_category' => 'Perawatan Rambut',
                        'name' => 'Repair Hair Care Set',
                        'description' => 'Paket perawatan rambut yang terdiri dari shampoo dan conditioner untuk membantu merawat rambut kering.',
                        'price' => 159000,
                        'stock' => 35,
                        'image' => 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?auto=format&fit=crop&w=800&q=80',
                    ],
                ],
            ],

            /*
            |--------------------------------------------------------------------------
            | 2. KESEHATAN & KEBUGARAN
            |--------------------------------------------------------------------------
            */
            [
                'category' => [
                    'name' => 'Kesehatan & Kebugaran',
                    'image' => 'https://images.unsplash.com/photo-1505751172876-fa1923c5c528?auto=format&fit=crop&w=800&q=80',
                    'description' => 'Perlengkapan pendukung gaya hidup sehat, kebugaran, dan aktivitas fisik.',
                ],

                'sub_categories' => [
                    [
                        'name' => 'Alat Kebugaran',
                        'description' => 'Peralatan untuk mendukung latihan kebugaran di rumah.',
                    ],
                    [
                        'name' => 'Yoga & Relaksasi',
                        'description' => 'Perlengkapan yoga, stretching, dan relaksasi.',
                    ],
                    [
                        'name' => 'Perawatan Pribadi',
                        'description' => 'Produk pendukung perawatan dan kebutuhan pribadi sehari-hari.',
                    ],
                ],

                'products' => [
                    [
                        'sub_category' => 'Alat Kebugaran',
                        'name' => 'Resistance Band Set',
                        'description' => 'Set resistance band dengan beberapa tingkat resistensi untuk latihan kekuatan dan mobilitas.',
                        'price' => 149000,
                        'stock' => 40,
                        'image' => 'https://images.unsplash.com/photo-1598289431512-b97b0917affc?auto=format&fit=crop&w=800&q=80',
                    ],
                    [
                        'sub_category' => 'Alat Kebugaran',
                        'name' => 'Digital Body Scale',
                        'description' => 'Timbangan digital dengan layar mudah dibaca untuk membantu memantau berat badan.',
                        'price' => 219000,
                        'stock' => 25,
                        'image' => 'https://images.unsplash.com/photo-1605296867304-46d5465a13f1?auto=format&fit=crop&w=800&q=80',
                    ],
                    [
                        'sub_category' => 'Yoga & Relaksasi',
                        'name' => 'Premium Yoga Block Set',
                        'description' => 'Set yoga block ringan untuk membantu meningkatkan stabilitas dan fleksibilitas saat latihan.',
                        'price' => 119000,
                        'stock' => 35,
                        'image' => 'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?auto=format&fit=crop&w=800&q=80',
                    ],
                    [
                        'sub_category' => 'Yoga & Relaksasi',
                        'name' => 'Massage Foam Roller',
                        'description' => 'Foam roller untuk membantu relaksasi otot setelah aktivitas olahraga.',
                        'price' => 179000,
                        'stock' => 28,
                        'image' => 'https://images.unsplash.com/photo-1518611012118-696072aa579a?auto=format&fit=crop&w=800&q=80',
                    ],
                    [
                        'sub_category' => 'Perawatan Pribadi',
                        'name' => 'Compact Hair Dryer',
                        'description' => 'Hair dryer berukuran compact dengan beberapa pilihan pengaturan untuk penggunaan sehari-hari.',
                        'price' => 249000,
                        'stock' => 22,
                        'image' => 'https://images.unsplash.com/photo-1522338140262-f46f5913618a?auto=format&fit=crop&w=800&q=80',
                    ],
                ],
            ],

            /*
            |--------------------------------------------------------------------------
            | 3. IBU & ANAK
            |--------------------------------------------------------------------------
            */
            [
                'category' => [
                    'name' => 'Ibu & Anak',
                    'image' => 'https://images.unsplash.com/photo-1515488042361-ee00e0ddd4e4?auto=format&fit=crop&w=800&q=80',
                    'description' => 'Berbagai kebutuhan bayi, anak, dan perlengkapan pendukung keluarga.',
                ],

                'sub_categories' => [
                    [
                        'name' => 'Perlengkapan Bayi',
                        'description' => 'Perlengkapan untuk kebutuhan bayi sehari-hari.',
                    ],
                    [
                        'name' => 'Mainan Anak',
                        'description' => 'Mainan edukatif dan hiburan untuk anak.',
                    ],
                    [
                        'name' => 'Pakaian Anak',
                        'description' => 'Pakaian nyaman untuk bayi dan anak-anak.',
                    ],
                ],

                'products' => [
                    [
                        'sub_category' => 'Perlengkapan Bayi',
                        'name' => 'Baby Stroller Comfort',
                        'description' => 'Kereta bayi dengan desain praktis dan dapat dilipat untuk memudahkan perjalanan.',
                        'price' => 899000,
                        'stock' => 12,
                        'image' => 'https://images.unsplash.com/photo-1519689680058-324335c77eba?auto=format&fit=crop&w=800&q=80',
                    ],
                    [
                        'sub_category' => 'Perlengkapan Bayi',
                        'name' => 'Baby Feeding Set',
                        'description' => 'Set perlengkapan makan bayi yang terdiri dari piring, mangkuk, sendok, dan gelas.',
                        'price' => 129000,
                        'stock' => 35,
                        'image' => 'https://images.unsplash.com/photo-1604917877934-07d8d248d396?auto=format&fit=crop&w=800&q=80',
                    ],
                    [
                        'sub_category' => 'Mainan Anak',
                        'name' => 'Wooden Learning Blocks',
                        'description' => 'Balok kayu edukatif untuk membantu mengenalkan bentuk, warna, dan koordinasi motorik anak.',
                        'price' => 159000,
                        'stock' => 30,
                        'image' => 'https://images.unsplash.com/photo-1596464716127-f2a82984de30?auto=format&fit=crop&w=800&q=80',
                    ],
                    [
                        'sub_category' => 'Mainan Anak',
                        'name' => 'Kids Building Blocks 80pcs',
                        'description' => 'Set balok konstruksi berisi 80 bagian untuk permainan kreatif anak.',
                        'price' => 189000,
                        'stock' => 25,
                        'image' => 'https://images.unsplash.com/photo-1594784055415-6e7c9e7c7d91?auto=format&fit=crop&w=800&q=80',
                    ],
                    [
                        'sub_category' => 'Pakaian Anak',
                        'name' => 'Kids Cotton Casual Set',
                        'description' => 'Set pakaian anak berbahan katun yang ringan dan nyaman untuk aktivitas sehari-hari.',
                        'price' => 149000,
                        'stock' => 40,
                        'image' => 'https://images.unsplash.com/photo-1519457431-44ccd64a579b?auto=format&fit=crop&w=800&q=80',
                    ],
                ],
            ],

            /*
            |--------------------------------------------------------------------------
            | 4. OTOMOTIF
            |--------------------------------------------------------------------------
            */
            [
                'category' => [
                    'name' => 'Otomotif',
                    'image' => 'https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?auto=format&fit=crop&w=800&q=80',
                    'description' => 'Aksesori dan perlengkapan pendukung untuk kebutuhan kendaraan.',
                ],

                'sub_categories' => [
                    [
                        'name' => 'Aksesoris Mobil',
                        'description' => 'Aksesori untuk meningkatkan kenyamanan dan fungsi kendaraan.',
                    ],
                    [
                        'name' => 'Aksesoris Motor',
                        'description' => 'Berbagai aksesori pendukung kebutuhan sepeda motor.',
                    ],
                    [
                        'name' => 'Perawatan Kendaraan',
                        'description' => 'Produk untuk menjaga kebersihan dan kondisi kendaraan.',
                    ],
                ],

                'products' => [
                    [
                        'sub_category' => 'Aksesoris Mobil',
                        'name' => 'Premium Car Phone Holder',
                        'description' => 'Holder smartphone untuk mobil dengan sistem pemasangan yang praktis dan kokoh.',
                        'price' => 129000,
                        'stock' => 50,
                        'image' => 'https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&fit=crop&w=800&q=80',
                    ],
                    [
                        'sub_category' => 'Aksesoris Mobil',
                        'name' => 'Car Seat Organizer',
                        'description' => 'Organizer praktis untuk menyimpan berbagai barang kecil di dalam kendaraan.',
                        'price' => 169000,
                        'stock' => 30,
                        'image' => 'https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?auto=format&fit=crop&w=800&q=80',
                    ],
                    [
                        'sub_category' => 'Aksesoris Motor',
                        'name' => 'Motorcycle Phone Mount',
                        'description' => 'Dudukan smartphone untuk sepeda motor dengan konstruksi yang stabil.',
                        'price' => 139000,
                        'stock' => 35,
                        'image' => 'https://images.unsplash.com/photo-1558981806-ec527fa84c39?auto=format&fit=crop&w=800&q=80',
                    ],
                    [
                        'sub_category' => 'Perawatan Kendaraan',
                        'name' => 'Car Cleaning Kit',
                        'description' => 'Paket perlengkapan pembersih kendaraan untuk perawatan interior dan eksterior.',
                        'price' => 199000,
                        'stock' => 24,
                        'image' => 'https://images.unsplash.com/photo-1607860108855-64acf2078ed9?auto=format&fit=crop&w=800&q=80',
                    ],
                    [
                        'sub_category' => 'Perawatan Kendaraan',
                        'name' => 'Microfiber Detailing Set',
                        'description' => 'Set kain microfiber untuk membersihkan dan merawat permukaan kendaraan.',
                        'price' => 89000,
                        'stock' => 60,
                        'image' => 'https://images.unsplash.com/photo-1609521263047-f8f205293f24?auto=format&fit=crop&w=800&q=80',
                    ],
                ],
            ],

            /*
            |--------------------------------------------------------------------------
            | 5. BUKU & ALAT TULIS
            |--------------------------------------------------------------------------
            */
            [
                'category' => [
                    'name' => 'Buku & Alat Tulis',
                    'image' => 'https://images.unsplash.com/photo-1495446815901-a7297e633e8d?auto=format&fit=crop&w=800&q=80',
                    'description' => 'Koleksi buku, stationery, dan perlengkapan untuk belajar serta bekerja.',
                ],

                'sub_categories' => [
                    [
                        'name' => 'Buku',
                        'description' => 'Berbagai buku untuk pembelajaran, pengembangan diri, dan hiburan.',
                    ],
                    [
                        'name' => 'Stationery',
                        'description' => 'Perlengkapan tulis untuk sekolah, kuliah, dan pekerjaan.',
                    ],
                    [
                        'name' => 'Notebook',
                        'description' => 'Notebook dan jurnal untuk mencatat berbagai kebutuhan.',
                    ],
                ],

                'products' => [
                    [
                        'sub_category' => 'Buku',
                        'name' => 'Modern Web Development Guide',
                        'description' => 'Buku panduan pengembangan web modern untuk pemula hingga tingkat menengah.',
                        'price' => 189000,
                        'stock' => 20,
                        'image' => 'https://images.unsplash.com/photo-1532012197267-da84d127e765?auto=format&fit=crop&w=800&q=80',
                    ],
                    [
                        'sub_category' => 'Buku',
                        'name' => 'Business Strategy Handbook',
                        'description' => 'Buku praktis mengenai strategi bisnis, perencanaan, dan pengembangan usaha.',
                        'price' => 219000,
                        'stock' => 18,
                        'image' => 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?auto=format&fit=crop&w=800&q=80',
                    ],
                    [
                        'sub_category' => 'Stationery',
                        'name' => 'Professional Stationery Set',
                        'description' => 'Set alat tulis profesional untuk kebutuhan kerja dan belajar sehari-hari.',
                        'price' => 99000,
                        'stock' => 45,
                        'image' => 'https://images.unsplash.com/photo-1455390582262-044cdead277a?auto=format&fit=crop&w=800&q=80',
                    ],
                    [
                        'sub_category' => 'Notebook',
                        'name' => 'Premium Hardcover Notebook',
                        'description' => 'Notebook hardcover dengan kertas berkualitas untuk mencatat ide dan pekerjaan.',
                        'price' => 79000,
                        'stock' => 70,
                        'image' => 'https://images.unsplash.com/photo-1517842645767-c639042777db?auto=format&fit=crop&w=800&q=80',
                    ],
                    [
                        'sub_category' => 'Notebook',
                        'name' => 'Daily Planner A5',
                        'description' => 'Planner ukuran A5 untuk membantu mengatur jadwal dan aktivitas harian.',
                        'price' => 69000,
                        'stock' => 55,
                        'image' => 'https://images.unsplash.com/photo-1499951360447-b19be8fe80f5?auto=format&fit=crop&w=800&q=80',
                    ],
                ],
            ],

            /*
            |--------------------------------------------------------------------------
            | 6. HOBI & KOLEKSI
            |--------------------------------------------------------------------------
            */
            [
                'category' => [
                    'name' => 'Hobi & Koleksi',
                    'image' => 'https://images.unsplash.com/photo-1594736797933-d0501ba2fe65?auto=format&fit=crop&w=800&q=80',
                    'description' => 'Produk untuk berbagai hobi, aktivitas kreatif, dan koleksi.',
                ],

                'sub_categories' => [
                    [
                        'name' => 'Alat Musik',
                        'description' => 'Peralatan dan aksesori untuk aktivitas bermusik.',
                    ],
                    [
                        'name' => 'Kerajinan',
                        'description' => 'Perlengkapan untuk aktivitas kerajinan dan kreativitas.',
                    ],
                    [
                        'name' => 'Permainan',
                        'description' => 'Permainan dan aktivitas hiburan untuk keluarga.',
                    ],
                ],

                'products' => [
                    [
                        'sub_category' => 'Alat Musik',
                        'name' => 'Acoustic Guitar Classic',
                        'description' => 'Gitar akustik dengan desain klasik untuk latihan dan pertunjukan ringan.',
                        'price' => 1299000,
                        'stock' => 10,
                        'image' => 'https://images.unsplash.com/photo-1510915361894-db8b60106cb1?auto=format&fit=crop&w=800&q=80',
                    ],
                    [
                        'sub_category' => 'Alat Musik',
                        'name' => 'Digital Piano 61 Keys',
                        'description' => 'Keyboard piano digital dengan 61 tuts untuk latihan musik di rumah.',
                        'price' => 2499000,
                        'stock' => 8,
                        'image' => 'https://images.unsplash.com/photo-1520523839897-bd0b52f945a0?auto=format&fit=crop&w=800&q=80',
                    ],
                    [
                        'sub_category' => 'Kerajinan',
                        'name' => 'Creative Drawing Kit',
                        'description' => 'Set alat gambar lengkap untuk kegiatan menggambar dan membuat ilustrasi.',
                        'price' => 149000,
                        'stock' => 30,
                        'image' => 'https://images.unsplash.com/photo-1513364776144-60967b0f800f?auto=format&fit=crop&w=800&q=80',
                    ],
                    [
                        'sub_category' => 'Kerajinan',
                        'name' => 'DIY Craft Starter Kit',
                        'description' => 'Paket perlengkapan kerajinan untuk berbagai proyek kreatif di rumah.',
                        'price' => 179000,
                        'stock' => 25,
                        'image' => 'https://images.unsplash.com/photo-1452860606245-08befc0ff44b?auto=format&fit=crop&w=800&q=80',
                    ],
                    [
                        'sub_category' => 'Permainan',
                        'name' => 'Family Board Game',
                        'description' => 'Permainan papan untuk aktivitas hiburan bersama keluarga dan teman.',
                        'price' => 199000,
                        'stock' => 20,
                        'image' => 'https://images.unsplash.com/photo-1610890716171-6b1bb98ffd09?auto=format&fit=crop&w=800&q=80',
                    ],
                ],
            ],

            /*
            |--------------------------------------------------------------------------
            | 7. HEWAN PELIHARAAN
            |--------------------------------------------------------------------------
            */
            [
                'category' => [
                    'name' => 'Hewan Peliharaan',
                    'image' => 'https://images.unsplash.com/photo-1450778869180-41d0601e046e?auto=format&fit=crop&w=800&q=80',
                    'description' => 'Kebutuhan makanan, perawatan, dan perlengkapan untuk hewan peliharaan.',
                ],

                'sub_categories' => [
                    [
                        'name' => 'Perlengkapan Kucing',
                        'description' => 'Berbagai kebutuhan untuk kucing peliharaan.',
                    ],
                    [
                        'name' => 'Perlengkapan Anjing',
                        'description' => 'Perlengkapan untuk kebutuhan anjing peliharaan.',
                    ],
                    [
                        'name' => 'Aksesori Hewan',
                        'description' => 'Aksesori dan perlengkapan tambahan untuk hewan peliharaan.',
                    ],
                ],

                'products' => [
                    [
                        'sub_category' => 'Perlengkapan Kucing',
                        'name' => 'Automatic Cat Feeder',
                        'description' => 'Tempat makan otomatis dengan jadwal pemberian makanan yang dapat diatur.',
                        'price' => 399000,
                        'stock' => 15,
                        'image' => 'https://images.unsplash.com/photo-1574158622682-e40e69881006?auto=format&fit=crop&w=800&q=80',
                    ],
                    [
                        'sub_category' => 'Perlengkapan Kucing',
                        'name' => 'Comfort Cat Bed',
                        'description' => 'Tempat tidur empuk dengan desain nyaman untuk kucing di rumah.',
                        'price' => 179000,
                        'stock' => 25,
                        'image' => 'https://images.unsplash.com/photo-1519052537078-e6302a4968d4?auto=format&fit=crop&w=800&q=80',
                    ],
                    [
                        'sub_category' => 'Perlengkapan Anjing',
                        'name' => 'Adjustable Dog Harness',
                        'description' => 'Harness anjing yang dapat disesuaikan ukurannya untuk aktivitas berjalan.',
                        'price' => 149000,
                        'stock' => 30,
                        'image' => 'https://images.unsplash.com/photo-1558788353-f76d92427f16?auto=format&fit=crop&w=800&q=80',
                    ],
                    [
                        'sub_category' => 'Perlengkapan Anjing',
                        'name' => 'Foldable Dog Bowl',
                        'description' => 'Mangkuk anjing lipat yang praktis untuk digunakan di rumah maupun saat bepergian.',
                        'price' => 69000,
                        'stock' => 50,
                        'image' => 'https://images.unsplash.com/photo-1583337130417-3346a1be7dee?auto=format&fit=crop&w=800&q=80',
                    ],
                    [
                        'sub_category' => 'Aksesori Hewan',
                        'name' => 'Pet Grooming Kit',
                        'description' => 'Set perlengkapan grooming untuk membantu menjaga kebersihan dan kerapian hewan peliharaan.',
                        'price' => 129000,
                        'stock' => 28,
                        'image' => 'https://images.unsplash.com/photo-1601758228041-f3b2795255f1?auto=format&fit=crop&w=800&q=80',
                    ],
                ],
            ],

            /*
            |--------------------------------------------------------------------------
            | 8. MAKANAN & MINUMAN
            |--------------------------------------------------------------------------
            */
            [
                'category' => [
                    'name' => 'Makanan & Minuman',
                    'image' => 'https://images.unsplash.com/photo-1498837167922-ddd27525d352?auto=format&fit=crop&w=800&q=80',
                    'description' => 'Pilihan makanan, minuman, dan kebutuhan konsumsi untuk sehari-hari.',
                ],

                'sub_categories' => [
                    [
                        'name' => 'Snack',
                        'description' => 'Berbagai makanan ringan untuk menemani aktivitas sehari-hari.',
                    ],
                    [
                        'name' => 'Kopi & Teh',
                        'description' => 'Pilihan kopi dan teh untuk dinikmati di rumah maupun kantor.',
                    ],
                    [
                        'name' => 'Bahan Masakan',
                        'description' => 'Bahan dan kebutuhan masak untuk dapur sehari-hari.',
                    ],
                ],

                'products' => [
                    [
                        'sub_category' => 'Snack',
                        'name' => 'Premium Granola Mix',
                        'description' => 'Campuran granola dengan berbagai bahan pilihan untuk sarapan atau camilan.',
                        'price' => 89000,
                        'stock' => 45,
                        'image' => 'https://images.unsplash.com/photo-1517093157656-b9eccef91cb1?auto=format&fit=crop&w=800&q=80',
                    ],
                    [
                        'sub_category' => 'Snack',
                        'name' => 'Roasted Almond Snack',
                        'description' => 'Kacang almond panggang sebagai pilihan camilan praktis.',
                        'price' => 79000,
                        'stock' => 50,
                        'image' => 'https://images.unsplash.com/photo-1508061253366-f7da158b6d46?auto=format&fit=crop&w=800&q=80',
                    ],
                    [
                        'sub_category' => 'Kopi & Teh',
                        'name' => 'Arabica Coffee Beans 250g',
                        'description' => 'Biji kopi Arabica dengan aroma dan cita rasa khas untuk kebutuhan seduh di rumah.',
                        'price' => 109000,
                        'stock' => 40,
                        'image' => 'https://images.unsplash.com/photo-1447933601403-0c6688de566e?auto=format&fit=crop&w=800&q=80',
                    ],
                    [
                        'sub_category' => 'Kopi & Teh',
                        'name' => 'Premium Green Tea',
                        'description' => 'Teh hijau pilihan dengan aroma ringan untuk dinikmati kapan saja.',
                        'price' => 69000,
                        'stock' => 55,
                        'image' => 'https://images.unsplash.com/photo-1556881286-fc6915169721?auto=format&fit=crop&w=800&q=80',
                    ],
                    [
                        'sub_category' => 'Bahan Masakan',
                        'name' => 'Organic Cooking Essentials',
                        'description' => 'Paket bahan masakan pilihan untuk mendukung kebutuhan memasak sehari-hari.',
                        'price' => 149000,
                        'stock' => 25,
                        'image' => 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=800&q=80',
                    ],
                ],
            ],

            /*
            |--------------------------------------------------------------------------
            | 9. PERLENGKAPAN KANTOR
            |--------------------------------------------------------------------------
            */
            [
                'category' => [
                    'name' => 'Perlengkapan Kantor',
                    'image' => 'https://images.unsplash.com/photo-1497366811353-6870744d04b2?auto=format&fit=crop&w=800&q=80',
                    'description' => 'Perlengkapan untuk mendukung produktivitas kerja di kantor maupun home office.',
                ],

                'sub_categories' => [
                    [
                        'name' => 'Meja Kantor',
                        'description' => 'Meja untuk kebutuhan kerja dan home office.',
                    ],
                    [
                        'name' => 'Aksesori Meja',
                        'description' => 'Aksesori untuk menjaga meja kerja tetap rapi dan nyaman.',
                    ],
                    [
                        'name' => 'Peralatan Kantor',
                        'description' => 'Peralatan pendukung aktivitas kantor sehari-hari.',
                    ],
                ],

                'products' => [
                    [
                        'sub_category' => 'Meja Kantor',
                        'name' => 'Minimalist Work Desk',
                        'description' => 'Meja kerja minimalis dengan permukaan luas untuk komputer dan perlengkapan kerja.',
                        'price' => 799000,
                        'stock' => 12,
                        'image' => 'https://images.unsplash.com/photo-1497366754035-f200968a6e72?auto=format&fit=crop&w=800&q=80',
                    ],
                    [
                        'sub_category' => 'Meja Kantor',
                        'name' => 'Compact Office Desk',
                        'description' => 'Meja kerja compact untuk ruang kantor atau home office berukuran terbatas.',
                        'price' => 599000,
                        'stock' => 15,
                        'image' => 'https://images.unsplash.com/photo-1497215728101-856f4ea42174?auto=format&fit=crop&w=800&q=80',
                    ],
                    [
                        'sub_category' => 'Aksesori Meja',
                        'name' => 'Desk Organizer Premium',
                        'description' => 'Organizer meja dengan beberapa kompartemen untuk menyimpan alat tulis dan aksesori.',
                        'price' => 119000,
                        'stock' => 35,
                        'image' => 'https://images.unsplash.com/photo-1499951360447-b19be8fe80f5?auto=format&fit=crop&w=800&q=80',
                    ],
                    [
                        'sub_category' => 'Aksesori Meja',
                        'name' => 'Adjustable Monitor Stand',
                        'description' => 'Stand monitor yang dapat disesuaikan untuk membantu menciptakan posisi kerja yang lebih nyaman.',
                        'price' => 229000,
                        'stock' => 22,
                        'image' => 'https://images.unsplash.com/photo-1527443224154-c4a3942d3acf?auto=format&fit=crop&w=800&q=80',
                    ],
                    [
                        'sub_category' => 'Peralatan Kantor',
                        'name' => 'Wireless Presentation Remote',
                        'description' => 'Remote presentasi wireless dengan desain compact untuk meeting dan presentasi.',
                        'price' => 159000,
                        'stock' => 30,
                        'image' => 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?auto=format&fit=crop&w=800&q=80',
                    ],
                ],
            ],

            /*
            |--------------------------------------------------------------------------
            | 10. KAMERA & FOTOGRAFI
            |--------------------------------------------------------------------------
            */
            [
                'category' => [
                    'name' => 'Kamera & Fotografi',
                    'image' => 'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?auto=format&fit=crop&w=800&q=80',
                    'description' => 'Kamera, aksesori, dan perlengkapan pendukung untuk kebutuhan fotografi dan videografi.',
                ],

                'sub_categories' => [
                    [
                        'name' => 'Kamera',
                        'description' => 'Kamera untuk kebutuhan fotografi dan pembuatan konten.',
                    ],
                    [
                        'name' => 'Tripod',
                        'description' => 'Tripod dan perlengkapan penyangga kamera.',
                    ],
                    [
                        'name' => 'Aksesori Kamera',
                        'description' => 'Berbagai aksesori pendukung kamera dan aktivitas fotografi.',
                    ],
                ],

                'products' => [
                    [
                        'sub_category' => 'Kamera',
                        'name' => 'Mirrorless Camera M50',
                        'description' => 'Kamera mirrorless compact untuk fotografi, perjalanan, dan pembuatan konten.',
                        'price' => 8499000,
                        'stock' => 7,
                        'image' => 'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?auto=format&fit=crop&w=800&q=80',
                    ],
                    [
                        'sub_category' => 'Kamera',
                        'name' => 'Compact Digital Camera Z5',
                        'description' => 'Kamera digital compact untuk pengguna yang menginginkan perangkat praktis untuk bepergian.',
                        'price' => 3299000,
                        'stock' => 10,
                        'image' => 'https://images.unsplash.com/photo-1606980707986-9f9c4c3b1a89?auto=format&fit=crop&w=800&q=80',
                    ],
                    [
                        'sub_category' => 'Tripod',
                        'name' => 'Aluminium Camera Tripod',
                        'description' => 'Tripod aluminium ringan dengan tinggi yang dapat disesuaikan untuk berbagai kebutuhan pemotretan.',
                        'price' => 349000,
                        'stock' => 18,
                        'image' => 'https://images.unsplash.com/photo-1502920917128-1aa500764cbd?auto=format&fit=crop&w=800&q=80',
                    ],
                    [
                        'sub_category' => 'Aksesori Kamera',
                        'name' => 'Camera Shoulder Bag',
                        'description' => 'Tas kamera dengan ruang penyimpanan untuk kamera dan beberapa aksesori fotografi.',
                        'price' => 299000,
                        'stock' => 20,
                        'image' => 'https://images.unsplash.com/photo-1556911220-bff31c812dba?auto=format&fit=crop&w=800&q=80',
                    ],
                    [
                        'sub_category' => 'Aksesori Kamera',
                        'name' => 'Universal Camera Cleaning Kit',
                        'description' => 'Paket perlengkapan pembersih kamera dan lensa untuk menjaga perangkat tetap bersih.',
                        'price' => 99000,
                        'stock' => 40,
                        'image' => 'https://images.unsplash.com/photo-1606986628253-3d1e1e5c7e65?auto=format&fit=crop&w=800&q=80',
                    ],
                ],
            ],
        ];

        foreach ($catalog as $categoryData) {
            $category = Category::updateOrCreate(
                [
                    'slug' => Str::slug($categoryData['category']['name']),
                ],
                [
                    'name' => $categoryData['category']['name'],
                    'image' => $categoryData['category']['image'],
                    'description' => $categoryData['category']['description'],
                ]
            );

            $subCategoryMap = [];

            foreach ($categoryData['sub_categories'] as $subCategoryData) {
                $subCategory = SubCategory::updateOrCreate(
                    [
                        'category_id' => $category->id,
                        'slug' => Str::slug($subCategoryData['name']),
                    ],
                    [
                        'name' => $subCategoryData['name'],
                        'image' => $categoryData['category']['image'],
                        'description' => $subCategoryData['description'],
                    ]
                );

                $subCategoryMap[$subCategoryData['name']] = $subCategory;
            }

            foreach ($categoryData['products'] as $productData) {
                $subCategory = $subCategoryMap[$productData['sub_category']];

                Product::updateOrCreate(
                    [
                        'slug' => Str::slug($productData['name']),
                    ],
                    [
                        'category_id' => $category->id,
                        'sub_category_id' => $subCategory->id,
                        'name' => $productData['name'],
                        'description' => $productData['description'],
                        'price' => $productData['price'],
                        'stock' => $productData['stock'],
                        'image' => $productData['image'],
                    ]
                );
            }
        }
    }
}
