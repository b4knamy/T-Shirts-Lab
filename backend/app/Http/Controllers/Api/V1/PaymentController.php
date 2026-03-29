<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Payment\ConfirmPaymentRequest;
use App\Http\Requests\Api\V1\Payment\CreatePaymentIntentRequest;
use App\Http\Requests\Api\V1\Payment\RefundPaymentRequest;
use App\Http\Resources\Api\V1\PaymentResource;
use App\Models\Order;
use App\Services\PaymentService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Stripe\Exception\ApiErrorException;

class PaymentController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly PaymentService $paymentService
    ) {}

    public function createIntent(CreatePaymentIntentRequest $request): JsonResponse
    {
        $user     = JWTAuth::parseToken()->authenticate();
        $order    = Order::find($request->validated('orderId'));

        if (!$order || $order->user_id !== $user->id) {
            return $this->error('Order not found', 404);
        }

        if ($order->payment_status === 'COMPLETED') {
            return $this->error('Order already paid', 400);
        }

        try {
            $result = $this->paymentService->createIntent($order, $request->validated('currency') ?? 'brl');
        } catch (ApiErrorException $e) {
            return $this->error('Payment processing error: ' . $e->getMessage(), 500);
        }

        return $this->success($result, 'Payment intent created');
    }

    public function confirm(ConfirmPaymentRequest $request): JsonResponse
    {
        try {
            $result = $this->paymentService->confirm(
                $request->validated('paymentIntentId'),
                $request->validated('paymentMethodId')
            );
        } catch (ApiErrorException $e) {
            return $this->error('Payment confirmation error: ' . $e->getMessage(), 500);
        }

        return $this->success($result, 'Payment confirmed');
    }

    public function status(string $paymentIntentId): JsonResponse
    {
        try {
            ['payment' => $payment, 'stripeStatus' => $stripeStatus] =
                $this->paymentService->getStatus($paymentIntentId);
        } catch (\RuntimeException $e) {
            return $this->error('Payment not found', 404);
        } catch (ApiErrorException $e) {
            return $this->error('Error retrieving payment status', 500);
        }

        return $this->success([
            'payment'      => new PaymentResource($payment),
            'stripeStatus' => $stripeStatus,
        ]);
    }

    public function refund(RefundPaymentRequest $request): JsonResponse
    {
        try {
            $result = $this->paymentService->refund(
                $request->validated('paymentIntentId'),
                $request->validated('amount'),
                $request->validated('reason')
            );
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 404);
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 400);
        } catch (ApiErrorException $e) {
            return $this->error('Refund error: ' . $e->getMessage(), 500);
        }

        return $this->success($result, 'Refund processed');
    }
}
