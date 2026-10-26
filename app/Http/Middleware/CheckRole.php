<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // 1. Jika belum login, arahkan ke katalog/login
        if (!$user) {
            return redirect()->route('catalog.index');
        }

        // 2. Jika role user TIDAK ada dalam daftar role yang diizinkan untuk rute ini
        if (!in_array($user->role, $roles)) {
            // Redirect cerdas ke dashboard masing-masing role
            if ($user->role === 'admin') {
                return redirect()->route('admin.dashboard');
            }

            if ($user->role === 'penjual') {
                return redirect()->route('penjual.dashboard');
            }

            return redirect()->route('catalog.index');
        }

        return $next($request);
    }
}