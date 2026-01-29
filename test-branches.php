<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$flow = App\Models\Flow::latest()->first();
if ($flow) {
    echo 'Flow ID: ' . $flow->id . PHP_EOL;
    echo 'Layout: ' . json_encode($flow->layout, JSON_PRETTY_PRINT) . PHP_EOL;
    echo 'Cards IDs: ' . json_encode($flow->cards) . PHP_EOL;
    
    $cards = App\Models\Card::whereIn('id', $flow->cards)->get();
    foreach ($cards as $card) {
        echo PHP_EOL . 'Card ID ' . $card->id . ':' . PHP_EOL;
        echo '  Question: ' . $card->question . PHP_EOL;
        echo '  Branches: ' . json_encode($card->branches) . PHP_EOL;
    }
}
