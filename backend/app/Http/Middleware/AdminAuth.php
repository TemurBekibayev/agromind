<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Iltimos, avval tizimga kiring.');
        }

        $user = Auth::user();
        if ($user->role !== 'admin' && $user->role !== 'monitor') {
            Auth::logout();
            return redirect()->route('login')->with('error', 'Admin panelga kirish ruxsatingiz yo\'q.');
        }

        return $next($request);
    }
}
