<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateOrderRequest;
use App\Http\Resources\OrderAcceptedResource;
use App\Jobs\ProcessOrderJob;
use App\Services\OrderProcessor;
use Exception;
use Illuminate\Http\Response;

class OrderController extends Controller
{
    protected $orderProcessor;

    public function __construct(OrderProcessor $orderProcessor)
    {
        $this->orderProcessor = $orderProcessor;
    }

    /**
     * Store order asynchronously
     */
    public function store(CreateOrderRequest $request)
    {
        try {
            $orderReference = uniqid('order_', true);

            // Dispatch without waiting
            ProcessOrderJob::dispatch($request->validated())
                ->onQueue('orders');

            return (new OrderAcceptedResource($orderReference))
                ->response()
                ->setStatusCode(Response::HTTP_ACCEPTED);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to queue order',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
