<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\Nesai\NesaiService;

$service = app(NesaiService::class);

echo "Testing prompt with live Gemini API..." . PHP_EOL;

$result = $service->respond('Halo, ada jurusan apa saja di SMKN 1 Subang?');

echo "=== RESULT ===" . PHP_EOL;
print_r($result);
