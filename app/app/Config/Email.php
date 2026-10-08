<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Email extends BaseConfig
{
    public string $fromEmail     = 'noreply@namamarine.cloud';
    public string $fromName      = 'NAMA Marine';
    public string $recipients    = '';
    public string $userAgent     = 'CodeIgniter';
    public string $protocol      = 'smtp';
    public string $mailPath      = '/usr/sbin/sendmail';
    public string $SMTPHost      = 'smtp-relay.brevo.com';
    public string $SMTPUser      = '';
    public string $SMTPPass      = '';
    public int    $SMTPPort      = 587;
    public int    $SMTPTimeout   = 10;
    public bool   $SMTPKeepAlive = false;
    public string $SMTPCrypto   = 'tls';
    public bool   $wordWrap      = true;
    public int    $wrapChars     = 76;
    public string $mailType      = 'html';
    public string $charset       = 'UTF-8';
    public bool   $validate      = false;
    public int    $priority      = 3;
    public string $CRLF          = "\r\n";
    public string $newline       = "\r\n";
    public bool   $BCCBatchMode  = false;
    public int    $BCCBatchSize  = 200;
    public bool   $DSN           = false;

    public function __construct()
    {
        parent::__construct();
        if ($v = env('email.SMTPUser')) $this->SMTPUser = $v;
        if ($v = env('email.SMTPPass')) $this->SMTPPass = $v;
        if ($v = env('email.fromEmail')) $this->fromEmail = $v;
        if ($v = env('email.fromName')) $this->fromName = $v;
    }
