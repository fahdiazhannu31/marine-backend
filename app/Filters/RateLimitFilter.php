<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

/**
 * Rate Limiting Filter
 * 
 * Protects endpoints from brute force attacks by limiting requests per IP.
 * Default: 10 requests per minute per IP.
 * 
 * Usage in Routes.php:
 *   $routes->post('api/auth/login', 'AuthApiController::login', ['filter' => 'ratelimit']);
 */
class RateLimitFilter implements FilterInterface
{
    private int $maxAttempts = 10;      // Max requests per window
    private int $windowSeconds = 60;     // Time window in seconds
    
    public function before(RequestInterface $request, $arguments = null)
    {
        // Get client IP
        $ip = $request->getIPAddress();
        
        // Whitelist internal/office IPs (optional)
        $whitelistedIPs = [
            '127.0.0.1',
            '::1',
            // Add your office IP here
            // '103.xxx.xxx.xxx',
        ];
        
        if (in_array($ip, $whitelistedIPs)) {
            return null; // Skip rate limiting
        }
        
        // Get cache service
        $cache = \Config\Services::cache();
        $key = 'rate_limit:' . md5($ip . ':' . $request->getUri()->getPath());
        
        // Get current attempts
        $data = $cache->get($key);
        
        if ($data === null) {
            // First request - initialize counter
            $cache->save($key, ['count' => 1, 'expires_at' => time() + $this->windowSeconds], $this->windowSeconds);
            return null;
        }
        
        // Check if limit exceeded
        if ($data['count'] >= $this->maxAttempts) {
            $remainingTime = $data['expires_at'] - time();
            
            log_message('warning', "Rate limit exceeded for IP: {$ip} on " . $request->getUri()->getPath());
            
            return service('response')
                ->setStatusCode(429)
                ->setJSON([
                    'error' => 'Too many requests. Please try again later.',
                    'retry_after' => max(0, $remainingTime),
                ])
                ->setHeader('Retry-After', (string) max(1, $remainingTime));
        }
        
        // Increment counter
        $data['count']++;
        $cache->save($key, $data, $this->windowSeconds);
        
        return null;
    }
    
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No action needed after request
    }
}
