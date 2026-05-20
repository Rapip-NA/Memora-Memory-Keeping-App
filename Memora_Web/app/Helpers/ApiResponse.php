<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    /**
     * Return a standardized success JSON response.
     *
     * @param  mixed       $data
     * @param  string      $message
     * @param  int         $code
     */
    public static function success(
        mixed $data = null,
        string $message = 'Berhasil',
        int $code = 200
    ): JsonResponse {
        $payload = [
            'status'  => 'success',
            'message' => $message,
        ];

        if (! is_null($data)) {
            $payload['data'] = $data;
        }

        return response()->json($payload, $code);
    }

    /**
     * Return a standardized error JSON response.
     *
     * @param  string      $message
     * @param  int         $code
     * @param  mixed       $errors
     */
    public static function error(
        string $message = 'Terjadi kesalahan',
        int $code = 400,
        mixed $errors = null
    ): JsonResponse {
        $payload = [
            'status'  => 'error',
            'message' => $message,
        ];

        if (! is_null($errors)) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $code);
    }
}
