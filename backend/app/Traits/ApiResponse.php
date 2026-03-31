<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    protected function success($data = null, string $message = 'Success', int $statusCode = 200, $meta = null): JsonResponse
    {
        // If a JsonResource or ResourceCollection was passed, unwrap it so
        // the frontend receives the raw array/object it expects under
        // response.data (avoids nested data.data shapes).
        if ($data instanceof \Illuminate\Http\Resources\Json\JsonResource) {
            $payload = $data->response()->getData(true);
            // If the resource produced an array with a 'data' key, return that
            // inner array; otherwise return the whole payload.
            $data = $payload['data'] ?? $payload;
        }

        $response = [
            'success' => true,
            'data' => $data,
            'message' => $message,
        ];

        if ($meta) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $statusCode);
    }

    protected function error(string $message = 'Error', int $statusCode = 400, $errors = null): JsonResponse
    {
        info($message);

        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    protected function paginated($data, int $total, int $page, int $limit): JsonResponse
    {
        // If a Resource or ResourceCollection was passed, unwrap its inner
        // 'data' so the frontend receives an array of items rather than
        // a nested resource object.
        if ($data instanceof \Illuminate\Http\Resources\Json\JsonResource) {
            $payload = $data->response()->getData(true);
            $data = $payload['data'] ?? $payload;
        }

        $payload = [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ];

        return response()->json([
            'success' => true,
            'data' => $payload,
            'meta' => [
                'total'       => $total,
                'page'        => $page,
                'limit'       => $limit,
                'total_pages' => ceil($total / $limit),
            ],
        ]);
    }
}
