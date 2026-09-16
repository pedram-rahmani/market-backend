<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CoAdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check() || (!Auth::user()->User::isAdmin() && !Auth::user()->User::isCoAdmin())) {
            return response()->json(['message' => 'Access denied. Admins or CoAdmins only.'], 403);
        }

        return $next($request);
    }
}
