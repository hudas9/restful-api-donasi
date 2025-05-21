<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckApiKey
{
    public function handle(Request $request, Closure $next)
    {
        $apiKey = $request->header('API_KEY');
        $validApiKey = env('API_KEY', 'apikey');

        if (!$apiKey || $apiKey !== $validApiKey) {
            return response()->json(['message' => 'Unauthorized. Invalid or missing API_KEY.'], 401);
        }

        return $next($request);
    }
}
