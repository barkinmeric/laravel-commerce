<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition()
    {
        return [
            'name' => $this->faker->word,
            'price' => $this->faker->randomFloat(2, 10, 1000),
            'tax' => $this->faker->randomFloat(2, 0, 20),
            'discount' => $this->faker->randomFloat(2, 0, 50),
            'stock' => $this->faker->numberBetween(0, 100),
        ];
    }
}
