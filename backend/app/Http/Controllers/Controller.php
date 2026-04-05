<?php

namespace App\Http\Controllers;

use Illuminate\Http\Resources\Json\JsonResource;
use Symfony\Component\HttpFoundation\JsonResponse;

abstract class Controller
{
    protected function jsonResponse($data = null, string $message = 'Success', int $statusCode = 200, $meta = null): JsonResponse
    {
        // Unwrap JsonResource/ResourceCollection to keep a flat response.data
        if ($data instanceof JsonResource) {
            $payload = $data->response()->getData(true);
            $data = $payload['data'] ?? $payload;
        }

        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];

        if ($meta) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $statusCode);
    }

    protected function errorJsonResponse(string $message = 'Failed', int $statusCode = 400, $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    protected function paginatedJsonResponse($data, int $total, int $page, int $limit): JsonResponse
    {
        // If a resource collection was passed, unwrap to its inner data array
        if ($data instanceof JsonResource) {
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
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($total / $limit),
            ],
        ]);
    }
}
