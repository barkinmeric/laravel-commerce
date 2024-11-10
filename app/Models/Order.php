<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'total_amount',
        'status',
    ];

    /**
     * Get the products for the order
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'order_product')
            ->withPivot('quantity', 'price')
            ->withTimestamps();
    }

    /**
     * Calculate the total amount of the order
     */
    public function calculateTotal(): float
    {
        return $this->products->sum(function ($product) {
            return $product->getFinalPrice() * $product->pivot->quantity;
        });
    }

    /**
     * Get the invoice for this order
     */
    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }
}
