<?php

/**
 * Test script to verify form response storage functionality
 * Run: php test-form-responses.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Flow;
use App\Models\FlowRun;
use App\Models\FlowRunFormResponse;
use Illuminate\Support\Str;

echo "=== Form Response Storage Test ===\n\n";

// Find a flow or create a test one
$flow = Flow::first();

if (!$flow) {
    echo "❌ No flows found. Please create a flow first.\n";
    exit(1);
}

echo "✅ Using flow: {$flow->name} (ID: {$flow->id})\n\n";

// Create a test flow run
$run = FlowRun::create([
    'id' => Str::uuid(),
    'flow_id' => $flow->id,
    'user_id' => $flow->user_id,
    'started_at' => now(),
]);

echo "✅ Created test flow run: {$run->id}\n\n";

// Simulate form data
$formData = [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'phone' => '+1234567890',
    'feedback' => 'This is a test feedback message',
];

echo "📝 Storing form responses:\n";
foreach ($formData as $fieldName => $fieldValue) {
    FlowRunFormResponse::create([
        'flow_run_id' => $run->id,
        'field_name' => $fieldName,
        'field_value' => $fieldValue,
    ]);
    echo "   ✓ {$fieldName}: {$fieldValue}\n";
}

echo "\n";

// Verify storage
$storedResponses = FlowRunFormResponse::where('flow_run_id', $run->id)->get();

echo "✅ Verification: Stored " . $storedResponses->count() . " form responses\n\n";

echo "📋 Retrieved data:\n";
foreach ($storedResponses as $response) {
    echo "   {$response->field_name}: {$response->field_value}\n";
}

echo "\n";

// Test relationship
$run->load('formResponses');
echo "✅ Relationship test: Run has " . $run->formResponses->count() . " form responses\n\n";

// Cleanup
echo "🧹 Cleaning up test data...\n";
$run->delete(); // Should cascade delete form responses

$remainingResponses = FlowRunFormResponse::where('flow_run_id', $run->id)->count();
echo "✅ Cascade delete test: " . ($remainingResponses === 0 ? "PASSED" : "FAILED") . "\n";

if ($remainingResponses > 0) {
    echo "   ⚠️  Found {$remainingResponses} orphaned responses\n";
    FlowRunFormResponse::where('flow_run_id', $run->id)->delete();
}

echo "\n=== Test Complete ===\n";
