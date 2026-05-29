<?php

/**
 * Создаёт или обновляет учётную запись администратора (admin@example.com).
 * Запуск: php tools/check_admin.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

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

echo "Admin user:\n";
echo "id=" . $user->id . "\n";
echo "email_polzovatela=" . $user->email_polzovatela . "\n";
echo "rol=" . $user->rol . "\n";
echo "password_set=hashed\n";
echo "password_plain={$password}\n";











