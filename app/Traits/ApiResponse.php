<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

trait ApiResponse
{
    /**
     * Success Response
     */
    protected function successResponse(
        mixed $data,
        ?LengthAwarePaginator $paginator = null,
        int $status = 200
    ): JsonResponse {
        $response = ['success' => true];

        // Handle different types of data
        if ($data instanceof JsonResource || $data instanceof ResourceCollection) {
            $response['data'] = $data;
        } else {
            $response['data'] = $data;
        }

        // Add pagination meta data if paginator is provided
        if ($paginator) {
            $response['meta'] = [
                'current_page' => $paginator->currentPage(),
                'from' => $paginator->firstItem(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'to' => $paginator->lastItem(),
                'total' => $paginator->total(),
            ];

            $response['links'] = [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ];
        }

        return response()->json($response, $status);
    }

    /**
     * Error Response
     */
    protected function errorResponse(
        string $message,
        int $status = 500,
        ?array $errors = null
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $status);
    }

    /**
     * Created Response
     */
    protected function createdResponse(
        mixed $data
    ): JsonResponse {
        return $this->successResponse($data, null, 201);
    }

    /**
     * No Content Response
     */
    protected function noContentResponse(): JsonResponse
    {
        return response()->json(null, 204);
    }
}
