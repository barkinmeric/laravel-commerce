<?php

namespace App\Jobs;

use App\Exceptions\OrderProcessingException;
use App\Services\OrderProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private array $orderData;

    public function getOrderData(): array
    {
        return $this->orderData;
    }

    /**
     * Create a new job instance.
     */
    public function __construct(array $orderData)
    {
        $this->orderData = $orderData;
    }

    /**
     * Execute the job.
     */
    public function handle(OrderProcessor $processor)
    {
        try {
            return $processor->processOrder($this->orderData);
        } catch (OrderProcessingException $e) {
            Log::error('Order processing job failed: '.$e->getMessage(), [
                'errors' => $e->getErrors(),
                'order_data' => $this->orderData,
            ]);
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception)
    {
        Log::error('Order processing job failed with exception: '.$exception->getMessage(), [
            'order_data' => $this->orderData,
            'exception' => $exception,
        ]);
    }
}
