<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'tax',
        'discount',
        'stock',
    ];

    /**
     * Get the final price after tax and discount
     *
     * @return float
     */
    public function getFinalPrice()
    {
        $priceWithTax = $this->price * (1 + ($this->tax / 100));
        $finalPrice = $priceWithTax * (1 - ($this->discount / 100));

        return round($finalPrice, 2);
    }

    /**
     * Get the orders that contain this product
     */
    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'order_product')
            ->withPivot('quantity', 'price')
            ->withTimestamps();
    }
}
