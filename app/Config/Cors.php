<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Cors extends BaseConfig
{
    public array $default = [
        'allowedOrigins' => [
            'http://localhost:5173',
            'http://localhost:3000',
            'http://localhost:5174',
            'https://namamarine.cloud',
            'http://namamarine.cloud',
            'https://www.namamarine.cloud',
        ],

        'allowedOriginsPatterns' => [],

        'allowedHeaders' => [
            'Content-Type',
            'Authorization',
            'X-Requested-With',
            'Accept',
            'Origin',
        ],

        'allowedMethods' => [
            'GET',
            'POST',
            'PUT',
            'PATCH',
            'DELETE',
            'OPTIONS',
        ],

        'supportsCredentials' => true,

        'exposedHeaders' => [
            'Content-Disposition',
            'Content-Type',
            'Content-Length',
        ],

        'maxAge' => 3600,
    ];
}