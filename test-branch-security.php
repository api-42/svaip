<?php

/**
 * Manual test script for Issue #1: Branch Target Authorization Bypass
 * 
 * Run with: php test-branch-security.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\{Card, User, Flow};
use App\Services\FlowService;

echo "=== Testing Issue #1: Branch Target Authorization Bypass ===\n\n";

// Test 1: Card model validation
echo "Test 1: Card::validateBranches() with invalid target...\n";
$card1 = new Card(['id' => 1]);
$card1->branches = [null, 999]; // 999 is not in flow

$errors = $card1->validateBranches([1, 2, 3]);
if (!empty($errors)) {
    echo "✓ PASS: Invalid branch detected\n";
    echo "  Error: " . $errors[0] . "\n";
} else {
    echo "✗ FAIL: Should have detected invalid branch\n";
}
echo "\n";

// Test 2: Card model validation with valid branches
echo "Test 2: Card::validateBranches() with valid target...\n";
$card2 = new Card(['id' => 1]);
$card2->branches = [null, 2]; // 2 is in flow

$errors = $card2->validateBranches([1, 2, 3]);
if (empty($errors)) {
    echo "✓ PASS: Valid branch accepted\n";
} else {
    echo "✗ FAIL: Should have accepted valid branch\n";
    echo "  Errors: " . implode('; ', $errors) . "\n";
}
echo "\n";

// Test 3: FlowService validation
echo "Test 3: FlowService rejects invalid branches...\n";
try {
    $user = User::first();
    if (!$user) {
        echo "⚠ SKIP: No user found in database\n";
    } else {
        $service = new FlowService();
        
        // Create an external card
        $externalCard = Card::create([
            'question' => 'External card',
            'options' => ['Yes', 'No'],
        ]);
        
        // Try to create flow with invalid branch
        try {
            $flow = $service->createFlow($user, [
                'name' => 'Test Flow',
                'description' => 'Test',
                'cards' => [
                    [
                        'question' => 'Card 1',
                        'options' => ['Yes', 'No'],
                        'branches' => [null, $externalCard->id], // Invalid!
                    ],
                ],
            ]);
            
            echo "✗ FAIL: Should have thrown exception\n";
            
            // Clean up
            if ($flow) {
                $flow->delete();
            }
        } catch (\InvalidArgumentException $e) {
            echo "✓ PASS: FlowService rejected invalid branch\n";
            echo "  Exception: " . $e->getMessage() . "\n";
        }
        
        // Clean up external card
        $externalCard->delete();
    }
} catch (\Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 4: FlowService accepts valid branches
echo "Test 4: FlowService accepts valid branches...\n";
try {
    $user = User::first();
    if (!$user) {
        echo "⚠ SKIP: No user found in database\n";
    } else {
        $service = new FlowService();
        
        $flow = $service->createFlow($user, [
            'name' => 'Test Flow Valid',
            'description' => 'Test',
            'cards' => [
                [
                    'question' => 'Card 1',
                    'options' => ['Yes', 'No'],
                    'branches' => [0, 1], // Branch to card 0 or 1
                ],
                [
                    'question' => 'Card 2',
                    'options' => ['Yes', 'No'],
                ],
            ],
        ]);
        
        echo "✓ PASS: FlowService accepted valid branches\n";
        echo "  Flow ID: " . $flow->id . "\n";
        echo "  Cards: " . count($flow->cards) . "\n";
        
        // Clean up
        foreach ($flow->cards as $cardId) {
            Card::find($cardId)?->delete();
        }
        $flow->delete();
    }
} catch (\Exception $e) {
    echo "✗ FAIL: Should have accepted valid branches\n";
    echo "  Exception: " . $e->getMessage() . "\n";
}
echo "\n";

echo "=== Tests Complete ===\n";
