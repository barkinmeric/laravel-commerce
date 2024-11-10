<?php

namespace Tests\Unit;

use App\Exceptions\OrderProcessingException;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use App\Services\OrderProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderProcessorTest extends TestCase
{
    use RefreshDatabase;

    private OrderProcessor $orderProcessor;

    private Product $product;

    public function setUp(): void
    {
        parent::setUp();

        $this->orderProcessor = new OrderProcessor;

        // Create test products
        $this->product = Product::factory()->create([
            'name' => 'Test Product',
            'price' => 100,
            'tax' => 10,
            'discount' => 5,
            'stock' => 10,
        ]);
    }

    public function test_process_order_creates_order_and_invoice()
    {
        $orderData = [
            'products' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'price' => 100,
                ],
            ],
        ];

        $order = $this->orderProcessor->processOrder($orderData);

        // Assert order was created correctly
        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals('completed', $order->status);

        // Assert products were attached
        $this->assertEquals(1, $order->products->count());
        $this->assertEquals(2, $order->products->first()->pivot->quantity);
        $this->assertEquals(100, $order->products->first()->pivot->price);

        // Assert stock was updated
        $this->assertEquals(8, $this->product->fresh()->stock);

        // Assert invoice was created
        $this->assertInstanceOf(Invoice::class, $order->invoice);
        $this->assertEquals($order->total_amount, $order->invoice->total_amount);
    }

    public function test_order_number_generation_is_unique()
    {
        $orderData = [
            'products' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'price' => 100,
                ],
            ],
        ];

        $order1 = $this->orderProcessor->processOrder($orderData);
        $order2 = $this->orderProcessor->processOrder($orderData);

        $this->assertNotEquals($order1->order_number, $order2->order_number);
    }

    public function test_invoice_number_generation_is_unique()
    {
        $orderData = [
            'products' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'price' => 100,
                ],
            ],
        ];

        $order1 = $this->orderProcessor->processOrder($orderData);
        $order2 = $this->orderProcessor->processOrder($orderData);

        $this->assertNotEquals(
            $order1->invoice->invoice_number,
            $order2->invoice->invoice_number
        );
    }

    public function test_calculate_total_works_correctly()
    {
        $orderData = [
            'products' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'price' => 100,
                ],
            ],
        ];

        $order = $this->orderProcessor->processOrder($orderData);

        // Price: 100
        // Tax: 10%
        // Discount: 5%
        // Final price per unit: 100 * 1.1 * 0.95 = 104.50
        // Total for 2 units: 104.50 * 2 = 209.00
        $this->assertEquals(209.00, $order->total_amount);
        $this->assertEquals(8, $this->product->fresh()->stock);
    }

    public function test_throws_exception_for_insufficient_stock()
    {
        $this->expectException(OrderProcessingException::class);
        $this->expectExceptionMessage('Some products are not available in requested quantity');

        $orderData = [
            'products' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 20, // More than available stock
                    'price' => 100,
                ],
            ],
        ];

        $this->orderProcessor->processOrder($orderData);
    }

    public function test_multiple_products_order_processing()
    {
        $product2 = Product::factory()->create([
            'price' => 200,
            'tax' => 10,
            'discount' => 0,
            'stock' => 5,
        ]);

        $orderData = [
            'products' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'price' => 100,
                ],
                [
                    'product_id' => $product2->id,
                    'quantity' => 3,
                    'price' => 200,
                ],
            ],
        ];

        $order = $this->orderProcessor->processOrder($orderData);

        $this->assertEquals(8, $this->product->fresh()->stock);
        $this->assertEquals(2, $product2->fresh()->stock);
        $this->assertEquals(2, $order->products->count());
    }
}
