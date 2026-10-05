<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            CategorySeeder::class,
            SubCategorySeeder::class,
            ProductSeeder::class,
            ProductSpecificationSeeder::class,
            ReviewSeeder::class,
            DemoCatalogSeeder::class
        ]);
    }
}
