<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StaffOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, ['admin', 'cs'])) {
            return response()->json(['message' => 'Staff access required.'], 403);
        }

        return $next($request);
    }
}
