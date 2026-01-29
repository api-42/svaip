<?php

/**
 * Quick test to verify authorize() method is available
 * Run with: php test-authorize-fix.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\Api\FlowController;
use App\Services\FlowService;

echo "=== Testing authorize() Method Fix ===\n\n";

// Create a FlowController instance
$service = new FlowService();
$controller = new FlowController($service);

// Check if authorize method exists
if (method_exists($controller, 'authorize')) {
    echo "✓ PASS: authorize() method exists on FlowController\n";
} else {
    echo "✗ FAIL: authorize() method NOT found on FlowController\n";
}

// Check parent classes and traits
$reflection = new ReflectionClass($controller);
echo "\nController inheritance chain:\n";
$class = $reflection;
while ($parent = $class->getParentClass()) {
    echo "  - " . $parent->getName() . "\n";
    
    // Check for AuthorizesRequests trait
    $traits = $parent->getTraitNames();
    if (!empty($traits)) {
        foreach ($traits as $trait) {
            echo "    * Uses trait: " . $trait . "\n";
            if (str_contains($trait, 'AuthorizesRequests')) {
                echo "    ✓ Found AuthorizesRequests trait!\n";
            }
        }
    }
    
    $class = $parent;
}

echo "\n=== Test Complete ===\n";
echo "\nThe error should now be fixed. Try saving a flow again.\n";
