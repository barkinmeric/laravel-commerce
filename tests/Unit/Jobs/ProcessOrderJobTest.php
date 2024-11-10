<?php

namespace Tests\Unit\Jobs;

use App\Jobs\ProcessOrderJob;
use App\Models\Product;
use App\Services\OrderProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessOrderJobTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        Product::factory()->create([
            'name' => 'Test Product',
            'price' => 100,
            'tax' => 10,
            'discount' => 5,
            'stock' => 10,
        ]);
    }

    public function test_job_processes_order_correctly()
    {
        $orderData = [
            'products' => [
                [
                    'product_id' => 1,
                    'quantity' => 2,
                    'price' => 100,
                ],
            ],
        ];

        $job = new ProcessOrderJob($orderData);
        $order = $job->handle(new OrderProcessor);

        $this->assertEquals('completed', $order->status);
        $this->assertEquals(209.00, $order->total_amount);
        $this->assertDatabaseHas('order_product', [
            'order_id' => $order->id,
            'product_id' => 1,
            'quantity' => 2,
        ]);
    }

    public function test_job_handles_failed_processing()
    {
        $this->expectException(\App\Exceptions\OrderProcessingException::class);

        $orderData = [
            'products' => [
                [
                    'product_id' => 1,
                    'quantity' => 20, // More than available stock
                    'price' => 100,
                ],
            ],
        ];

        $job = new ProcessOrderJob($orderData);
        $job->handle(new OrderProcessor);
    }
}
