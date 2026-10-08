<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Email extends BaseConfig
{
    public string $fromEmail  = '';
    public string $fromName   = '';
    public string $recipients = '';

    /**
     * The "user agent"
     */
    public string $userAgent = 'CodeIgniter';

    /**
     * The mail sending protocol: mail, sendmail, smtp
     */
    public string $protocol = 'smtp';

    /**
     * The server path to Sendmail.
     */
    public string $mailPath = '/usr/sbin/sendmail';

    /**
     * SMTP Server Hostname - Brevo (Sendinblue)
     */
    public string $SMTPHost = 'smtp-relay.brevo.com';

    /**
     * SMTP Username - Brevo: gunakan email akun Brevo Anda
     * Set via .env: email.SMTPUser
     */
    public string $SMTPUser = '';

    /**
     * SMTP Password - Brevo: gunakan SMTP Key dari dashboard Brevo
     * Dashboard Brevo → SMTP & API → SMTP → Generate new SMTP key
     * Set via .env: email.SMTPPass
     */
    public string $SMTPPass = '';

    /**
     * SMTP Port - Brevo menggunakan port 587 (TLS) atau 465 (SSL)
     */
    public int $SMTPPort = 587;

    /**
     * SMTP Timeout (in seconds)
     */
    public int $SMTPTimeout = 10;

    /**
     * Enable persistent SMTP connections
     */
    public bool $SMTPKeepAlive = false;

    /**
     * SMTP Encryption - Brevo pakai TLS di port 587
     */
    public string $SMTPCrypto = 'tls';

    /**
     * Enable word-wrap
     */
    public bool $wordWrap = true;

    /**
     * Character count to wrap at
     */
    public int $wrapChars = 76;

    /**
     * Type of mail, either 'text' or 'html'
     */
    public string $mailType = 'html';

    /**
     * Character set (utf-8, iso-8859-1, etc.)
     */
    public string $charset = 'UTF-8';

    /**
     * Whether to validate the email address
     */
    public bool $validate = false;

    /**
     * Email Priority. 1 = highest. 5 = lowest. 3 = normal
     */
    public int $priority = 3;

    /**
     * Newline character. (Use “\r\n” to comply with RFC 822)
     */
    public string $CRLF = "\r\n";

    /**
     * Newline character. (Use “\r\n” to comply with RFC 822)
     */
    public string $newline = "\r\n";

    /**
     * Enable BCC Batch Mode.
     */
    public bool $BCCBatchMode = false;

    /**
     * Number of emails in each BCC batch
     */
    public int $BCCBatchSize = 200;

    /**
     * Enable notify message from server
     */
    public bool $DSN = false;
}
