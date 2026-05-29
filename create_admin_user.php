<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

$app->make(Kernel::class)->bootstrap();

$email = 'admin@example.com';
$password = 'Admin123!';

$user = User::updateOrCreate(
    ['email_polzovatela' => $email],
    [
        'familia' => 'Admin',
        'imya' => 'User',
        'otchestvo' => null,
        'email_polzovatela' => $email,
        'parol' => Hash::make($password),
        'telefon' => null,
        'rol' => 'admin',
    ]
);

echo "Admin user ready:\n";
echo "Email: {$email}\n";
echo "Password: {$password}\n";

