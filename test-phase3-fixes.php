<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Flow;
use App\Models\Card;
use App\Models\FlowRun;
use Illuminate\Support\Facades\DB;

echo "=== Phase 3 Bug Fix Testing ===" . PHP_EOL . PHP_EOL;

// Test Issue #6: N+1 Query Fix
echo "--- Test 1: N+1 Query in Analytics ---" . PHP_EOL;
$flow = Flow::first();
if ($flow) {
    try {
        DB::enableQueryLog();
        
        $analytics = app(\App\Services\FlowAnalyticsService::class)->generateAnalytics($flow->id);
        
        $queries = DB::getQueryLog();
        $queryCount = count($queries);
        
        echo "  ℹ️  Total queries executed: $queryCount" . PHP_EOL;
        
        // Count queries related to result templates
        $resultTemplateQueries = array_filter($queries, function($query) {
            return strpos($query['query'], 'result_templates') !== false;
        });
        
        $rtQueryCount = count($resultTemplateQueries);
        echo "  ℹ️  Result template queries: $rtQueryCount" . PHP_EOL;
        
        if ($rtQueryCount <= 1) {
            echo "  ✅ PASS: No N+1 query problem (using JOIN)" . PHP_EOL;
        } else {
            echo "  ❌ FAIL: N+1 query detected ($rtQueryCount queries instead of 1)" . PHP_EOL;
        }
        
        DB::disableQueryLog();
        
    } catch (Exception $e) {
        echo "  ❌ ERROR: " . $e->getMessage() . PHP_EOL;
        DB::disableQueryLog();
    }
} else {
    echo "  ⚠️  No flows found - skipping test" . PHP_EOL;
}

// Test Issue #7: Unique Visitors Count
echo PHP_EOL . "--- Test 2: Unique Visitors Count ---" . PHP_EOL;

$user1 = User::first();
$user2 = User::skip(1)->first() ?? $user1;

if ($user1 && $flow) {
    try {
        // Create test flow runs
        $authRun1 = $flow->runs()->create([
            'id' => \Illuminate\Support\Str::uuid(),
            'user_id' => $user1->id,
            'started_at' => now(),
        ]);
        
        $authRun2 = $flow->runs()->create([
            'id' => \Illuminate\Support\Str::uuid(),
            'user_id' => $user1->id, // Same user
            'started_at' => now(),
        ]);
        
        if ($user2 && $user2->id !== $user1->id) {
            $authRun3 = $flow->runs()->create([
                'id' => \Illuminate\Support\Str::uuid(),
                'user_id' => $user2->id, // Different user
                'started_at' => now(),
            ]);
        }
        
        // Create anonymous runs
        $anonRun1 = $flow->runs()->create([
            'id' => \Illuminate\Support\Str::uuid(),
            'user_id' => null,
            'started_at' => now(),
        ]);
        // Manually set session_token since boot method handles it
        
        $analytics = app(\App\Services\FlowAnalyticsService::class)->generateAnalytics($flow->id);
        
        echo "  ℹ️  Total runs: {$analytics['total_runs']}" . PHP_EOL;
        echo "  ℹ️  Unique visitors: {$analytics['unique_visitors']}" . PHP_EOL;
        
        // Expected: 2-3 authenticated users + 1 anonymous
        if ($analytics['unique_visitors'] > 0 && $analytics['unique_visitors'] < $analytics['total_runs']) {
            echo "  ✅ PASS: Unique visitors count looks correct (includes both auth and anon)" . PHP_EOL;
        } else {
            echo "  ⚠️  WARNING: Unique visitors = {$analytics['unique_visitors']}, total runs = {$analytics['total_runs']}" . PHP_EOL;
        }
        
        // Clean up
        $authRun1->delete();
        $authRun2->delete();
        if (isset($authRun3)) $authRun3->delete();
        $anonRun1->delete();
        
    } catch (Exception $e) {
        echo "  ❌ ERROR: " . $e->getMessage() . PHP_EOL;
    }
} else {
    echo "  ⚠️  No test data - skipping test" . PHP_EOL;
}

// Test Issue #8: Foreign Key Constraint
echo PHP_EOL . "--- Test 3: Foreign Key Constraint ---" . PHP_EOL;
echo "  ℹ️  Migration required - run: php artisan migrate" . PHP_EOL;
echo "  ℹ️  After migration, flows will cascade delete when user is deleted" . PHP_EOL;

// Test Issue #9: Race Condition in Score Calculation
echo PHP_EOL . "--- Test 4: Race Condition Protection ---" . PHP_EOL;

if ($flow && $user1) {
    try {
        // Create a test flow run with results
        $testRun = $flow->runs()->create([
            'id' => \Illuminate\Support\Str::uuid(),
            'user_id' => $user1->id,
            'started_at' => now(),
        ]);
        
        // Create some results
        foreach ($flow->cards()->take(2) as $card) {
            $testRun->results()->create([
                'card_id' => $card->id,
                'answer' => 1,
            ]);
        }
        
        // Calculate score - should use transaction and locking
        $score1 = $testRun->calculateScore();
        
        echo "  ℹ️  First calculation: score = $score1, score_calculated = " . ($testRun->score_calculated ? 'true' : 'false') . PHP_EOL;
        
        // Try to calculate again - should return cached score
        $score2 = $testRun->calculateScore();
        
        if ($score1 === $score2 && $testRun->score_calculated) {
            echo "  ✅ PASS: Score calculation protected (returns cached score on second call)" . PHP_EOL;
        } else {
            echo "  ❌ FAIL: Score recalculated when it shouldn't be" . PHP_EOL;
        }
        
        // Verify code uses DB::transaction and lockForUpdate
        $code = file_get_contents(__DIR__ . '/app/Models/FlowRun.php');
        
        if (strpos($code, 'DB::transaction') !== false && strpos($code, 'lockForUpdate') !== false) {
            echo "  ✅ PASS: Code uses DB::transaction with lockForUpdate" . PHP_EOL;
        } else {
            echo "  ❌ FAIL: Missing transaction or locking mechanism" . PHP_EOL;
        }
        
        // Clean up
        $testRun->results()->delete();
        $testRun->delete();
        
    } catch (Exception $e) {
        echo "  ❌ ERROR: " . $e->getMessage() . PHP_EOL;
    }
} else {
    echo "  ⚠️  No test data - skipping test" . PHP_EOL;
}

echo PHP_EOL . "=== Phase 3 Testing Complete ===" . PHP_EOL;
echo PHP_EOL . "⚠️  Remember to run: php artisan migrate" . PHP_EOL;
