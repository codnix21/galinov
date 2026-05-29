<?php

/**
 * Нагрузочное тестирование публичных страниц (параллельные запросы через curl_multi).
 * Запуск: php tools/load_test.php [base_url]
 * Переменные: LOAD_CONCURRENCY, LOAD_REQUESTS. Отчёт: tools/load_test_report.json
 */

$baseUrl = rtrim($argv[1] ?? 'http://127.0.0.1:8000', '/');

$scenarios = [
    'home' => ['GET', '/'],
    'properties_index' => ['GET', '/properties'],
    'properties_filtered' => ['GET', '/properties?type=apartment&operation=sale&min_price=1000000'],
    'login' => ['GET', '/login'],
    'about' => ['GET', '/about-contacts'],
    'help' => ['GET', '/help'],
    'mortgage' => ['GET', '/mortgage-calculator'],
];

$concurrency = (int) (getenv('LOAD_CONCURRENCY') ?: 10);
$requestsPerEndpoint = (int) (getenv('LOAD_REQUESTS') ?: 50);

echo "Load test\n";
echo "Base URL: {$baseUrl}\n";
echo "Concurrency: {$concurrency}, requests per endpoint: {$requestsPerEndpoint}\n\n";

// Health check
$health = singleRequest($baseUrl . '/', 'GET');
if ($health['http_code'] === 0) {
    fwrite(STDERR, "Server unreachable at {$baseUrl}. Start: php artisan serve\n");
    exit(1);
}

$allResults = [];

foreach ($scenarios as $name => [$method, $path]) {
    $url = $baseUrl . $path;
    $latencies = [];
    $errors = 0;
    $codes = [];

    $total = $requestsPerEndpoint;
    $offset = 0;

    while ($offset < $total) {
        $batch = min($concurrency, $total - $offset);
        $batchResults = runBatch($url, $method, $batch);
        foreach ($batchResults as $r) {
            if ($r['http_code'] >= 200 && $r['http_code'] < 400) {
                $latencies[] = $r['time_ms'];
            } else {
                $errors++;
            }
            $codes[$r['http_code']] = ($codes[$r['http_code']] ?? 0) + 1;
        }
        $offset += $batch;
    }

    $stats = latencyStats($latencies);
    $stats['endpoint'] = $name;
    $stats['path'] = $path;
    $stats['errors'] = $errors;
    $stats['http_codes'] = $codes;
    $stats['success_rate'] = $total > 0
        ? round(100 * ($total - $errors) / $total, 2)
        : 0;
    $allResults[] = $stats;

    printf(
        "[%s] %s — ok: %d/%d (%.1f%%), p50: %.1f ms, p95: %.1f ms, max: %.1f ms\n",
        $name,
        $path,
        $total - $errors,
        $total,
        $stats['success_rate'],
        $stats['p50'],
        $stats['p95'],
        $stats['max']
    );
}

echo "\n--- Summary ---\n";
$avgP95 = array_sum(array_column($allResults, 'p95')) / max(1, count($allResults));
$minSuccess = min(array_column($allResults, 'success_rate'));
echo sprintf("Average p95: %.1f ms\n", $avgP95);
echo sprintf("Min success rate: %.1f%%\n", $minSuccess);

$reportPath = __DIR__ . '/load_test_report.json';
file_put_contents($reportPath, json_encode([
    'timestamp' => date('c'),
    'base_url' => $baseUrl,
    'concurrency' => $concurrency,
    'requests_per_endpoint' => $requestsPerEndpoint,
    'results' => $allResults,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Report: {$reportPath}\n";

function singleRequest(string $url, string $method): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_NOBODY => false,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_FOLLOWLOCATION => true,
    ]);
    $start = hrtime(true);
    curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $timeMs = (hrtime(true) - $start) / 1e6;
    curl_close($ch);

    return ['http_code' => $httpCode, 'time_ms' => $timeMs];
}

function runBatch(string $url, string $method, int $count): array
{
    $mh = curl_multi_init();
    $handles = [];

    for ($i = 0; $i < $count; $i++) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        $handles[] = ['ch' => $ch, 'start' => hrtime(true)];
        curl_multi_add_handle($mh, $ch);
    }

    $running = null;
    do {
        curl_multi_exec($mh, $running);
        curl_multi_select($mh, 0.5);
    } while ($running > 0);

    $results = [];
    foreach ($handles as $h) {
        curl_multi_remove_handle($mh, $h['ch']);
        $httpCode = (int) curl_getinfo($h['ch'], CURLINFO_HTTP_CODE);
        $timeMs = (hrtime(true) - $h['start']) / 1e6;
        curl_close($h['ch']);
        $results[] = ['http_code' => $httpCode, 'time_ms' => $timeMs];
    }
    curl_multi_close($mh);

    return $results;
}

function latencyStats(array $latencies): array
{
    if ($latencies === []) {
        return [
            'count' => 0, 'min' => 0, 'max' => 0, 'avg' => 0,
            'p50' => 0, 'p95' => 0, 'p99' => 0,
        ];
    }
    sort($latencies);
    $n = count($latencies);

    return [
        'count' => $n,
        'min' => round($latencies[0], 2),
        'max' => round($latencies[$n - 1], 2),
        'avg' => round(array_sum($latencies) / $n, 2),
        'p50' => round(percentile($latencies, 50), 2),
        'p95' => round(percentile($latencies, 95), 2),
        'p99' => round(percentile($latencies, 99), 2),
    ];
}

function percentile(array $sorted, float $p): float
{
    $n = count($sorted);
    if ($n === 1) {
        return $sorted[0];
    }
    $rank = ($p / 100) * ($n - 1);
    $lo = (int) floor($rank);
    $hi = (int) ceil($rank);
    if ($lo === $hi) {
        return $sorted[$lo];
    }
    $w = $rank - $lo;

    return $sorted[$lo] * (1 - $w) + $sorted[$hi] * $w;
}
