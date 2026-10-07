<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductSpecification;
use Illuminate\Database\Seeder;

class ProductSpecificationSeeder extends Seeder
{
    public function run(): void
    {
        $specifications = [

            /*
            |--------------------------------------------------------------------------
            | Smartphone X1
            |--------------------------------------------------------------------------
            */
            'Smartphone X1' => [
                'Informasi Umum' => [
                    'Brand' => 'MeiPhone',
                    'Model' => 'X1',
                    'Warna' => 'Midnight Black',
                    'Garansi' => '1 Tahun Garansi Resmi',
                ],

                'Layar' => [
                    'Ukuran Layar' => '6.67 inch',
                    'Panel' => 'AMOLED',
                    'Resolusi' => '2400 × 1080 pixels',
                    'Refresh Rate' => '120 Hz',
                ],

                'Performa' => [
                    'Processor' => 'Octa-Core 2.8 GHz',
                    'RAM' => '8 GB',
                    'Penyimpanan' => '256 GB',
                    'Sistem Operasi' => 'Android',
                ],

                'Kamera' => [
                    'Kamera Utama' => '50 MP',
                    'Kamera Ultrawide' => '8 MP',
                    'Kamera Depan' => '16 MP',
                ],

                'Baterai' => [
                    'Kapasitas' => '5000 mAh',
                    'Fast Charging' => '67 W',
                ],
            ],

            /*
            |--------------------------------------------------------------------------
            | Pro Laptop 14
            |--------------------------------------------------------------------------
            */
            'Pro Laptop 14' => [
                'Informasi Umum' => [
                    'Brand' => 'MeiBook',
                    'Model' => 'Pro Laptop 14',
                    'Warna' => 'Space Gray',
                    'Garansi' => '1 Tahun',
                ],

                'Display' => [
                    'Ukuran Layar' => '14 inch',
                    'Panel' => 'IPS',
                    'Resolusi' => '2560 × 1600 pixels',
                    'Refresh Rate' => '120 Hz',
                ],

                'Performa' => [
                    'Processor' => 'Intel Core i7',
                    'RAM' => '16 GB',
                    'Penyimpanan' => '512 GB NVMe SSD',
                    'GPU' => 'Integrated Graphics',
                ],

                'Konektivitas' => [
                    'Wi-Fi' => 'Wi-Fi 6',
                    'Bluetooth' => 'Bluetooth 5.3',
                    'USB' => 'USB-C, USB-A',
                    'HDMI' => 'HDMI 2.1',
                ],

                'Baterai' => [
                    'Kapasitas' => '70 Wh',
                    'Daya Tahan' => 'Hingga 12 jam',
                ],
            ],

            /*
            |--------------------------------------------------------------------------
            | Wireless Headset
            |--------------------------------------------------------------------------
            */
            'Wireless Headset' => [
                'Informasi Umum' => [
                    'Brand' => 'MeiAudio',
                    'Model' => 'WH-01',
                    'Warna' => 'Black',
                    'Tipe' => 'Over-Ear',
                ],

                'Audio' => [
                    'Driver' => '40 mm',
                    'Frequency Response' => '20 Hz - 20 kHz',
                    'Noise Cancellation' => 'Active Noise Cancellation',
                    'Microphone' => 'Built-in',
                ],

                'Konektivitas' => [
                    'Bluetooth' => 'Bluetooth 5.3',
                    'Jarak' => 'Hingga 10 meter',
                    'Port' => 'USB-C',
                ],

                'Baterai' => [
                    'Kapasitas' => '600 mAh',
                    'Waktu Pemakaian' => 'Hingga 40 jam',
                    'Charging' => 'USB-C Fast Charging',
                ],
            ],

            /*
            |--------------------------------------------------------------------------
            | Classic Men's Jacket
            |--------------------------------------------------------------------------
            */
            "Classic Men's Jacket" => [
                'Informasi Umum' => [
                    'Brand' => 'MeiWear',
                    'Model' => 'Classic Jacket',
                    'Jenis' => 'Jaket Casual',
                    'Gender' => 'Pria',
                ],

                'Material' => [
                    'Bahan' => 'Cotton Twill',
                    'Lapisan' => 'Polyester',
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

            /*
            |--------------------------------------------------------------------------
            | Women's Casual Dress
            |--------------------------------------------------------------------------
            */
            "Women's Casual Dress" => [
                'Informasi Umum' => [
                    'Brand' => 'MeiFashion',
                    'Model' => 'Casual Dress',
                    'Gender' => 'Wanita',
                    'Style' => 'Casual',
                ],

                'Material' => [
                    'Bahan' => 'Cotton Blend',
                    'Tekstur' => 'Soft',
                    'Karakter' => 'Breathable',
                ],

                'Ukuran' => [
                    'Size' => 'S, M, L, XL',
                    'Fit' => 'Regular Fit',
                ],

                'Perawatan' => [
                    'Pencucian' => 'Hand Wash / Machine Wash',
                    'Setrika' => 'Low Temperature',
                ],
            ],

            /*
            |--------------------------------------------------------------------------
            | Modern Sofa
            |--------------------------------------------------------------------------
            */
            'Modern Sofa' => [
                'Informasi Umum' => [
                    'Brand' => 'MeiHome',
                    'Model' => 'Modern Sofa 3 Seater',
                    'Warna' => 'Light Gray',
                    'Kapasitas' => '3 Orang',
                ],

                'Material' => [
                    'Frame' => 'Solid Wood',
                    'Material Pelapis' => 'Polyester Fabric',
                    'Cushion' => 'High Density Foam',
                ],

                'Dimensi' => [
                    'Panjang' => '210 cm',
                    'Lebar' => '85 cm',
                    'Tinggi' => '80 cm',
                ],

                'Informasi Tambahan' => [
                    'Assembly' => 'Diperlukan',
                    'Garansi' => '1 Tahun',
                ],
            ],

            /*
            |--------------------------------------------------------------------------
            | Dapur Cookware Set
            |--------------------------------------------------------------------------
            */
            'Dapur Cookware Set' => [
                'Informasi Umum' => [
                    'Brand' => 'MeiKitchen',
                    'Model' => 'Cookware Set 10 pcs',
                    'Warna' => 'Black',
                    'Jumlah' => '10 Pieces',
                ],

                'Material' => [
                    'Bahan Utama' => 'Aluminium',
                    'Lapisan' => 'Non-Stick Coating',
                    'Handle' => 'Heat Resistant',
                ],

                'Kompatibilitas' => [
                    'Gas Stove' => 'Ya',
                    'Electric Stove' => 'Ya',
                    'Induction Stove' => 'Ya',
                ],

                'Perawatan' => [
                    'Dishwasher' => 'Ya',
                    'Oven Safe' => 'Tidak',
                ],
            ],

            /*
            |--------------------------------------------------------------------------
            | Adjustable Dumbbell
            |--------------------------------------------------------------------------
            */
            'Adjustable Dumbbell' => [
                'Informasi Umum' => [
                    'Brand' => 'MeiSport',
                    'Model' => 'Adjustable Dumbbell Pro',
                    'Warna' => 'Black',
                    'Tipe' => 'Adjustable',
                ],

                'Spesifikasi' => [
                    'Berat Minimum' => '2.5 kg',
                    'Berat Maksimum' => '24 kg',
                    'Increment' => '2 kg',
                    'Material' => 'Cast Iron',
                ],

                'Dimensi' => [
                    'Panjang' => '45 cm',
                    'Lebar' => '20 cm',
                    'Tinggi' => '20 cm',
                ],
            ],

            /*
            |--------------------------------------------------------------------------
            | Running Shoes Pro
            |--------------------------------------------------------------------------
            */
            'Running Shoes Pro' => [
                'Informasi Umum' => [
                    'Brand' => 'MeiSport',
                    'Model' => 'Running Shoes Pro',
                    'Gender' => 'Unisex',
                    'Jenis' => 'Running Shoes',
                ],

                'Material' => [
                    'Upper' => 'Engineered Mesh',
                    'Midsole' => 'EVA Foam',
                    'Outsole' => 'Rubber',
                ],

                'Ukuran' => [
                    'Size' => '39 - 44',
                    'Fit' => 'Regular Fit',
                ],

                'Teknologi' => [
                    'Cushioning' => 'Responsive Foam',
                    'Breathability' => 'High',
                    'Anti Slip' => 'Ya',
                ],
            ],

            /*
            |--------------------------------------------------------------------------
            | Hiking Backpack
            |--------------------------------------------------------------------------
            */
            'Hiking Backpack' => [
                'Informasi Umum' => [
                    'Brand' => 'MeiOutdoor',
                    'Model' => 'Hiking Backpack 40L',
                    'Warna' => 'Dark Green',
                    'Jenis' => 'Hiking Backpack',
                ],

                'Kapasitas' => [
                    'Volume' => '40 Liter',
                    'Kompartemen Utama' => '1',
                    'Kantong Eksternal' => '4',
                ],

                'Material' => [
                    'Material Utama' => 'Ripstop Nylon',
                    'Water Resistance' => 'Water Resistant',
                    'Frame' => 'Aluminium Frame',
                ],

                'Fitur' => [
                    'Chest Strap' => 'Ya',
                    'Hip Belt' => 'Ya',
                    'Rain Cover' => 'Termasuk',
                ],

                'Dimensi' => [
                    'Tinggi' => '65 cm',
                    'Lebar' => '32 cm',
                    'Kedalaman' => '24 cm',
                ],
            ],
        ];

        foreach ($specifications as $productName => $groups) {
            $product = Product::where('name', $productName)->first();

            if (! $product) {
                $this->command->warn(
                    "Produk tidak ditemukan: {$productName}"
                );

                continue;
            }

            ProductSpecification::where('product_id', $product->id)->delete();

            $sortOrder = 0;

            foreach ($groups as $groupName => $items) {
                foreach ($items as $name => $value) {
                    ProductSpecification::create([
                        'product_id' => $product->id,
                        'specification_group' => $groupName,
                        'specification_name' => $name,
                        'specification_value' => $value,
                        'sort_order' => $sortOrder++,
                    ]);
                }
            }
        }

        $this->command->info(
            'Dummy product specifications berhasil dibuat.'
        );
    }
}
