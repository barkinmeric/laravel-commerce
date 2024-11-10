<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_number',
        'order_id',
        'total_amount',
        'order_date',
        'status',
    ];

    protected $casts = [
        'order_date' => 'datetime',
    ];

    /**
     * Get the order associated with the invoice
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Generate invoice number
     */
    public static function generateInvoiceNumber(): string
    {
        $prefix = 'INV';
        $year = date('Y');
        $lastInvoice = self::whereYear('created_at', $year)->latest()->first();
        $sequence = $lastInvoice ? intval(substr($lastInvoice->invoice_number, -5)) + 1 : 1;

        return $prefix.$year.str_pad($sequence, 5, '0', STR_PAD_LEFT);
    }
}
