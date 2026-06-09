<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserStatus
{
    /**
     * Handle an incoming request.
     * Pastikan user yang sudah login statusnya 'active'.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->status !== 'active') {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Akun tidak aktif atau belum disetujui',
                ], 403);
            }

            if (!$request->is('pending') && !$request->is('logout') && !$request->is('logout/*')) {
                return redirect()->route('pending');
            }
        }

        return $next($request);
    }
}
