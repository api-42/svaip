<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Flow;
use App\Models\CardConnection;

$flow = Flow::orderBy('id', 'desc')->first();

if (!$flow) {
    echo "No flows found\n";
    exit;
}

echo "Flow ID: {$flow->id}\n";
echo "Flow name: {$flow->name}\n";
echo "Cards in flow: " . count($flow->cards) . "\n";
echo "Card IDs: " . json_encode($flow->cards) . "\n\n";

$connections = CardConnection::whereIn('source_card_id', $flow->cards)->get();
echo "Total connections: " . $connections->count() . "\n\n";

foreach ($connections as $conn) {
    echo "Connection #{$conn->id}: Card {$conn->source_card_id} -> Card {$conn->target_card_id} (option {$conn->source_option})\n";
}

echo "\n--- Connection details by card ---\n";
foreach ($flow->cards as $index => $cardId) {
    $outgoing = CardConnection::where('source_card_id', $cardId)->get();
    echo "Card #{$cardId} has " . $outgoing->count() . " outgoing connections\n";
    foreach ($outgoing as $conn) {
        echo "  → Card {$conn->target_card_id} (via option {$conn->source_option})\n";
    }
}
