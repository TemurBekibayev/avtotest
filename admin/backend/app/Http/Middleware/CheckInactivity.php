<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class CheckInactivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ($token = $request->bearerToken()) {
            $accessToken = PersonalAccessToken::findToken($token);
            
            // Check if the token exists and its last_used_at is older than 1 hour.
            if ($accessToken && $accessToken->last_used_at && $accessToken->last_used_at->lt(now()->subHours(1))) {
                $accessToken->delete();
                return response()->json(['message' => 'Sessiya vaqti tugadi. Iltimos qaytadan tizimga kiring.'], 401);
            }
        }

        return $next($request);
    }
}
