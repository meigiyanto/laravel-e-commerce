<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            [
                'name' => 'Budi Santoso',
                'email' => 'budi@example.com',
            ],
            [
                'name' => 'Siti Rahma',
                'email' => 'siti@example.com',
            ],
            [
                'name' => 'Andi Pratama',
                'email' => 'andi@example.com',
            ],
            [
                'name' => 'Dewi Lestari',
                'email' => 'dewi@example.com',
            ],
            [
                'name' => 'Rizky Maulana',
                'email' => 'rizky@example.com',
            ],
        ];

        $products = Product::orderBy('id')->get();

        if ($products->isEmpty()) {
            $this->command->warn('Tidak ada produk. Jalankan ProductSeeder terlebih dahulu.');

            return;
        }

        /*
         * Setiap customer mendapatkan satu order completed.
         * Produk yang terdapat pada order tersebut kemudian dapat
         * digunakan sebagai sumber review terverifikasi.
         */
        foreach ($customers as $index => $customerData) {
            $customer = User::updateOrCreate(
                [
                    'email' => $customerData['email'],
                ],
                [
                    'name' => $customerData['name'],
                    'password' => Hash::make('password123'),
                    'role' => 'customer',
                    'email_verified_at' => now(),
                ]
            );

            /*
             * Setiap customer mereview beberapa produk berbeda.
             */
            $reviewProducts = $products
                ->slice(($index * 2) % $products->count(), min(5, $products->count()))
                ->values();

            /*
             * Jika hasil slice terlalu sedikit karena jumlah produk,
             * gunakan beberapa produk pertama.
             */
            if ($reviewProducts->count() < 3) {
                $reviewProducts = $products->take(min(5, $products->count()));
            }

            $subtotal = $reviewProducts->sum(
                fn (Product $product) => (float) $product->price
            );

            $order = Order::updateOrCreate(
                [
                    'order_number' => 'DUMMY-REVIEW-' . str_pad(
                        $index + 1,
                        4,
                        '0',
                        STR_PAD_LEFT
                    ),
                ],
                [
                    'user_id' => $customer->id,
                    'status' => 'completed',
                    'customer_name' => $customer->name,
                    'phone' => '08123456789' . $index,
                    'shipping_address' => 'Jl. Dummy No. ' . ($index + 1) . ', Indonesia',
                    'notes' => 'Dummy order untuk data review.',
                    'subtotal' => $subtotal,
                    'shipping_cost' => 15000,
                    'total' => $subtotal + 15000,
                ]
            );

            foreach ($reviewProducts as $product) {
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
            }
        }

        /*
         * Data rating dummy.
         *
         * Rating dibuat bervariasi agar tampilan:
         * - average rating
         * - rating distribution
         * - review list
         * terlihat realistis.
         */
        $reviewTemplates = [
            5 => [
                [
                    'title' => 'Produk sangat bagus',
                    'comment' => 'Produknya sesuai deskripsi dan kualitasnya sangat bagus. Saya puas dengan pembelian ini.',
                ],
                [
                    'title' => 'Sangat recommended',
                    'comment' => 'Kualitas produk bagus, pengiriman juga cepat. Sangat recommended.',
                ],
                [
                    'title' => 'Puas dengan produknya',
                    'comment' => 'Barang datang dengan kondisi baik dan sesuai dengan informasi di halaman produk.',
                ],
            ],
            4 => [
                [
                    'title' => 'Bagus dan sesuai',
                    'comment' => 'Produk sesuai dengan deskripsi. Kualitasnya bagus dan cukup memuaskan.',
                ],
                [
                    'title' => 'Worth it',
                    'comment' => 'Secara keseluruhan bagus dan sesuai dengan harga yang ditawarkan.',
                ],
            ],
            3 => [
                [
                    'title' => 'Cukup bagus',
                    'comment' => 'Produk cukup bagus dan berfungsi dengan baik, meskipun masih ada beberapa hal yang bisa ditingkatkan.',
                ],
                [
                    'title' => 'Lumayan',
                    'comment' => 'Kualitasnya cukup sesuai ekspektasi. Tidak buruk, tetapi juga belum sempurna.',
                ],
            ],
            2 => [
                [
                    'title' => 'Masih perlu diperbaiki',
                    'comment' => 'Produknya masih bisa digunakan, tetapi kualitasnya belum sesuai dengan ekspektasi saya.',
                ],
            ],
            1 => [
                [
                    'title' => 'Kurang puas',
                    'comment' => 'Saya kurang puas dengan produk ini. Ada beberapa hal yang menurut saya perlu diperbaiki.',
                ],
            ],
        ];

        $reviews = [
            [
                'customer' => 'budi@example.com',
                'product' => 'Smartphone X1',
                'rating' => 5,
            ],
            [
                'customer' => 'budi@example.com',
                'product' => 'Pro Laptop 14',
                'rating' => 4,
            ],
            [
                'customer' => 'budi@example.com',
                'product' => 'Wireless Headset',
                'rating' => 5,
            ],

            [
                'customer' => 'siti@example.com',
                'product' => "Classic Men's Jacket",
                'rating' => 5,
            ],
            [
                'customer' => 'siti@example.com',
                'product' => "Women's Casual Dress",
                'rating' => 4,
            ],
            [
                'customer' => 'siti@example.com',
                'product' => 'Modern Sofa',
                'rating' => 5,
            ],

            [
                'customer' => 'andi@example.com',
                'product' => 'Dapur Cookware Set',
                'rating' => 4,
            ],
            [
                'customer' => 'andi@example.com',
                'product' => 'Adjustable Dumbbell',
                'rating' => 5,
            ],
            [
                'customer' => 'andi@example.com',
                'product' => 'Running Shoes Pro',
                'rating' => 4,
            ],

            [
                'customer' => 'dewi@example.com',
                'product' => 'Hiking Backpack',
                'rating' => 5,
            ],
            [
                'customer' => 'dewi@example.com',
                'product' => 'Smartphone X1',
                'rating' => 4,
            ],
            [
                'customer' => 'dewi@example.com',
                'product' => 'Modern Sofa',
                'rating' => 3,
            ],

            [
                'customer' => 'rizky@example.com',
                'product' => 'Pro Laptop 14',
                'rating' => 5,
            ],
            [
                'customer' => 'rizky@example.com',
                'product' => 'Wireless Headset',
                'rating' => 4,
            ],
            [
                'customer' => 'rizky@example.com',
                'product' => 'Running Shoes Pro',
                'rating' => 3,
            ],
        ];

        foreach ($reviews as $index => $reviewData) {
            $user = User::where('email', $reviewData['customer'])->first();

            $product = Product::where('name', $reviewData['product'])->first();

            if (! $user || ! $product) {
                continue;
            }

            $orderItem = OrderItem::where('product_id', $product->id)
                ->whereHas('order', function ($query) use ($user) {
                    $query
                        ->where('user_id', $user->id)
                        ->where('status', 'completed');
                })
                ->first();

            if (! $orderItem) {
                continue;
            }

            $templates = $reviewTemplates[$reviewData['rating']];
            $template = $templates[$index % count($templates)];

            Review::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'product_id' => $product->id,
                ],
                [
                    'order_id' => $orderItem->order_id,
                    'rating' => $reviewData['rating'],
                    'title' => $template['title'],
                    'comment' => $template['comment'],
                    'is_verified' => true,
                ]
            );
        }

        $this->command->info(
            'Dummy customer, completed orders, order items, dan product reviews berhasil dibuat.'
        );
    }
}
