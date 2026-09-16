<?php

namespace App\Libraries;

/**
 * Login Attempt Tracker
 * 
 * Tracks failed login attempts and locks accounts temporarily after too many failures.
 * Uses cache to store attempt data (Redis/Memcached in production, file cache in dev).
 */
class LoginAttemptTracker
{
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_MINUTES = 15;
    private const ATTEMPT_WINDOW_MINUTES = 10;
    
    private $cache;
    
    public function __construct()
    {
        $this->cache = \Config\Services::cache();
    }
    
    /**
     * Check if an email is currently locked out
     * 
     * @param string $email
     * @return bool
     */
    public function isLocked(string $email): bool
    {
        $key = $this->getLockKey($email);
        $lockData = $this->cache->get($key);
        
        if ($lockData && isset($lockData['locked_until'])) {
            return time() < $lockData['locked_until'];
        }
        
        return false;
    }
    
    /**
     * Get remaining lockout time in seconds
     * 
     * @param string $email
     * @return int
     */
    public function getRemainingLockoutTime(string $email): int
    {
        $key = $this->getLockKey($email);
        $lockData = $this->cache->get($key);
        
        if ($lockData && isset($lockData['locked_until'])) {
            return max(0, $lockData['locked_until'] - time());
        }
        
        return 0;
    }
    
    /**
     * Record a failed login attempt
     * 
     * @param string $email
     * @param string $ip
     * @return array ['locked' => bool, 'remaining_attempts' => int, 'lockout_minutes' => int]
     */
    public function recordFailedAttempt(string $email, string $ip): array
    {
        $key = $this->getAttemptKey($email);
        $lockKey = $this->getLockKey($email);
        
        $attempts = $this->cache->get($key) ?? [
            'count' => 0,
            'first_attempt' => time(),
            'ips' => [],
        ];
        
        $attempts['count']++;
        $attempts['last_attempt'] = time();
        $attempts['ips'][] = $ip;
        
        // Check if max attempts exceeded
        if ($attempts['count'] >= self::MAX_ATTEMPTS) {
            $lockUntil = time() + (self::LOCKOUT_MINUTES * 60);
            
            $this->cache->save($lockKey, [
                'locked_until' => $lockUntil,
                'reason' => 'Too many failed login attempts',
                'attempts' => $attempts,
            ], self::LOCKOUT_MINUTES * 60);
            
            // Clear attempt counter
            $this->cache->delete($key);
            
            // Log security event
            log_message('warning', "Account locked: {$email} after {$attempts['count']} failed attempts from IPs: " . implode(', ', array_unique($attempts['ips'])));
            
            return [
                'locked' => true,
                'remaining_attempts' => 0,
                'lockout_minutes' => self::LOCKOUT_MINUTES,
            ];
        }
        
        // Save updated attempts
        $this->cache->save($key, $attempts, self::ATTEMPT_WINDOW_MINUTES * 60);
        
        return [
            'locked' => false,
            'remaining_attempts' => self::MAX_ATTEMPTS - $attempts['count'],
            'lockout_minutes' => 0,
        ];
    }
    
    /**
     * Clear failed attempts after successful login
     * 
     * @param string $email
     */
    public function clearAttempts(string $email): void
    {
        $this->cache->delete($this->getAttemptKey($email));
        $this->cache->delete($this->getLockKey($email));
    }
    
    /**
     * Get current attempt count
     * 
     * @param string $email
     * @return int
     */
    public function getAttemptCount(string $email): int
    {
        $key = $this->getAttemptKey($email);
        $attempts = $this->cache->get($key);
        
        return $attempts['count'] ?? 0;
    }
    
    private function getAttemptKey(string $email): string
    {
        return 'login_attempts:' . md5(strtolower($email));
    }
    
    private function getLockKey(string $email): string
    {
        return 'login_lock:' . md5(strtolower($email));
    }
}
