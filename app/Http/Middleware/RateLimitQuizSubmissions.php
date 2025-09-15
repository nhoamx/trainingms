<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RateLimitQuizSubmissions
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $maxAttempts = 3; // Maximum 3 submissions per hour
        $decayMinutes = 60; // Reset after 60 minutes
        
        // Create unique key based on IP and quiz ID
        $key = 'quiz_submissions:' . $request->ip() . ':' . $request->route('quiz')->id;
        
        // Get current submission count
        $attempts = Cache::get($key, 0);
        
        if ($attempts >= $maxAttempts) {
            Log::warning('Quiz submission rate limit exceeded', [
                'ip' => $request->ip(),
                'quiz_id' => $request->route('quiz')->id,
                'attempts' => $attempts,
                'user_agent' => $request->userAgent()
            ]);
            
            return response()->json([
                'message' => 'Demasiados intentos de envío. Por favor, espere antes de intentar nuevamente.',
                'error' => 'RATE_LIMIT_EXCEEDED'
            ], 429);
        }
        
        // Increment attempt counter
        Cache::put($key, $attempts + 1, now()->addMinutes($decayMinutes));
        
        Log::info('Quiz submission attempt recorded', [
            'ip' => $request->ip(),
            'quiz_id' => $request->route('quiz')->id,
            'attempts' => $attempts + 1
        ]);
        
        return $next($request);
    }
}
