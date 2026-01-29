<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Flow;
use App\Models\Card;
use App\Models\FlowRun;
use Illuminate\Support\Str;

echo "=== Phase 1 Bug Fix Testing ===" . PHP_EOL . PHP_EOL;

// Test Issue #3: Mass Assignment Protection
echo "--- Test 1: Mass Assignment Protection ---" . PHP_EOL;
try {
    $maliciousData = [
        'id' => Str::uuid(),
        'user_id' => 1,
        'flow_id' => 1,
        'started_at' => now(),
        'total_score' => 999,  // Should be rejected
        'completed_at' => now(),  // Should be rejected
        'result_template_id' => 999,  // Should be rejected
    ];
    
    $testRun = new FlowRun($maliciousData);
    
    $passed = 0;
    $failed = 0;
    
    if ($testRun->total_score === 999) {
        echo "  ❌ FAIL: total_score was set (security vulnerability!)" . PHP_EOL;
        $failed++;
    } else {
        echo "  ✅ PASS: total_score rejected (" . ($testRun->total_score ?? 'null') . ")" . PHP_EOL;
        $passed++;
    }
    
    if ($testRun->completed_at !== null) {
        echo "  ❌ FAIL: completed_at was set (security vulnerability!)" . PHP_EOL;
        $failed++;
    } else {
        echo "  ✅ PASS: completed_at rejected (null)" . PHP_EOL;
        $passed++;
    }
    
    if ($testRun->result_template_id === 999) {
        echo "  ❌ FAIL: result_template_id was set (security vulnerability!)" . PHP_EOL;
        $failed++;
    } else {
        echo "  ✅ PASS: result_template_id rejected (" . ($testRun->result_template_id ?? 'null') . ")" . PHP_EOL;
        $passed++;
    }
    
    // Verify safe fields ARE set
    if ($testRun->id && $testRun->user_id === 1 && $testRun->flow_id === 1) {
        echo "  ✅ PASS: Safe fields (id, user_id, flow_id) accepted" . PHP_EOL;
        $passed++;
    } else {
        echo "  ❌ FAIL: Safe fields not set properly" . PHP_EOL;
        $failed++;
    }
    
    echo "  Result: $passed passed, $failed failed" . PHP_EOL;
    
} catch (Exception $e) {
    echo "  ❌ ERROR: " . $e->getMessage() . PHP_EOL;
}

// Test Issue #1 & #2: Foreign Key Type and UUID Generation
echo PHP_EOL . "--- Test 2: Foreign Key Type & UUID Generation ---" . PHP_EOL;

// First, we need to check if we have test data
$user = User::first();
if (!$user) {
    echo "  ⚠️  No users found - skipping authenticated flow test" . PHP_EOL;
} else {
    $flow = Flow::where('user_id', $user->id)->first();
    
    if (!$flow) {
        echo "  ℹ️  Creating test flow..." . PHP_EOL;
        
        // Create a test card
        $card = Card::create([
            'question' => 'Test Question?',
            'description' => 'Test description',
            'skipable' => false,
            'options' => ['Yes', 'No'],
            'branches' => [null, null],
            'scoring' => [1, 0],
        ]);
        
        // Create a test flow
        $flow = Flow::create([
            'user_id' => $user->id,
            'name' => 'Test Flow for Phase 1',
            'description' => 'Testing foreign key fixes',
            'cards' => [$card->id],
            'is_public' => false,
            'allow_anonymous' => false,
        ]);
        
        echo "  ✅ Test flow created (ID: {$flow->id})" . PHP_EOL;
    }
    
    try {
        // Test creating a flow run (this tests Issue #2: UUID & user_id assignment)
        $flowRun = $flow->runs()->create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'started_at' => now(),
        ]);
        
        // Verify UUID is set correctly
        $isValidUuid = preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $flowRun->id);
        if ($isValidUuid) {
            echo "  ✅ PASS: Flow run created with UUID: {$flowRun->id}" . PHP_EOL;
        } else {
            echo "  ❌ FAIL: Flow run ID is not a valid UUID: {$flowRun->id}" . PHP_EOL;
        }
        
        // Verify user_id is set
        if ($flowRun->user_id === $user->id) {
            echo "  ✅ PASS: user_id set correctly: {$flowRun->user_id}" . PHP_EOL;
        } else {
            echo "  ❌ FAIL: user_id not set correctly. Expected {$user->id}, got " . ($flowRun->user_id ?? 'null') . PHP_EOL;
        }
        
        // Test creating results (this tests Issue #1: foreign key type)
        foreach ($flow->cards() as $card) {
            $result = $flowRun->results()->create([
                'card_id' => $card->id,
            ]);
            
            if ($result->exists) {
                echo "  ✅ PASS: Flow run result created successfully (ID: {$result->id})" . PHP_EOL;
                
                // Verify foreign key is UUID string
                $isValidUuid = preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $result->flow_run_id);
                if ($isValidUuid) {
                    echo "  ✅ PASS: flow_run_id is valid UUID: {$result->flow_run_id}" . PHP_EOL;
                } else {
                    echo "  ❌ FAIL: flow_run_id is not UUID: {$result->flow_run_id}" . PHP_EOL;
                }
            } else {
                echo "  ❌ FAIL: Could not create flow run result" . PHP_EOL;
            }
        }
        
        // Test cascade deletion (Issue #1 verification)
        echo PHP_EOL . "--- Test 3: Cascade Deletion ---" . PHP_EOL;
        $resultCount = $flowRun->results()->count();
        echo "  ℹ️  Flow run has {$resultCount} results" . PHP_EOL;
        
        $flowRun->delete();
        
        $remainingResults = \App\Models\FlowRunResult::where('flow_run_id', $flowRun->id)->count();
        
        if ($remainingResults === 0) {
            echo "  ✅ PASS: Cascade deletion works - all results deleted" . PHP_EOL;
        } else {
            echo "  ❌ FAIL: Cascade deletion failed - {$remainingResults} orphaned results remain" . PHP_EOL;
        }
        
    } catch (Exception $e) {
        echo "  ❌ ERROR: " . $e->getMessage() . PHP_EOL;
        echo "  Stack trace: " . $e->getTraceAsString() . PHP_EOL;
    }
}

echo PHP_EOL . "=== Phase 1 Testing Complete ===" . PHP_EOL;
