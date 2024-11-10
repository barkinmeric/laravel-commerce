<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        Product::create([
            'name' => 'Laptop',
            'price' => 999.99,
            'tax' => 10,
            'discount' => 0,
            'stock' => 10,
        ]);

        Product::create([
            'name' => 'Smartphone',
            'price' => 599.99,
            'tax' => 10,
            'discount' => 5,
            'stock' => 10,
        ]);

        Product::create([
            'name' => 'Headphones',
            'price' => 199.99,
            'tax' => 10,
            'discount' => 0,
            'stock' => 10,
        ]);

    }
}
