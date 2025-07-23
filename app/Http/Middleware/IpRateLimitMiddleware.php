<?php

namespace App\Http\Middleware;

use App\Traits\ApiResponseTrait;
use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Exceptions\ThrottleRequestsException;

class IpRateLimitMiddleware
{
    use ApiResponseTrait;

    protected $limiter;

    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    public function handle($request, Closure $next)
    {
        // Define the rate limit key based on the user's IP address
        $key = 'ip:' . $request->ip();

        // Define the maximum number of requests and the time period (in seconds)
        $maxRequests = 2; // Adjust as needed
        $decayMinutes = 60; // Adjust as needed

        // Check if the user has exceeded the rate limit
        if ($this->limiter->tooManyAttempts($key, $maxRequests, $decayMinutes)) {
//            throw new ThrottleRequestsException('Too many requests. Please try again later.');
            return $this->failedResponse('Too many requests. Please try again later.');
        }

        // Increment the rate limit counter for this IP address
        $this->limiter->hit($key, $decayMinutes);

        return $next($request);
    }
}
