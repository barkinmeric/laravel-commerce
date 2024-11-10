<?php

namespace App\Services;

use App\Exceptions\OrderProcessingException;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderProcessor
{
    /**
     * Process a new order
     *
     * @throws OrderProcessingException
     */
    public function processOrder(array $orderData): Order
    {
        try {
            // Check stock availability before starting transaction
            $this->checkStockAvailability($orderData['products']);

            DB::beginTransaction();

            // Create order
            $order = $this->createOrder($orderData);

            // Attach products and update stock
            $this->processOrderProducts($order, $orderData['products']);

            // Calculate total
            $total = $order->calculateTotal();
            $order->update(['total_amount' => $total]);

            // Generate invoice
            $this->generateInvoice($order);

            // Update order status
            $order->update(['status' => 'completed']);

            DB::commit();

            return $order;

        } catch (OrderProcessingException $e) {
            DB::rollBack();
            Log::error('Order processing failed: '.$e->getMessage(), [
                'errors' => $e->getErrors(),
                'order_data' => $orderData,
            ]);
            throw $e;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Unexpected error during order processing: '.$e->getMessage(), [
                'order_data' => $orderData,
            ]);
            throw new OrderProcessingException('An unexpected error occurred while processing the order');
        }
    }

    /**
     * Check if all products are available in requested quantities
     *
     * @throws OrderProcessingException
     */
    private function checkStockAvailability(array $products): void
    {
        $unavailableProducts = [];

        foreach ($products as $item) {
            $product = Product::query()
                ->where('id', $item['product_id'])
                ->lockForUpdate()
                ->first();

            if (! $product) {
                throw new OrderProcessingException(
                    'Product not found',
                    ['product_id' => "Product {$item['product_id']} does not exist"]
                );
            }

            if ($product->stock < $item['quantity']) {
                $unavailableProducts[] = [
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'requested' => $item['quantity'],
                    'available' => $product->stock,
                ];
            }
        }

        if (! empty($unavailableProducts)) {
            throw new OrderProcessingException(
                'Some products are not available in requested quantity',
                ['unavailable_products' => $unavailableProducts]
            );
        }
    }

    /**
     * Create new order
     */
    private function createOrder(): Order
    {
        return Order::create([
            'order_number' => $this->generateOrderNumber(),
            'status' => 'pending',
            'total_amount' => 0, // Will be calculated later
        ]);
    }

    /**
     * Process products for order and update stock
     */
    private function processOrderProducts(Order $order, array $products): void
    {
        foreach ($products as $item) {
            $product = Product::query()
                ->where('id', $item['product_id'])
                ->lockForUpdate()
                ->first();

            // Attach product to order
            $order->products()->attach($item['product_id'], [
                'quantity' => $item['quantity'],
                'price' => $item['price'],
            ]);

            // Update stock
            $product->decrement('stock', $item['quantity']);
        }
    }

    /**
     * Generate invoice for order
     */
    private function generateInvoice(Order $order): Invoice
    {
        return Invoice::create([
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'order_id' => $order->id,
            'total_amount' => $order->total_amount,
            'order_date' => now(),
            'status' => 'issued',
        ]);
    }

    /**
     * Generate unique order number
     */
    private function generateOrderNumber(): string
    {
        $prefix = 'ORD';
        $year = date('Y');
        $lastOrder = Order::whereYear('created_at', $year)->latest()->first();
        $sequence = $lastOrder ? intval(substr($lastOrder->order_number, -5)) + 1 : 1;

        return $prefix.$year.str_pad($sequence, 5, '0', STR_PAD_LEFT);
    }
}
