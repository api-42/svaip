<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Flow;
use App\Models\Card;
use App\Models\FlowRunResult;
use App\Models\ResultTemplate;

echo "=== Phase 2 Bug Fix Testing ===" . PHP_EOL . PHP_EOL;

// Test Issue #4: Mass Assignment Protection - All Models
echo "--- Test 1: Mass Assignment Protection - Flow Model ---" . PHP_EOL;
try {
    $maliciousFlowData = [
        'name' => 'Test Flow',
        'description' => 'Test',
        'user_id' => 999,  // Should be rejected
        'public_slug' => 'hacked',  // Should be rejected
        'is_public' => true,
    ];
    
    $testFlow = new Flow($maliciousFlowData);
    
    if ($testFlow->user_id === 999) {
        echo "  ❌ FAIL: user_id was set (security vulnerability!)" . PHP_EOL;
    } else {
        echo "  ✅ PASS: user_id rejected (" . ($testFlow->user_id ?? 'null') . ")" . PHP_EOL;
    }
    
    if ($testFlow->public_slug === 'hacked') {
        echo "  ❌ FAIL: public_slug was set (security vulnerability!)" . PHP_EOL;
    } else {
        echo "  ✅ PASS: public_slug rejected (" . ($testFlow->public_slug ?? 'null') . ")" . PHP_EOL;
    }
    
    if ($testFlow->name === 'Test Flow' && $testFlow->is_public === true) {
        echo "  ✅ PASS: Safe fields accepted (name, is_public)" . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "  ❌ ERROR: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL . "--- Test 2: Mass Assignment Protection - Card Model ---" . PHP_EOL;
try {
    $maliciousCardData = [
        'question' => 'Test?',
        'options' => ['A', 'B'],
        'id' => 99999,  // Should be rejected (not in fillable)
    ];
    
    $testCard = new Card($maliciousCardData);
    
    if ($testCard->id === 99999) {
        echo "  ❌ FAIL: id was set (not expected in mass assignment)" . PHP_EOL;
    } else {
        echo "  ✅ PASS: id rejected (" . ($testCard->id ?? 'null') . ")" . PHP_EOL;
    }
    
    if ($testCard->question === 'Test?') {
        echo "  ✅ PASS: Safe fields accepted (question, options)" . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "  ❌ ERROR: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL . "--- Test 3: Mass Assignment Protection - FlowRunResult Model ---" . PHP_EOL;
try {
    $maliciousResultData = [
        'card_id' => 1,
        'flow_run_id' => '123e4567-e89b-12d3-a456-426614174000',
        'answer' => 1,
        'score' => 999,  // Should be rejected (not in fillable)
    ];
    
    $testResult = new FlowRunResult($maliciousResultData);
    
    if ($testResult->score === 999) {
        echo "  ❌ FAIL: score was set (security vulnerability!)" . PHP_EOL;
    } else {
        echo "  ✅ PASS: score rejected (" . ($testResult->score ?? 'null') . ")" . PHP_EOL;
    }
    
    if ($testResult->answer === 1) {
        echo "  ✅ PASS: Safe fields accepted (card_id, flow_run_id, answer)" . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "  ❌ ERROR: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL . "--- Test 4: Mass Assignment Protection - ResultTemplate Model ---" . PHP_EOL;
try {
    $maliciousTemplateData = [
        'title' => 'Test Result',
        'content' => 'Test content',
        'flow_id' => 999,  // Safe field
        'min_score' => -1000,  // Safe field but should be validated elsewhere
        'max_score' => 9999,  // Safe field but should be validated elsewhere
    ];
    
    $testTemplate = new ResultTemplate($maliciousTemplateData);
    
    // All fields should be accepted (they're in fillable)
    if ($testTemplate->title === 'Test Result' && $testTemplate->flow_id === 999) {
        echo "  ✅ PASS: Fillable fields accepted (title, content, flow_id, min/max_score)" . PHP_EOL;
        echo "  ℹ️  Note: Score range validation should be done in FormRequest, not mass assignment" . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "  ❌ ERROR: " . $e->getMessage() . PHP_EOL;
}

// Test Issue #5: Card Ordering
echo PHP_EOL . "--- Test 5: Card Ordering Preservation ---" . PHP_EOL;

// Get or create test cards
$card1 = Card::first();
if (!$card1) {
    echo "  ℹ️  Creating test cards..." . PHP_EOL;
    $card1 = Card::create(['question' => 'Q1', 'options' => ['A', 'B'], 'branches' => [null, null], 'scoring' => [1, 0]]);
    $card2 = Card::create(['question' => 'Q2', 'options' => ['A', 'B'], 'branches' => [null, null], 'scoring' => [1, 0]]);
    $card3 = Card::create(['question' => 'Q3', 'options' => ['A', 'B'], 'branches' => [null, null], 'scoring' => [1, 0]]);
    $card4 = Card::create(['question' => 'Q4', 'options' => ['A', 'B'], 'branches' => [null, null], 'scoring' => [1, 0]]);
    $card5 = Card::create(['question' => 'Q5', 'options' => ['A', 'B'], 'branches' => [null, null], 'scoring' => [1, 0]]);
} else {
    $card2 = Card::skip(1)->first() ?? $card1;
    $card3 = Card::skip(2)->first() ?? $card1;
    $card4 = Card::skip(3)->first() ?? $card1;
    $card5 = Card::skip(4)->first() ?? $card1;
}

$user = User::first();
if (!$user) {
    echo "  ⚠️  No user found - skipping card ordering test" . PHP_EOL;
} else {
    try {
        // Create a flow with cards in a specific, non-sequential order
        $intentionalOrder = [$card5->id, $card2->id, $card4->id, $card1->id, $card3->id];
        
        $testFlow = Flow::create([
            'user_id' => $user->id,
            'name' => 'Card Order Test Flow',
            'description' => 'Testing card ordering',
            'cards' => $intentionalOrder,
            'is_public' => false,
            'allow_anonymous' => false,
        ]);
        
        echo "  ℹ️  Created flow with card order: [" . implode(', ', $intentionalOrder) . "]" . PHP_EOL;
        
        // Retrieve cards using the cards() method
        $retrievedCards = $testFlow->cards();
        $retrievedOrder = $retrievedCards->pluck('id')->toArray();
        
        echo "  ℹ️  Retrieved card order: [" . implode(', ', $retrievedOrder) . "]" . PHP_EOL;
        
        // Check if order is preserved
        if ($retrievedOrder === $intentionalOrder) {
            echo "  ✅ PASS: Card order preserved correctly in Flow::cards()" . PHP_EOL;
        } else {
            echo "  ❌ FAIL: Card order NOT preserved!" . PHP_EOL;
            echo "     Expected: [" . implode(', ', $intentionalOrder) . "]" . PHP_EOL;
            echo "     Got:      [" . implode(', ', $retrievedOrder) . "]" . PHP_EOL;
        }
        
        // Test FlowRun::cards() as well
        $flowRun = $testFlow->runs()->create([
            'id' => \Illuminate\Support\Str::uuid(),
            'user_id' => $user->id,
            'started_at' => now(),
        ]);
        
        $runCards = $flowRun->cards();
        $runOrder = $runCards->pluck('id')->toArray();
        
        if ($runOrder === $intentionalOrder) {
            echo "  ✅ PASS: Card order preserved correctly in FlowRun::cards()" . PHP_EOL;
        } else {
            echo "  ❌ FAIL: Card order NOT preserved in FlowRun::cards()!" . PHP_EOL;
            echo "     Expected: [" . implode(', ', $intentionalOrder) . "]" . PHP_EOL;
            echo "     Got:      [" . implode(', ', $runOrder) . "]" . PHP_EOL;
        }
        
        // Clean up
        $flowRun->delete();
        $testFlow->delete();
        
    } catch (Exception $e) {
        echo "  ❌ ERROR: " . $e->getMessage() . PHP_EOL;
    }
}

echo PHP_EOL . "=== Phase 2 Testing Complete ===" . PHP_EOL;
