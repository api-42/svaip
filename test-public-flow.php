<?php

use App\Models\User;
use App\Models\Flow;
use App\Models\Card;

$user = User::first();

$card1 = Card::create([
    'description' => 'Do you like public flows?',
    'option_0' => 'No',
    'option_1' => 'Yes',
    'user_id' => $user->id,
    'scoring' => ['0' => 0, '1' => 10],
]);

$card2 = Card::create([
    'description' => 'Would you use this feature?',
    'option_0' => 'Maybe',
    'option_1' => 'Definitely',
    'user_id' => $user->id,
    'scoring' => ['0' => 5, '1' => 15],
]);

$flow = Flow::create([
    'name' => 'Public Test Flow',
    'description' => 'A test flow to try public access',
    'user_id' => $user->id,
    'cards' => [$card1->id, $card2->id],
    'is_public' => true,
]);

echo "✅ Flow created!\n";
echo "   ID: {$flow->id}\n";
echo "   Slug: {$flow->public_slug}\n";
echo "   Public URL: {$flow->publicUrl()}\n";
echo "\n";
echo "🌐 Access at: http://localhost:8001/p/{$flow->public_slug}\n";
