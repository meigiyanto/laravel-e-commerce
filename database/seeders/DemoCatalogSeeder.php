<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductSpecification;
use App\Models\Review;
use App\Models\SubCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating demo catalog...');

        /*
        |--------------------------------------------------------------------------
        | Demo customers
        |--------------------------------------------------------------------------
        */

        $customers = collect([
            [
                'name' => 'Andi Pratama',
                'email' => 'demo.andi@example.com',
            ],
            [
                'name' => 'Budi Santoso',
                'email' => 'demo.budi@example.com',
            ],
            [
                'name' => 'Citra Lestari',
                'email' => 'demo.citra@example.com',
            ],
            [
                'name' => 'Dewi Anggraini',
                'email' => 'demo.dewi@example.com',
            ],
            [
                'name' => 'Eko Saputra',
                'email' => 'demo.eko@example.com',
            ],
        ])->map(function (array $customer) {
            return User::updateOrCreate(
                ['email' => $customer['email']],
                [
                    'name' => $customer['name'],
                    'password' => Hash::make('password'),
                    'role' => 'customer',
                ]
            );
        });

        /*
        |--------------------------------------------------------------------------
        | Products
        |--------------------------------------------------------------------------
        */

        $products = [
            [
                'category' => 'Elektronik',
                'sub_category' => 'Smartphone',
                'name' => 'Smartphone X1',
                'description' => 'Smartphone modern dengan layar AMOLED 120Hz, kamera 50MP, performa tinggi, dan baterai 5000mAh.',
                'price' => 4999000,
                'stock' => 25,
                'image' => 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?auto=format&fit=crop&w=800&q=80',
                'specifications' => [
                    'Informasi Umum' => [
                        'Brand' => 'MeiPhone',
                        'Model' => 'X1',
                        'Warna' => 'Midnight Black',
                        'Garansi' => '1 Tahun Garansi Resmi',
                    ],
                    'Layar' => [
                        'Ukuran' => '6.67 inch',
                        'Panel' => 'AMOLED',
                        'Resolusi' => '2400 × 1080',
                        'Refresh Rate' => '120Hz',
                    ],
                    'Performa' => [
                        'Processor' => 'Octa-Core 2.8GHz',
                        'RAM' => '8GB',
                        'Storage' => '256GB',
                        'Operating System' => 'Android',
                    ],
                    'Kamera' => [
                        'Kamera Utama' => '50MP',
                        'Ultra Wide' => '8MP',
                        'Kamera Depan' => '16MP',
                    ],
                    'Baterai' => [
                        'Kapasitas' => '5000mAh',
                        'Fast Charging' => '67W',
                    ],
                ],
            ],

            [
                'category' => 'Elektronik',
                'sub_category' => 'Laptop',
                'name' => 'Pro Laptop 14',
                'description' => 'Laptop profesional 14 inci dengan prosesor kelas tinggi, RAM 16GB, dan SSD NVMe 512GB.',
                'price' => 12999000,
                'stock' => 15,
                'image' => 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?auto=format&fit=crop&w=800&q=80',
                'specifications' => [
                    'Informasi Umum' => [
                        'Brand' => 'MeiBook',
                        'Model' => 'Pro Laptop 14',
                        'Warna' => 'Space Gray',
                        'Garansi' => '1 Tahun',
                    ],
                    'Display' => [
                        'Ukuran' => '14 inch',
                        'Panel' => 'IPS',
                        'Resolusi' => '2560 × 1600',
                        'Refresh Rate' => '120Hz',
                    ],
                    'Performa' => [
                        'Processor' => 'Intel Core i7',
                        'RAM' => '16GB',
                        'Storage' => '512GB NVMe SSD',
                        'Graphics' => 'Integrated Graphics',
                    ],
                    'Konektivitas' => [
                        'Wi-Fi' => 'Wi-Fi 6',
                        'Bluetooth' => 'Bluetooth 5.3',
                        'Port' => 'USB-C, USB-A',
                        'HDMI' => 'HDMI 2.1',
                    ],
                    'Baterai' => [
                        'Kapasitas' => '70Wh',
                        'Daya Tahan' => 'Hingga 12 jam',
                    ],
                ],
            ],

            [
                'category' => 'Elektronik',
                'sub_category' => 'Audio',
                'name' => 'Wireless Headset',
                'description' => 'Headset wireless over-ear dengan Active Noise Cancellation dan baterai hingga 40 jam.',
                'price' => 799000,
                'stock' => 40,
                'image' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80',
                'specifications' => [
                    'Informasi Umum' => [
                        'Brand' => 'MeiAudio',
                        'Model' => 'WH-01',
                        'Warna' => 'Black',
                        'Type' => 'Over-Ear',
                    ],
                    'Audio' => [
                        'Driver' => '40mm',
                        'Frequency Response' => '20Hz–20kHz',
                        'Noise Cancellation' => 'Active Noise Cancellation',
                        'Microphone' => 'Built-in',
                    ],
                    'Konektivitas' => [
                        'Bluetooth' => 'Bluetooth 5.3',
                        'Jangkauan' => '10 meter',
                        'Charging Port' => 'USB-C',
                    ],
                    'Baterai' => [
                        'Kapasitas' => '600mAh',
                        'Daya Tahan' => 'Hingga 40 jam',
                        'Charging' => 'USB-C Fast Charging',
                    ],
                ],
            ],

            [
                'category' => 'Fashion',
                'sub_category' => 'Pakaian Pria',
                'name' => "Classic Men's Jacket",
                'description' => 'Jaket casual pria dengan desain klasik, bahan cotton twill, dan regular fit.',
                'price' => 599000,
                'stock' => 30,
                'image' => 'https://images.unsplash.com/photo-1551028719-00167b16eac5?auto=format&fit=crop&w=800&q=80',
                'specifications' => [
                    'Informasi Umum' => [
                        'Brand' => 'MeiWear',
                        'Model' => 'Classic Jacket',
                        'Gender' => 'Pria',
                        'Type' => 'Jaket Casual',
                    ],
                    'Material' => [
                        'Material Utama' => 'Cotton Twill',
                        'Lining' => 'Polyester',
                        'Ketebalan' => 'Medium',
                    ],
                    'Ukuran' => [
                        'Size' => 'S, M, L, XL',
                        'Fit' => 'Regular Fit',
                    ],
                    'Perawatan' => [
                        'Pencucian' => 'Machine Wash',
                        'Pengeringan' => 'Low Heat',
                    ],
                ],
            ],

            [
                'category' => 'Fashion',
                'sub_category' => 'Pakaian Wanita',
                'name' => "Women's Casual Dress",
                'description' => 'Dress casual wanita berbahan cotton blend yang ringan, nyaman, dan breathable.',
                'price' => 449000,
                'stock' => 35,
                'image' => 'https://images.unsplash.com/photo-1496747611176-843222e1e57c?auto=format&fit=crop&w=800&q=80',
                'specifications' => [
                    'Informasi Umum' => [
                        'Brand' => 'MeiFashion',
                        'Model' => 'Casual Dress',
                        'Gender' => 'Wanita',
                        'Style' => 'Casual',
                    ],
                    'Material' => [
                        'Material' => 'Cotton Blend',
                        'Karakter' => 'Soft & Breathable',
                    ],
                    'Ukuran' => [
                        'Size' => 'S, M, L, XL',
                        'Fit' => 'Regular Fit',
                    ],
                    'Perawatan' => [
                        'Pencucian' => 'Hand / Machine Wash',
                        'Suhu' => 'Low Temperature',
                    ],
                ],
            ],

            [
                'category' => 'Rumah Tangga',
                'sub_category' => 'Peralatan Rumah',
                'name' => 'Modern Sofa',
                'description' => 'Sofa modern tiga dudukan dengan desain minimalis dan bantalan high density foam.',
                'price' => 4999000,
                'stock' => 10,
                'image' => 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=800&q=80',
                'specifications' => [
                    'Informasi Umum' => [
                        'Brand' => 'MeiHome',
                        'Model' => 'Modern Sofa 3 Seater',
                        'Warna' => 'Light Gray',
                        'Kapasitas' => '3 Orang',
                    ],
                    'Material' => [
                        'Frame' => 'Solid Wood',
                        'Cover' => 'Polyester Fabric',
                        'Foam' => 'High Density Foam',
                    ],
                    'Dimensi' => [
                        'Ukuran' => '210 × 85 × 80 cm',
                    ],
                    'Informasi Tambahan' => [
                        'Assembly' => 'Required',
                        'Garansi' => '1 Tahun',
                    ],
                ],
            ],

            [
                'category' => 'Rumah Tangga',
                'sub_category' => 'Dapur',
                'name' => 'Dapur Cookware Set',
                'description' => 'Set cookware 10 pcs dengan lapisan anti lengket untuk kebutuhan dapur sehari-hari.',
                'price' => 899000,
                'stock' => 20,
                'image' => 'https://images.unsplash.com/photo-1556911220-bff31c812dba?auto=format&fit=crop&w=800&q=80',
                'specifications' => [
                    'Informasi Umum' => [
                        'Brand' => 'MeiKitchen',
                        'Model' => 'Cookware Set 10 pcs',
                        'Warna' => 'Black',
                        'Jumlah' => '10 Pieces',
                    ],
                    'Material' => [
                        'Material' => 'Aluminium',
                        'Coating' => 'Non-Stick',
                        'Handle' => 'Heat Resistant',
                    ],
                    'Kompatibilitas' => [
                        'Gas' => 'Yes',
                        'Electric' => 'Yes',
                        'Induction' => 'Yes',
                    ],
                    'Perawatan' => [
                        'Dishwasher' => 'Yes',
                        'Oven Safe' => 'No',
                    ],
                ],
            ],

            [
                'category' => 'Olahraga',
                'sub_category' => 'Fitness',
                'name' => 'Adjustable Dumbbell',
                'description' => 'Dumbbell adjustable untuk latihan kekuatan dengan rentang beban hingga 24kg.',
                'price' => 749000,
                'stock' => 25,
                'image' => 'https://images.unsplash.com/photo-1583454110551-21f2fa2afe61?auto=format&fit=crop&w=800&q=80',
                'specifications' => [
                    'Informasi Umum' => [
                        'Brand' => 'MeiSport',
                        'Model' => 'Adjustable Dumbbell Pro',
                        'Warna' => 'Black',
                        'Type' => 'Adjustable',
                    ],
                    'Spesifikasi' => [
                        'Minimum Weight' => '2.5kg',
                        'Maximum Weight' => '24kg',
                        'Increment' => '2kg',
                        'Material' => 'Cast Iron',
                    ],
                    'Dimensi' => [
                        'Ukuran' => '45 × 20 × 20 cm',
                    ],
                ],
            ],

            [
                'category' => 'Olahraga',
                'sub_category' => 'Running',
                'name' => 'Running Shoes Pro',
                'description' => 'Sepatu lari ringan dengan engineered mesh, responsive foam, dan outsole anti-slip.',
                'price' => 999000,
                'stock' => 30,
                'image' => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=800&q=80',
                'specifications' => [
                    'Informasi Umum' => [
                        'Brand' => 'MeiSport',
                        'Model' => 'Running Shoes Pro',
                        'Gender' => 'Unisex',
                        'Type' => 'Running Shoes',
                    ],
                    'Material' => [
                        'Upper' => 'Engineered Mesh',
                        'Midsole' => 'EVA Foam',
                        'Outsole' => 'Rubber',
                    ],
                    'Ukuran' => [
                        'Size' => '39–44',
                        'Fit' => 'Regular Fit',
                    ],
                    'Teknologi' => [
                        'Foam' => 'Responsive Foam',
                        'Breathability' => 'High',
                        'Anti Slip' => 'Yes',
                    ],
                ],
            ],

            [
                'category' => 'Olahraga',
                'sub_category' => 'Outdoor',
                'name' => 'Hiking Backpack',
                'description' => 'Tas hiking 40L dengan aluminium frame, rain cover, chest strap, dan hip belt.',
                'price' => 649000,
                'stock' => 18,
                'image' => 'https://images.unsplash.com/photo-1551632811-561732d1e306?auto=format&fit=crop&w=800&q=80',
                'specifications' => [
                    'Informasi Umum' => [
                        'Brand' => 'MeiOutdoor',
                        'Model' => 'Hiking Backpack 40L',
                        'Warna' => 'Dark Green',
                        'Type' => 'Hiking Backpack',
                    ],
                    'Kapasitas' => [
                        'Capacity' => '40L',
                        'Main Compartment' => '1',
                        'External Pockets' => '4',
                    ],
                    'Material' => [
                        'Material' => 'Ripstop Nylon',
                        'Water Resistant' => 'Yes',
                        'Frame' => 'Aluminium',
                    ],
                    'Fitur' => [
                        'Chest Strap' => 'Yes',
                        'Hip Belt' => 'Yes',
                        'Rain Cover' => 'Included',
                    ],
                    'Dimensi' => [
                        'Ukuran' => '65 × 32 × 24 cm',
                    ],
                ],
            ],

            [
                'category' => 'Elektronik',
                'sub_category' => 'Aksesoris HP',
                'name' => 'Smartwatch Active 2',
                'description' => 'Smartwatch AMOLED dengan monitoring kesehatan, GPS, dan ketahanan air 5 ATM.',
                'price' => 1499000,
                'stock' => 22,
                'image' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&w=800&q=80',
                'specifications' => [
                    'Informasi Umum' => [
                        'Brand' => 'MeiWear',
                        'Model' => 'Active 2',
                        'Warna' => 'Black',
                        'Garansi' => '1 Tahun',
                    ],
                    'Display' => [
                        'Ukuran' => '1.43 inch',
                        'Panel' => 'AMOLED',
                        'Resolusi' => '466 × 466',
                    ],
                    'Health' => [
                        'Heart Rate' => 'Yes',
                        'SpO2' => 'Yes',
                        'Sleep Tracking' => 'Yes',
                        'Step Counter' => 'Yes',
                    ],
                    'Ketahanan' => [
                        'Water Resistance' => '5 ATM',
                    ],
                    'Baterai' => [
                        'Capacity' => '350mAh',
                        'Daya Tahan' => 'Hingga 10 hari',
                    ],
                ],
            ],

            [
                'category' => 'Komputer',
                'sub_category' => 'Keyboard',
                'name' => 'Mechanical Keyboard K87',
                'description' => 'Keyboard mechanical TKL 87-key dengan RGB, hot-swappable switch, dan koneksi wireless.',
                'price' => 899000,
                'stock' => 28,
                'image' => 'https://images.unsplash.com/photo-1587829741301-dc798b83add3?auto=format&fit=crop&w=800&q=80',
                'specifications' => [
                    'Informasi Umum' => [
                        'Brand' => 'MeiGear',
                        'Model' => 'K87',
                        'Layout' => 'TKL 87 Keys',
                        'Warna' => 'Black',
                    ],
                    'Switch' => [
                        'Type' => 'Mechanical',
                        'Switch' => 'Red',
                        'Actuation' => '2.0mm',
                        'Lifespan' => '50 Million Keystrokes',
                    ],
                    'Konektivitas' => [
                        'USB' => 'USB-C',
                        'Bluetooth' => 'Bluetooth 5.2',
                        'Wireless' => '2.4GHz',
                    ],
                    'Fitur' => [
                        'RGB' => 'Yes',
                        'N-Key Rollover' => 'Yes',
                        'Anti-Ghosting' => 'Yes',
                        'Hot-Swappable' => 'Yes',
                    ],
                ],
            ],

            [
                'category' => 'Komputer',
                'sub_category' => 'Mouse',
                'name' => 'Wireless Mouse Pro',
                'description' => 'Mouse wireless ergonomis dengan sensor presisi hingga 12.000 DPI dan baterai hingga 70 jam.',
                'price' => 499000,
                'stock' => 35,
                'image' => 'https://images.unsplash.com/photo-1527814050087-3793815479db?auto=format&fit=crop&w=800&q=80',
                'specifications' => [
                    'Informasi Umum' => [
                        'Brand' => 'MeiGear',
                        'Model' => 'Mouse Pro M1',
                        'Warna' => 'Matte Black',
                        'Ergonomic' => 'Yes',
                    ],
                    'Performa' => [
                        'Sensor' => 'Optical',
                        'DPI' => '100–12,000',
                        'Polling Rate' => '1000Hz',
                        'Buttons' => '6',
                    ],
                    'Konektivitas' => [
                        'Bluetooth' => 'Bluetooth 5.2',
                        'Wireless' => '2.4GHz',
                        'Charging' => 'USB-C',
                    ],
                    'Baterai' => [
                        'Capacity' => '500mAh',
                        'Daya Tahan' => 'Hingga 70 jam',
                    ],
                ],
            ],

            [
                'category' => 'Fashion',
                'sub_category' => 'Sepatu',
                'name' => "Men's Casual Sneakers",
                'description' => 'Sneakers casual pria dengan desain minimalis, ringan, breathable, dan nyaman untuk penggunaan harian.',
                'price' => 699000,
                'stock' => 32,
                'image' => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=800&q=80',
                'specifications' => [
                    'Informasi Umum' => [
                        'Brand' => 'MeiSport',
                        'Model' => 'Casual Sneakers M1',
                        'Gender' => 'Men',
                        'Type' => 'Casual',
                    ],
                    'Material' => [
                        'Upper' => 'Engineered Mesh',
                        'Midsole' => 'EVA',
                        'Outsole' => 'Rubber',
                    ],
                    'Ukuran' => [
                        'Size' => '39–44',
                        'Fit' => 'Regular',
                    ],
                    'Fitur' => [
                        'Lightweight' => 'Yes',
                        'Breathable' => 'Yes',
                        'Anti-Slip' => 'Yes',
                        'Cushioned Sole' => 'Yes',
                    ],
                ],
            ],

            [
                'category' => 'Fashion',
                'sub_category' => 'Tas',
                'name' => "Women's Handbag Classic",
                'description' => 'Handbag wanita bergaya klasik dengan ruang utama luas dan adjustable shoulder strap.',
                'price' => 599000,
                'stock' => 20,
                'image' => 'https://images.unsplash.com/photo-1584917865442-de89df76afd3?auto=format&fit=crop&w=800&q=80',
                'specifications' => [
                    'Informasi Umum' => [
                        'Brand' => 'MeiFashion',
                        'Model' => 'Classic Bag',
                        'Gender' => 'Women',
                        'Color' => 'Brown',
                    ],
                    'Material' => [
                        'Exterior' => 'PU Leather',
                        'Interior' => 'Polyester',
                        'Hardware' => 'Metal',
                    ],
                    'Ukuran' => [
                        'Width' => '32cm',
                        'Height' => '24cm',
                        'Depth' => '12cm',
                    ],
                    'Fitur' => [
                        'Main Compartment' => 'Yes',
                        'Inner Pocket' => 'Yes',
                        'Zipper Closure' => 'Yes',
                        'Adjustable Strap' => 'Yes',
                    ],
                ],
            ],

            [
                'category' => 'Rumah Tangga',
                'sub_category' => 'Peralatan Rumah',
                'name' => 'Ergonomic Office Chair',
                'description' => 'Kursi kantor ergonomis dengan lumbar support, adjustable armrest, dan reclining hingga 135 derajat.',
                'price' => 1899000,
                'stock' => 14,
                'image' => 'https://images.unsplash.com/photo-1580480055273-228ff5388ef8?auto=format&fit=crop&w=800&q=80',
                'specifications' => [
                    'Informasi Umum' => [
                        'Brand' => 'MeiHome',
                        'Model' => 'Ergo Chair X',
                        'Color' => 'Black',
                        'Type' => 'Office Chair',
                    ],
                    'Material' => [
                        'Frame' => 'Steel',
                        'Seat' => 'High Density Foam',
                        'Cover' => 'Mesh',
                    ],
                    'Ergonomi' => [
                        'Adjustable Height' => 'Yes',
                        'Adjustable Armrest' => 'Yes',
                        'Lumbar Support' => 'Adjustable',
                        'Reclining' => '90–135°',
                    ],
                    'Kapasitas' => [
                        'Maximum Load' => '120kg',
                    ],
                ],
            ],

            [
                'category' => 'Rumah Tangga',
                'sub_category' => 'Dapur',
                'name' => 'Air Fryer Digital 5L',
                'description' => 'Air fryer digital 5 liter dengan 8 program memasak, layar digital, dan perlindungan overheat.',
                'price' => 899000,
                'stock' => 24,
                'image' => 'https://images.unsplash.com/photo-1585515320310-259814833e62?auto=format&fit=crop&w=800&q=80',
                'specifications' => [
                    'Informasi Umum' => [
                        'Brand' => 'MeiKitchen',
                        'Model' => 'Air Fryer 5L',
                        'Color' => 'Black',
                        'Capacity' => '5L',
                    ],
                    'Performance' => [
                        'Power' => '1500W',
                        'Temperature' => '80–200°C',
                        'Timer' => '60 Minutes',
                    ],
                    'Fitur' => [
                        'Digital Display' => 'Yes',
                        'Cooking Programs' => '8',
                        'Overheat Protection' => 'Yes',
                        'Non-Stick Basket' => 'Yes',
                    ],
                ],
            ],

            [
                'category' => 'Olahraga',
                'sub_category' => 'Fitness',
                'name' => 'Yoga Mat Premium',
                'description' => 'Yoga mat premium berbahan TPE dengan ketebalan 8mm dan permukaan anti-slip.',
                'price' => 299000,
                'stock' => 45,
                'image' => 'https://images.unsplash.com/photo-1601925260368-ae2f83cf8b7f?auto=format&fit=crop&w=800&q=80',
                'specifications' => [
                    'Informasi Umum' => [
                        'Brand' => 'MeiSport',
                        'Model' => 'Yoga Mat Premium',
                        'Color' => 'Purple',
                        'Type' => 'Exercise Mat',
                    ],
                    'Material' => [
                        'Material' => 'TPE',
                        'Thickness' => '8mm',
                        'Surface' => 'Anti-Slip',
                    ],
                    'Dimensi' => [
                        'Length' => '183cm',
                        'Width' => '61cm',
                        'Thickness' => '8mm',
                    ],
                    'Fitur' => [
                        'Waterproof' => 'Yes',
                        'Easy to Clean' => 'Yes',
                        'Lightweight' => 'Yes',
                        'Carry Strap' => 'Included',
                    ],
                ],
            ],

            [
                'category' => 'Olahraga',
                'sub_category' => 'Outdoor',
                'name' => 'Camping Tent 4P',
                'description' => 'Tenda camping untuk empat orang dengan waterproof rating 3000mm dan perlindungan UV.',
                'price' => 1299000,
                'stock' => 12,
                'image' => 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?auto=format&fit=crop&w=800&q=80',
                'specifications' => [
                    'Informasi Umum' => [
                        'Brand' => 'MeiOutdoor',
                        'Model' => 'Camping Tent 4P',
                        'Capacity' => '4 Persons',
                        'Color' => 'Forest Green',
                    ],
                    'Material' => [
                        'Outer' => 'Polyester 210T',
                        'Floor' => 'Oxford 150D',
                        'Pole' => 'Fiberglass',
                    ],
                    'Protection' => [
                        'Waterproof' => 'Yes',
                        'Waterproof Rating' => '3000mm',
                        'UV Protection' => 'Yes',
                    ],
                    'Dimensi' => [
                        'Setup' => '240 × 210 × 150cm',
                        'Packed' => '60 × 20cm',
                    ],
                ],
            ],

            [
                'category' => 'Olahraga',
                'sub_category' => 'Outdoor',
                'name' => 'Travel Duffel Bag 40L',
                'description' => 'Tas travel 40L berbahan polyester 600D dengan kompartemen sepatu dan beberapa pocket tambahan.',
                'price' => 449000,
                'stock' => 30,
                'image' => 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&w=800&q=80',
                'specifications' => [
                    'Informasi Umum' => [
                        'Brand' => 'MeiOutdoor',
                        'Model' => 'Duffel 40L',
                        'Color' => 'Black',
                        'Capacity' => '40L',
                    ],
                    'Material' => [
                        'Main' => 'Polyester 600D',
                        'Lining' => 'Polyester',
                        'Water Resistant' => 'Yes',
                    ],
                    'Kompartemen' => [
                        'Main Compartment' => 'Yes',
                        'Shoe Compartment' => 'Yes',
                        'Front Pocket' => 'Yes',
                        'Inner Pocket' => 'Yes',
                    ],
                    'Dimensi' => [
                        'Ukuran' => '55 × 30 × 25cm',
                        'Weight' => '0.9kg',
                    ],
                ],
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | Insert / update products and specifications
        |--------------------------------------------------------------------------
        */

        $productModels = [];

        foreach ($products as $data) {
            $category = Category::where('name', $data['category'])->firstOrFail();

            $subCategory = SubCategory::where('category_id', $category->id)
                ->where('name', $data['sub_category'])
                ->firstOrFail();

            $product = Product::updateOrCreate(
                [
                    'slug' => Str::slug($data['name']),
                ],
                [
                    'category_id' => $category->id,
                    'sub_category_id' => $subCategory->id,
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'price' => $data['price'],
                    'stock' => $data['stock'],
                    'image' => $data['image'],
                ]
            );

            $productModels[$data['name']] = $product;

            ProductSpecification::where('product_id', $product->id)->delete();

            $sortOrder = 0;

            foreach ($data['specifications'] as $group => $specifications) {
                foreach ($specifications as $name => $value) {
                    ProductSpecification::create([
                        'product_id' => $product->id,
                        'specification_group' => $group,
                        'specification_name' => $name,
                        'specification_value' => $value,
                        'sort_order' => $sortOrder++,
                    ]);
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Reviews
        |--------------------------------------------------------------------------
        |
        | Each review needs:
        | - customer
        | - completed order
        | - order item
        | - product
        |
        */

        $reviewTemplates = [
            [
                'rating' => 5,
                'title' => 'Sangat puas',
                'comment' => 'Produknya bagus dan sesuai dengan deskripsi. Kualitasnya terasa baik dan pengirimannya juga cepat.',
            ],
            [
                'rating' => 5,
                'title' => 'Recommended',
                'comment' => 'Barang datang dengan kondisi baik. Secara keseluruhan saya sangat puas dengan pembelian ini.',
            ],
            [
                'rating' => 4,
                'title' => 'Bagus',
                'comment' => 'Kualitas produk bagus dan sesuai ekspektasi. Ada sedikit kekurangan tetapi tidak mengganggu penggunaan.',
            ],
            [
                'rating' => 5,
                'title' => 'Sesuai ekspektasi',
                'comment' => 'Produk terlihat bagus, nyaman digunakan, dan kualitasnya sesuai dengan harga.',
            ],
            [
                'rating' => 4,
                'title' => 'Worth it',
                'comment' => 'Menurut saya produk ini cukup worth it. Material dan finishing-nya bagus untuk kelas harganya.',
            ],
        ];

        foreach ($productModels as $product) {
            /*
             * Hapus review demo sebelumnya untuk produk ini.
             * Hanya user demo yang dihapus, bukan review customer asli.
             */
            Review::where('product_id', $product->id)
                ->whereIn('user_id', $customers->pluck('id'))
                ->delete();

            /*
             * Buat 5 review untuk setiap produk.
             */
            foreach ($customers as $index => $customer) {
                $template = $reviewTemplates[$index];

                $orderNumber = 'DEMO-' . strtoupper(
                    Str::slug($product->slug) . '-' . $customer->id
                );

                $order = Order::updateOrCreate(
                    [
                        'order_number' => $orderNumber,
                    ],
                    [
                        'user_id' => $customer->id,
                        'status' => 'completed',
                        'customer_name' => $customer->name,
                        'phone' => '0812345678' . $customer->id,
                        'shipping_address' => 'Jl. Demo Portfolio No. ' . $customer->id . ', Indonesia',
                        'notes' => 'Demo order generated by DemoCatalogSeeder.',
                        'subtotal' => $product->price,
                        'shipping_cost' => 0,
                        'total' => $product->price,
                    ]
                );

                OrderItem::updateOrCreate(
                    [
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                    ],
                    [
                        'product_name' => $product->name,
                        'price' => $product->price,
                        'quantity' => 1,
                        'subtotal' => $product->price,
                    ]
                );

                Review::updateOrCreate(
                    [
                        'user_id' => $customer->id,
                        'product_id' => $product->id,
                    ],
                    [
                        'order_id' => $order->id,
                        'rating' => $template['rating'],
                        'title' => $template['title'],
                        'comment' => $template['comment'],
                        'is_verified' => true,
                    ]
                );
            }
        }

        $this->command->info('Demo catalog successfully created.');
        $this->command->info('Products: ' . count($productModels));
        $this->command->info('Customers: ' . $customers->count());
        $this->command->info('Reviews: ' . (count($productModels) * $customers->count()));
    }
}
