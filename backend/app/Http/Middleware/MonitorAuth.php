<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class MonitorAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthorized.'], 401);
            }
            return redirect()->route('login')->with('error', 'Iltimos, avval tizimga kiring.');
        }

        $user = Auth::user();
        if ($user->role !== 'monitor' && $user->role !== 'admin') {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Forbidden.'], 403);
            }
            Auth::logout();
            return redirect()->route('login')->with('error', 'Monitoring paneliga kirish ruxsatingiz yo\'q.');
        }

        return $next($request);
    }
}
