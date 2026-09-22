<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$key = env('GEMINI_API_KEY', env('LLM_API_KEY'));

echo "Key starts with: " . substr($key, 0, 8) . "..." . PHP_EOL;

$url = "https://generativelanguage.googleapis.com/v1beta/models?key=" . urlencode($key);

$client = new \GuzzleHttp\Client();
try {
    $res = $client->get($url);
    $data = json_decode($res->getBody(), true);
    echo "Connection successful! Models count: " . count($data['models'] ?? []) . PHP_EOL;
    foreach (array_slice($data['models'] ?? [], 0, 6) as $m) {
        echo "- " . $m['name'] . PHP_EOL;
    }
} catch (\Throwable $e) {
    echo "API Error: " . $e->getMessage() . PHP_EOL;
}
