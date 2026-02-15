<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    // Success response
    public function successResponse($data, int $status = 200, $message = null): JsonResponse
    {
        $response = ['success' => true];

        if ($message) {
            $response['message'] = $message;
        }

        $response['data'] = $data;

        return response()->json($response, $status);
    }

    // Error response
    public function errorResponse(string $message, int $status = 400, $data = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message
        ];

        if ($data) {
            $response['data'] = $data;
        }

        return response()->json($response, $status);
    }
}
