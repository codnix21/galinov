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

        $host = self::resolveMailHost($host);

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

    /** SMTP на хосте VPS: из контейнера — IP шлюза Docker-сети, не host.docker.internal. */
    private static function resolveMailHost(string $host): string
    {
        if ($host !== 'host.docker.internal') {
            return $host;
        }

        $override = env('MAIL_DOCKER_GATEWAY');
        if (is_string($override) && $override !== '') {
            return $override;
        }

        $resolved = gethostbyname($host);
        if ($resolved !== $host) {
            return $resolved;
        }

        $gateway = self::defaultGatewayFromProc();
        if ($gateway !== null) {
            return $gateway;
        }

        return $host;
    }

    private static function defaultGatewayFromProc(): ?string
    {
        if (! is_readable('/proc/net/route')) {
            return null;
        }

        $lines = file('/proc/net/route', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return null;
        }

        array_shift($lines);

        foreach ($lines as $line) {
            $parts = preg_split('/\s+/', trim($line));
            if (($parts[1] ?? '') !== '00000000' || ! isset($parts[2])) {
                continue;
            }

            $hex = $parts[2];
            if (strlen($hex) !== 8) {
                continue;
            }

            $ip = long2ip(hexdec(implode('', array_reverse(str_split($hex, 2)))));

            return $ip !== '0.0.0.0' ? $ip : null;
        }

        return null;
    }
}
