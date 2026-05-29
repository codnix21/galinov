<?php

/**
 * Простой последовательный замер времени ответа страниц (без параллели).
 * Запуск: php tools/load_test_sequential.php [base_url]
 */

$base = rtrim($argv[1] ?? 'http://127.0.0.1:8000', '/');
$paths = ['/', '/properties', '/login'];

foreach ($paths as $path) {
    $times = [];
    for ($i = 0; $i < 10; $i++) {
        $s = hrtime(true);
        $ctx = stream_context_create(['http' => ['timeout' => 60]]);
        file_get_contents($base . $path, false, $ctx);
        $times[] = (hrtime(true) - $s) / 1e6;
    }
    printf(
        "%s sequential (n=10): avg=%.1f ms min=%.1f max=%.1f\n",
        $path,
        array_sum($times) / count($times),
        min($times),
        max($times)
    );
}
