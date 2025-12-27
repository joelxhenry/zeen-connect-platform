<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    /**
     * Return a successful response with data.
     */
    public static function success(mixed $data = null, ?string $message = null, int $status = 200): JsonResponse
    {
        $response = [
            'success' => true,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        if ($message !== null) {
            $response['message'] = $message;
        }

        return response()->json($response, $status);
    }

    /**
     * Return a successful response with just a message.
     */
    public static function message(string $message, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
        ], $status);
    }

    /**
     * Return a 201 Created response.
     */
    public static function created(mixed $data = null, ?string $message = null): JsonResponse
    {
        return self::success($data, $message ?? 'Created successfully', 201);
    }

    /**
     * Return a 204 No Content response.
     */
    public static function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * Return an error response.
     */
    public static function error(string $message, int $status = 400, ?string $code = null): JsonResponse
    {
        $error = [
            'message' => $message,
        ];

        if ($code !== null) {
            $error['code'] = $code;
        }

        return response()->json([
            'success' => false,
            'error' => $error,
        ], $status);
    }

    /**
     * Return a validation error response (422).
     */
    public static function validationError(array $errors, ?string $message = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'message' => $message ?? 'Validation failed',
                'code' => 'VALIDATION_ERROR',
                'errors' => $errors,
            ],
        ], 422);
    }

    /**
     * Return a 404 Not Found response.
     */
    public static function notFound(?string $message = null): JsonResponse
    {
        return self::error($message ?? 'Resource not found', 404, 'NOT_FOUND');
    }

    /**
     * Return a 401 Unauthorized response.
     */
    public static function unauthorized(?string $message = null): JsonResponse
    {
        return self::error($message ?? 'Unauthorized', 401, 'UNAUTHORIZED');
    }

    /**
     * Return a 403 Forbidden response.
     */
    public static function forbidden(?string $message = null): JsonResponse
    {
        return self::error($message ?? 'Forbidden', 403, 'FORBIDDEN');
    }

    /**
     * Return a 500 Server Error response.
     */
    public static function serverError(?string $message = null): JsonResponse
    {
        return self::error($message ?? 'Internal server error', 500, 'SERVER_ERROR');
    }

    /**
     * Return a paginated response.
     */
    public static function paginated($paginator, ?string $message = null): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
            'message' => $message,
        ], 200);
    }
}
