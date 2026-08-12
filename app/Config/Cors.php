<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Cross-Origin Resource Sharing (CORS) Configuration
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
 */
class Cors extends BaseConfig
{
    /**
     * The default CORS configuration.
     *
     * @var array{
     *      allowedOrigins: list<string>,
     *      allowedOriginsPatterns: list<string>,
     *      supportsCredentials: bool,
     *      allowedHeaders: list<string>,
     *      exposedHeaders: list<string>,
     *      allowedMethods: list<string>,
     *      maxAge: int,
     *  }
     */
   public array $default = [
    'allowedOrigins' => [
        // Local development
        'http://localhost:5173',
        'http://localhost:3000',
        'http://localhost:5174',
        'http://localhost:8080',
        // Production — same-origin requests (Nginx proxies /api/ internally)
        // Add your domain/IP here if you have one, e.g.:
        // 'https://yourdomain.com',
        // 'http://123.456.789.0',
    ],

    // Allow all origins as fallback for same-server deployment
    // (request comes from same IP so no CORS issue, but CI4 still checks)
    'allowedOriginsPatterns' => ['#.*#'],

    'allowedHeaders' => ['*'],
    'allowedMethods' => ['*'],
    'supportsCredentials' => true,
    'exposedHeaders' => [
        'Content-Disposition',
        'Content-Type',
        'Content-Length',
    ],
    'maxAge' => 3600,
];
}
