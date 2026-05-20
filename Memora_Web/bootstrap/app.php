<?php

use App\Http\Middleware\CheckAdminRole;
use App\Http\Middleware\CheckUserStatus;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        $middleware->alias([
            'check.status' => CheckUserStatus::class,
            'check.admin'  => CheckAdminRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        // Hanya tangani request API (Accept: application/json atau /api/*)
        $isApiRequest = fn ($request) => $request->expectsJson() || $request->is('api/*');

        // 401 — Token tidak ada atau tidak valid
        $exceptions->render(function (AuthenticationException $e, $request) use ($isApiRequest) {
            if ($isApiRequest($request)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Unauthenticated. Token tidak valid atau tidak ada.',
                ], 401);
            }
        });

        // 422 — Validasi gagal
        $exceptions->render(function (ValidationException $e, $request) use ($isApiRequest) {
            if ($isApiRequest($request)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Validasi gagal',
                    'errors'  => $e->errors(),
                ], 422);
            }
        });

        // 404 — Model tidak ditemukan (ModelNotFoundException)
        $exceptions->render(function (ModelNotFoundException $e, $request) use ($isApiRequest) {
            if ($isApiRequest($request)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Data tidak ditemukan',
                ], 404);
            }
        });

        // 403 — Akses ditolak (AuthorizationException)
        $exceptions->render(function (AuthorizationException $e, $request) use ($isApiRequest) {
            if ($isApiRequest($request)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Akses ditolak',
                ], 403);
            }
        });

        // 429 — Too many requests
        $exceptions->render(function (ThrottleRequestsException $e, $request) use ($isApiRequest) {
            if ($isApiRequest($request)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Terlalu banyak request. Coba lagi nanti.',
                ], 429);
            }
        });

    })->create();
