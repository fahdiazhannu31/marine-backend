<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Email extends BaseConfig
{
    public string $fromEmail    = '';
    public string $fromName     = '';
    public string $recipients   = '';
    public string $userAgent    = 'CodeIgniter';
    public string $protocol     = 'smtp';
    public string $mailPath     = '/usr/sbin/sendmail';
    public string $SMTPHost     = 'smtp-relay.brevo.com';
    public string $SMTPUser     = '';
    public string $SMTPPass     = '';
    public int    $SMTPPort     = 587;
    public int    $SMTPTimeout  = 10;
    public bool   $SMTPKeepAlive = false;
    public string $SMTPCrypto   = 'tls';
    public bool   $wordWrap     = true;
    public int    $wrapChars    = 76;
    public string $mailType     = 'html';
    public string $charset      = 'UTF-8';
    public bool   $validate     = false;
    public int    $priority     = 3;
    public string $CRLF         = "\r\n";
    public string $newline      = "\r\n";
    public bool   $BCCBatchMode = false;
    public int    $BCCBatchSize = 200;
    public bool   $DSN          = false;

    public function __construct()
    {
        parent::__construct();

        // Explicitly load SMTP config from .env
        // CodeIgniter doesn't auto-map camelCase SMTP* properties from .env
        $host     = env('email.SMTPHost');
        $user     = env('email.SMTPUser');
        $pass     = env('email.SMTPPass');
        $port     = env('email.SMTPPort');
        $crypto   = env('email.SMTPCrypto');
        $from     = env('email.fromEmail');
        $fromName = env('email.fromName');

        if ($host)     $this->SMTPHost   = $host;
        if ($user)     $this->SMTPUser   = $user;
        if ($pass)     $this->SMTPPass   = $pass;
        if ($port)     $this->SMTPPort   = (int) $port;
        if ($crypto)   $this->SMTPCrypto = $crypto;
        if ($from)     $this->fromEmail  = $from;
        if ($fromName) $this->fromName   = $fromName;
    }
}
