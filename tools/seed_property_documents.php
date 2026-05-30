<?php

/**
 * Демо: проверенные документы для всех объявлений (чтобы модерация проходила).
 * php tools/seed_property_documents.php
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Property;
use App\Models\UserDocument;
use App\Support\PropertyDocumentRules;
use Illuminate\Support\Facades\Storage;

Storage::disk('public')->makeDirectory('documents/demo');
$stub = 'documents/demo/verified-stub.txt';
if (!Storage::disk('public')->exists($stub)) {
    Storage::disk('public')->put($stub, 'Demo verified document placeholder');
}

$created = 0;
foreach (Property::query()->cursor() as $property) {
    if (PropertyDocumentRules::isReadyForPublication($property)) {
        continue;
    }
    $required = PropertyDocumentRules::requiredForType(
        $property->tip ?? 'apartment',
        $property->operatsiya ?? 'sale',
    );
    foreach ($required as $tip) {
        $exists = UserDocument::where('nedvizhimost_id', $property->id)
            ->where('tip', $tip)
            ->whereStatusKod('verified')
            ->exists();
        if ($exists) {
            continue;
        }
        UserDocument::create([
            'polzovatel_id' => $property->polzovatel_id,
            'nedvizhimost_id' => $property->id,
            'tip' => $tip,
            'tip_obekta' => $property->tip,
            'nazvanie' => PropertyDocumentRules::allTipLabels()[$tip] ?? $tip,
            'put_fajla' => $stub,
            'status' => 'verified',
            'vneshniy_id' => 'RR-DEMO-' . $property->id,
            'vneshniy_status' => 'verified',
            'vneshniy_provereno_at' => now(),
            'provereno_at' => now(),
            'kommentariy_mod' => '[ЕГРН] Автопроверка для тестовой БД',
        ]);
        $created++;
    }
}

echo "Создано записей документов: {$created}" . PHP_EOL;
