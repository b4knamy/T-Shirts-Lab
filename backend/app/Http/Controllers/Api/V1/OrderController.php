<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Order\StoreOrderRequest;
use App\Http\Requests\Api\V1\Order\UpdateOrderStatusRequest;
use App\Http\Resources\Api\V1\OrderResource;
use App\Services\OrderService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly OrderService $orderService
    ) {}

    public function store(StoreOrderRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        try {
            $order = $this->orderService->createOrder($request->validated(), $user->id);
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 400);
        }

        return $this->success(new OrderResource($order), 'Order created', 201);
    }

    public function myOrders(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $page = (int) $request->get('page', 1);
        $limit = min((int) $request->get('limit', 20), 100);

        ['orders' => $orders, 'total' => $total] = $this->orderService->paginateForUser($user->id, $page, $limit);

        return $this->paginated(OrderResource::collection($orders), $total, $page, $limit);
    }

    public function show(string $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $order = $this->orderService->findById($id);

        if (! $order) {
            return $this->error('Order not found', 404);
        }

        if ($user->role === 'CUSTOMER' && $order->user_id !== $user->id) {
            return $this->error('Forbidden', 403);
        }

        return $this->success(new OrderResource($order));
    }

    // Admin
    public function index(Request $request): JsonResponse
    {
        $page = (int) $request->get('page', 1);
        $limit = min((int) $request->get('limit', 20), 100);

        $filters = $request->only(['search', 'status', 'payment_status']);

        ['orders' => $orders, 'total' => $total] = $this->orderService->paginateAll($filters, $page, $limit);

        return $this->paginated(OrderResource::collection($orders), $total, $page, $limit);
    }

    public function updateStatus(UpdateOrderStatusRequest $request, string $id): JsonResponse
    {
        try {
            $order = $this->orderService->updateStatus(
                $id,
                $request->validated('status'),
                $request->validated('admin_notes')
            );
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 404);
        }

        return $this->success(new OrderResource($order), 'Order status updated');
    }
}
