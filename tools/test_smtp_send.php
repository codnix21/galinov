<?php

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Mail\WeeklyReportMail;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Transport\Smtp\Stream\SocketStream;

$host = env('MAIL_HOST', 'mail.irk138.ru');
$user = env('MAIL_USERNAME');
$pass = env('MAIL_PASSWORD');
$to = $argv[1] ?? $user;

$configs = [
    ['port' => 587, 'tls' => false, 'label' => '587 STARTTLS'],
    ['port' => 465, 'tls' => true, 'label' => '465 SSL'],
    ['port' => 25, 'tls' => false, 'label' => '25 plain'],
];

foreach ($configs as $cfg) {
    echo "\n=== {$cfg['label']} ===\n";
    try {
        $transport = new EsmtpTransport($host, $cfg['port'], $cfg['tls']);
        $transport->setUsername($user);
        $transport->setPassword($pass);

        /** @var SocketStream $stream */
        $stream = $transport->getStream();
        $opts = $stream->getStreamOptions();
        $opts['ssl']['verify_peer'] = false;
        $opts['ssl']['verify_peer_name'] = false;
        $opts['ssl']['allow_self_signed'] = true;
        $stream->setStreamOptions($opts);

        $transport->start();
        echo "SMTP handshake OK\n";
        $transport->stop();
    } catch (Throwable $e) {
        echo 'FAIL: '.$e->getMessage()."\n";
    }
}

echo "\n=== Laravel Mail::send (current .env) ===\n";
try {
    Mail::to($to)->send(new WeeklyReportMail([
        'properties_total' => 0,
        'properties_active' => 0,
        'properties_sold' => 0,
        'contracts_period' => 0,
        'contracts_active' => 0,
        'inquiries_total' => 0,
        'inquiries_processed' => 0,
        'users_total' => 0,
    ], 'SMTP probe '.date('H:i:s')));
    echo "Mail::send OK to {$to}\n";
} catch (Throwable $e) {
    echo 'FAIL: '.$e->getMessage()."\n";
}
