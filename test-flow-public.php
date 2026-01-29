<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$flow = App\Models\Flow::first();

if (!$flow) {
    echo "No flow found\n";
    exit(1);
}

echo "Flow ID: {$flow->id}\n";
echo "Flow Name: {$flow->name}\n";
echo "Is Public: " . ($flow->is_public ? 'Yes' : 'No') . "\n";
echo "Public Slug: " . ($flow->public_slug ?? 'NULL') . "\n";
echo "\nRoute URL would be: /p/{$flow->public_slug}\n";
