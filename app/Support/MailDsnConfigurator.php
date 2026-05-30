<?php

namespace App\Support;

/**
 * Собирает MAIL_URL для Symfony Mailer (verify_peer, STARTTLS на 587).
 */
final class MailDsnConfigurator
{
    public static function apply(): void
    {
        if (env('MAIL_URL')) {
            return;
        }

        $host = env('MAIL_HOST');
        if (! is_string($host) || $host === '') {
            return;
        }

        $user = rawurlencode((string) env('MAIL_USERNAME', ''));
        $pass = rawurlencode((string) env('MAIL_PASSWORD', ''));
        $port = (int) env('MAIL_PORT', 587);
        $verifyPeer = filter_var(env('MAIL_VERIFY_PEER', true), FILTER_VALIDATE_BOOL) ? '1' : '0';

        $encryption = strtolower((string) env('MAIL_ENCRYPTION', ''));
        if ($encryption === '') {
            $encryption = $port === 465 ? 'ssl' : 'tls';
        }

        $auth = ($user !== '' || $pass !== '') ? "{$user}:{$pass}@" : '';

        $query = ['verify_peer='.$verifyPeer];
        if (in_array($encryption, ['tls', 'starttls'], true)) {
            $query[] = 'encryption=tls';
        }

        config([
            'mail.mailers.smtp.url' => 'smtp://'.$auth.$host.':'.$port.'?'.implode('&', $query),
        ]);
    }
}
