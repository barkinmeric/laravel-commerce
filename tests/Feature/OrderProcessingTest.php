<?php

namespace Tests\Feature;

use App\Jobs\ProcessOrderJob;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OrderProcessingTest extends TestCase
{
    use RefreshDatabase;

    private Product $product1;

    private Product $product2;

    public function setUp(): void
    {
        parent::setUp();

        // Fake both Queue and Bus facades
        Queue::fake();
        Bus::fake();

        // Create test products with specific stock
        $this->product1 = Product::factory()->create([
            'name' => 'Test Product 1',
            'price' => 100,
            'tax' => 10,
            'discount' => 5,
            'stock' => 10,
        ]);

        $this->product2 = Product::factory()->create([
            'name' => 'Test Product 2',
            'price' => 200,
            'tax' => 10,
            'discount' => 0,
            'stock' => 5,
        ]);
    }

    public function test_can_create_order()
    {
        $orderData = [
            'products' => [
                [
                    'product_id' => $this->product1->id,
                    'quantity' => 2,
                    'price' => 100,
                ],
            ],
        ];

        $response = $this->postJson('/api/orders', $orderData);

        $response->assertStatus(202)
            ->assertJsonStructure([
                'data' => [
                    'success',
                    'message',
                    'order_reference',
                ],
            ]);

        Bus::assertDispatchedTimes(ProcessOrderJob::class, 1);
    }

    public function test_cannot_create_order_with_invalid_product()
    {
        $orderData = [
            'products' => [
                [
                    'product_id' => 999, // Non-existent product
                    'quantity' => 1,
                    'price' => 100,
                ],
            ],
        ];

        $response = $this->postJson('/api/orders', $orderData);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors',
            ]);

        Bus::assertNotDispatched(ProcessOrderJob::class);
        Queue::assertNotPushed(ProcessOrderJob::class);
    }

    public function test_cannot_create_order_with_invalid_quantity()
    {
        $orderData = [
            'products' => [
                [
                    'product_id' => $this->product1->id,
                    'quantity' => 0, // Invalid quantity
                    'price' => 100,
                ],
            ],
        ];

        $response = $this->postJson('/api/orders', $orderData);

        $response->assertStatus(422);
        Bus::assertNotDispatched(ProcessOrderJob::class);
        Queue::assertNotPushed(ProcessOrderJob::class);
    }

    public function test_order_validation_rules()
    {
        $response = $this->postJson('/api/orders', [
            'products' => [],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['products']);

        Bus::assertNotDispatched(ProcessOrderJob::class);
        Queue::assertNotPushed(ProcessOrderJob::class);
    }
}
