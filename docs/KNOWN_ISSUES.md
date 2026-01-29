# Known Issues & Bug Fixes

> **Last Updated:** 2026-01-27 (Phase 1 Complete)  
> **Purpose:** Document bugs found through code reviews and their resolutions

---

## Table of Contents

1. [Critical Issues - Fixed](#critical-issues---fixed)
2. [High Priority Issues - Pending](#high-priority-issues---pending)
3. [Medium Priority Issues - Pending](#medium-priority-issues---pending)
4. [Low Priority Issues - Pending](#low-priority-issues---pending)

---

## Critical Issues - Fixed

### ✅ Issue #1: Foreign Key Type Mismatch

**Discovered:** 2026-01-27 (Code Review)  
**Severity:** CRITICAL  
**Status:** ✅ FIXED (Phase 1)

**Problem:**
The `flow_run_results` table used `foreignId()` (BIGINT) for `flow_run_id`, but the `flow_runs` table has `uuid('id')` (string UUID). This type mismatch caused:
- Foreign key constraint errors when creating results
- Flow runs completely broken - couldn't create results
- System unusable for core functionality

**Impact:**
- Complete system failure for flow execution
- Cannot create flow run results
- Data integrity compromised

**Fix Applied:**
Created migration `2026_01_27_204732_fix_flow_run_results_foreign_key_type.php`:
```php
Schema::dropIfExists('flow_run_results');

Schema::create('flow_run_results', function (Blueprint $table) {
    $table->id();
    $table->unsignedInteger('answer')->nullable();
    $table->foreignId('card_id')->constrained()->onDelete('cascade');
    
    // Fix: Use UUID instead of foreignId (BIGINT)
    $table->uuid('flow_run_id');
    $table->foreign('flow_run_id')
          ->references('id')
          ->on('flow_runs')
          ->onDelete('cascade');
    
    $table->timestamps();
});
```

**Files Changed:**
- `database/migrations/2026_01_27_204732_fix_flow_run_results_foreign_key_type.php` (NEW)

**Migration Applied:** ✅ Yes

---

### ✅ Issue #2: Missing UUID & user_id in FlowRun Creation

**Discovered:** 2026-01-27 (Code Review)  
**Severity:** CRITICAL  
**Status:** ✅ FIXED (Phase 1)

**Problem:**
The `FlowRunController::create()` method didn't explicitly set `id` (UUID) or `user_id` when creating flow runs:
```php
// Before (broken):
$flowRun = $flow->runs()->create();
```

This caused:
- Flow runs created with null/invalid IDs
- Authorization bypass (user_id not set for authenticated users)
- Results couldn't be linked to flow runs
- Analytics couldn't distinguish authenticated from anonymous users

**Impact:**
- Security vulnerability (authorization bypass)
- Data corruption
- System instability

**Fix Applied:**
Updated `app/Http/Controllers/Api/FlowRunController.php`:
```php
// After (fixed):
$flowRun = $flow->runs()->create([
    'id' => \Illuminate\Support\Str::uuid(),
    'user_id' => auth()->id(),
    'started_at' => now(),
]);
```

**Files Changed:**
- `app/Http/Controllers/Api/FlowRunController.php`

---

### ✅ Issue #3: Mass Assignment Vulnerability - FlowRun Model

**Discovered:** 2026-01-27 (Code Review)  
**Severity:** CRITICAL  
**Status:** ✅ FIXED (Phase 1)

**Problem:**
The `FlowRun` model had conflicting mass assignment protection:
```php
// Insecure configuration:
protected $guarded = [];  // Allows ALL fields
protected $fillable = ['user_id', 'flow_id', 'score_calculated'];  // Ignored!
```

The `$guarded = []` takes precedence, allowing attackers to:
- Manipulate `total_score` to any value
- Forge `completed_at` to mark runs complete
- Hijack `result_template_id` to show wrong results
- Manipulate `share_token` and `session_token`

**Impact:**
- Complete security bypass
- Score manipulation
- Data integrity compromise
- Cheating possible

**Fix Applied:**
Updated `app/Models/FlowRun.php`:
```php
// Secure configuration:
protected $fillable = ['id', 'user_id', 'flow_id', 'started_at'];
// Removed: protected $guarded = [];
```

Now only safe fields can be mass-assigned. Sensitive fields like `total_score`, `completed_at`, `result_template_id` must be set explicitly.

**Files Changed:**
- `app/Models/FlowRun.php`

**Test Verification:**
- ✅ Mass assignment of `total_score` rejected
- ✅ Mass assignment of `completed_at` rejected  
- ✅ Mass assignment of `result_template_id` rejected
- ✅ Safe fields (`id`, `user_id`, `flow_id`) accepted

---

### ✅ Issue #4: Authorization Bypass in FlowRunController

**Discovered:** 2026-01-27  
**Severity:** CRITICAL  
**Status:** ✅ FIXED (Previous checkpoint)

**Discovered:** 2026-01-27  
**Severity:** CRITICAL  
**Status:** ✅ FIXED

**Problem:**
Methods `ensureOwnsFlow()` and `ensureOwnsFlowRun()` were called but didn't exist in `FlowRunController`. This created a complete authorization bypass allowing any authenticated user to:
- Create flow runs for other users' flows
- Start/stop any flow run regardless of ownership
- Submit answers to any flow run

**Impact:**
- Complete security bypass
- Data integrity compromise
- Privacy violation

**Root Cause:**
Missing authorization helper methods that were assumed to exist from parent class.

**Fix Applied:**
Added authorization methods to `app/Http/Controllers/Api/FlowRunController.php`:
```php
private function ensureOwnsFlow(Flow $flow): void
{
    if ($flow->user_id !== auth()->id()) {
        abort(403, 'Not authorized to access this flow.');
    }
}

private function ensureOwnsFlowRun(FlowRun $flowRun): void
{
    if ($flowRun->user_id !== auth()->id()) {
        abort(403, 'Not authorized to access this flow run.');
    }
}
```

**Files Changed:**
- `app/Http/Controllers/Api/FlowRunController.php`

**Commit:** [Reference commit hash when committed]

---

### ✅ Issue #2: Wrong Data Type for Answer Field

**Discovered:** 2026-01-27  
**Severity:** HIGH  
**Status:** ✅ FIXED

**Problem:**
The `answer` field in `FlowRunResult` model was cast to `'array'` but used throughout the codebase as an integer (0 or 1). This type mismatch caused:
- Scoring calculation failures
- Validation errors
- Incorrect data storage/retrieval

**Impact:**
- Flow runs could not complete correctly
- Scores calculated incorrectly
- Results stored in wrong format

**Evidence:**
- Model cast: `'answer' => 'array'`
- Validation rules: `'answer' => 'required|integer|in:0,1'`
- Usage: `$card->scoring[$answer]` expects integer keys

**Fix Applied:**
Changed cast in `app/Models/FlowRunResult.php`:
```php
public $casts = [
    'answer' => 'integer', // Changed from 'array'
];
```

**Files Changed:**
- `app/Models/FlowRunResult.php`

**Migration Required:** No (cast change only)

**Commit:** [Reference commit hash when committed]

---

## High Priority Issues - Fixed

### ✅ Issue #5: Mass Assignment Vulnerabilities - All Models

**Discovered:** 2026-01-27 (Code Review)  
**Severity:** HIGH  
**Status:** ✅ FIXED (Phase 2)

**Problem:**
All models used `protected $guarded = []` which allows unrestricted mass assignment of ALL fields. This created multiple security vulnerabilities:
- **Flow Model:** Attackers could set `user_id` to hijack flows, modify `public_slug`
- **Card Model:** Could inject malicious branching logic or scoring rules
- **FlowRunResult Model:** Could manipulate `answer` and `score` fields
- **ResultTemplate Model:** Could change score ranges to always match their template

**Impact:**
- Security vulnerabilities across entire system
- Privilege escalation possible
- Data manipulation attacks
- Score cheating enabled

**Fix Applied:**
Replaced `$guarded = []` with explicit `$fillable` arrays in all models:

```php
// Flow.php
protected $fillable = ['name', 'description', 'cards', 'layout', 'metadata', 'is_public', 'allow_anonymous'];

// Card.php
protected $fillable = ['question', 'description', 'image_url', 'skipable', 'options', 'branches', 'scoring'];

// FlowRunResult.php
protected $fillable = ['flow_run_id', 'card_id', 'answer'];

// ResultTemplate.php
protected $fillable = ['flow_id', 'title', 'content', 'image_url', 'min_score', 'max_score', 'cta_text', 'cta_url', 'order'];
```

**Files Changed:**
- `app/Models/Flow.php`
- `app/Models/Card.php`
- `app/Models/FlowRunResult.php`
- `app/Models/ResultTemplate.php`

**Security Impact:**
- ✅ Sensitive fields now protected from mass assignment
- ✅ Attack surface significantly reduced
- ✅ Follows Laravel security best practices

---

### ✅ Issue #6: Card Ordering Not Preserved

**Discovered:** 2026-01-27 (Code Review)  
**Severity:** HIGH  
**Status:** ✅ FIXED (Phase 2)

**Problem:**
The `Flow::cards()`, `FlowRun::cards()`, and `PublicFlowController::getNextCard()` methods used `whereIn()` which doesn't preserve order. Cards were returned in arbitrary database order, not the order specified in the flow's `cards` array.

This caused:
- Questions displayed in wrong order
- Branching logic breaking (indices don't match expected sequence)
- Inconsistent user experience
- "Next card in sequence" logic returning wrong card

**Impact:**
- Flow progression broken
- Branching logic fails
- Poor user experience
- Logic bugs in production

**Evidence:**
```php
// Before (broken):
return Card::whereIn('id', $this->cards)->get();
// Returns cards in database order, not flow order!
```

**Fix Applied:**
Implemented order-preserving retrieval using `keyBy()` and `map()`:

```php
// After (fixed):
public function cards()
{
    if (empty($this->cards)) {
        return collect([]);
    }
    
    $cards = Card::whereIn('id', $this->cards)->get()->keyBy('id');
    
    return collect($this->cards)->map(function($cardId) use ($cards) {
        return $cards->get($cardId);
    })->filter();
}
```

**Files Changed:**
- `app/Models/Flow.php` (cards() method)
- `app/Models/FlowRun.php` (cards() method)
- `app/Http/Controllers/PublicFlowController.php` (getNextCard() method)

**Test Verification:**
- ✅ Cards retrieved in exact order from `cards` array
- ✅ Branching logic now works correctly
- ✅ Sequential progression fixed

---

### ✅ Issue #7: Race Condition in Score Calculation

**Discovered:** 2026-01-27  
**Severity:** HIGH  
**Status:** ✅ FIXED (Previous checkpoint)

**Problem:**
Score calculation logic checked `$this->total_score === 0 && !$this->isDirty('total_score')` to determine if score was calculated. This treated a legitimate score of 0 as "not calculated", causing:
- Incorrect recalculation on every template assignment
- Wrong result template assigned
- Data inconsistency

**Impact:**
- Users with score of 0 get wrong results
- System recalculates unnecessarily
- Performance degradation

**Root Cause:**
Using score value as calculation indicator instead of dedicated flag.

**Fix Applied:**

1. Added `score_calculated` boolean field to track calculation state
2. Updated `FlowRun` model:

**In `app/Models/FlowRun.php`:**
```php
// Added to casts
'score_calculated' => 'boolean',

// Updated calculateScore()
public function calculateScore(): int
{
    // ... calculation logic ...
    $this->total_score = $score;
    $this->score_calculated = true; // NEW
    $this->save();
    return $score;
}

// Updated assignResultTemplate()
public function assignResultTemplate(): ?ResultTemplate
{
    if (!$this->score_calculated) { // Changed from score === 0 check
        $this->calculateScore();
    }
    // ... rest of method
}
```

3. Created migration:

**Files Changed:**
- `app/Models/FlowRun.php`
- `database/migrations/2026_01_27_144131_add_score_calculated_to_flow_runs_table.php`

**Migration Required:** YES
```bash
php artisan migrate
```

**Commit:** [Reference commit hash when committed]

---

### ✅ Issue #4: Orphaned Data on Card Deletion

**Discovered:** 2026-01-27  
**Severity:** HIGH  
**Status:** ✅ FIXED

**Problem:**
When updating a flow, the service deleted all old cards but didn't clean up related `flow_run_results` records. This caused:
- Orphaned records in database
- FlowRun results referencing non-existent cards
- Incorrect score calculations
- Data integrity violations

**Impact:**
- Database corruption over time
- Scoring failures
- Analytics inaccuracies

**Root Cause:**
No cascade delete or validation before card deletion.

**Fix Applied:**
Added validation in `app/Services/FlowService.php` to prevent updates when active runs exist:

```php
// Check for active flow runs before modifying structure
$existingRuns = \App\Models\FlowRun::where('flow_id', $flow->id)
    ->whereNull('completed_at')
    ->exists();

if ($existingRuns) {
    throw new \InvalidArgumentException(
        'Cannot modify flow structure while active flow runs exist. ' .
        'Please complete or archive existing runs before updating.'
    );
}
```

**Design Decision:**
Chose to prevent modification over cascade deletion to preserve data integrity and prevent unexpected behavior for in-progress runs.

**Files Changed:**
- `app/Services/FlowService.php`

**User Impact:**
- Users must complete or abandon active runs before editing flow structure
- Clear error message explains requirement
- Protects data integrity

**Commit:** [Reference commit hash when committed]

---

## Medium Priority Issues - Fixed

### ✅ Issue #8: N+1 Query in Analytics Service

**Discovered:** 2026-01-27 (Code Review)  
**Severity:** MEDIUM  
**Status:** ✅ FIXED (Phase 3)

**Problem:**
The analytics service attempted to use `with('resultTemplate:id,title')` AFTER calling `get()`, which doesn't work in Laravel. This caused an N+1 query problem where:
- First query: Get flow runs grouped by result_template_id
- N queries: One query per result template to fetch title

With many result templates, this caused significant performance degradation.

**Impact:**
- Slow analytics loading times
- High database load
- Poor performance with multiple result templates

**Evidence:**
```php
// Before (broken):
$resultTemplateDistribution = (clone $query)
    ->select('result_template_id', DB::raw('count(*) as count'))
    ->groupBy('result_template_id')
    ->with('resultTemplate:id,title')  // Doesn't work after get()!
    ->get()
```

**Fix Applied:**
Used proper JOIN query to eager load result template titles:

```php
// After (fixed):
$resultTemplateDistribution = (clone $query)
    ->join('result_templates', 'flow_runs.result_template_id', '=', 'result_templates.id')
    ->select(
        'flow_runs.result_template_id',
        'result_templates.title as template_title',
        DB::raw('count(*) as count')
    )
    ->groupBy('flow_runs.result_template_id', 'result_templates.title')
    ->get()
```

**Files Changed:**
- `app/Services/FlowAnalyticsService.php`

**Performance Impact:**
- ✅ Reduced from N+1 queries to 1 query
- ✅ Analytics load significantly faster
- ✅ Proper Laravel query building

---

### ✅ Issue #9: Incorrect Unique Visitors Count

**Discovered:** 2026-01-27 (Code Review)  
**Severity:** MEDIUM  
**Status:** ✅ FIXED (Phase 3)

**Problem:**
The unique visitors calculation only counted `session_token`, which excluded all authenticated users (they don't have session tokens). This caused:
- Inaccurate visitor metrics
- Authenticated users not counted
- Wrong business intelligence data

**Impact:**
- Analytics data incomplete
- Business decisions based on wrong metrics
- Underreporting of actual visitor count

**Evidence:**
```php
// Before (broken):
$uniqueVisitors = (clone $query)
    ->distinct('session_token')
    ->count('session_token');
// Only counts anonymous users!
```

**Fix Applied:**
Count both authenticated and anonymous users separately, then sum:

```php
// After (fixed):
$authenticatedVisitors = (clone $query)
    ->whereNotNull('user_id')
    ->distinct('user_id')
    ->count('user_id');

$anonymousVisitors = (clone $query)
    ->whereNull('user_id')
    ->whereNotNull('session_token')
    ->distinct('session_token')
    ->count('session_token');

$uniqueVisitors = $authenticatedVisitors + $anonymousVisitors;
```

**Files Changed:**
- `app/Services/FlowAnalyticsService.php`

**Data Quality Impact:**
- ✅ Both user types now counted
- ✅ Accurate visitor metrics
- ✅ Reliable business intelligence

---

### ✅ Issue #10: Missing Foreign Key on flows.user_id

**Discovered:** 2026-01-27 (Code Review)  
**Severity:** MEDIUM  
**Status:** ✅ FIXED (Phase 3)

**Problem:**
The `flows` table had `user_id` column but no foreign key constraint to `users` table. This allowed:
- Orphaned flows when users were deleted
- Data integrity violations
- Database inconsistencies

**Impact:**
- Orphaned data accumulation
- Cannot cascade delete user data
- Referential integrity compromised

**Fix Applied:**
Created migration to add foreign key constraint with cascade delete:

```php
public function up(): void
{
    Schema::table('flows', function (Blueprint $table) {
        $table->foreign('user_id')
              ->references('id')
              ->on('users')
              ->onDelete('cascade');
    });
}
```

**Files Changed:**
- `database/migrations/2026_01_27_211500_add_foreign_key_to_flows_user_id.php` (NEW)

**Migration Status:**
- ✅ Migration created
- ⚠️  **Action Required:** Run `php artisan migrate`

**Data Integrity Impact:**
- ✅ Orphaned flows prevented
- ✅ Cascade deletion enabled
- ✅ Referential integrity enforced

---

### ✅ Issue #11: Race Condition in Score Calculation

**Discovered:** 2026-01-27 (Code Review)  
**Severity:** MEDIUM  
**Status:** ✅ FIXED (Phase 3)

**Problem:**
The `calculateScore()` method had no concurrency control. If called simultaneously by multiple requests (e.g., user hits "stop" button multiple times), it could:
- Calculate score multiple times
- Use inconsistent data if answers being saved concurrently
- Overwrite correct score with wrong value
- Waste database resources

**Impact:**
- Potential score corruption
- Race conditions in production
- Wasted computation

**Evidence:**
```php
// Before (vulnerable):
public function calculateScore(): int
{
    $score = 0;
    $this->load('results');
    // No locking, no transaction
    foreach ($this->results as $result) { ... }
    $this->total_score = $score;
    $this->save();
    return $score;
}
```

**Fix Applied:**
Implemented pessimistic locking with database transaction:

```php
// After (protected):
public function calculateScore(): int
{
    return DB::transaction(function () {
        // Lock this row to prevent concurrent calculations
        $flowRun = self::where('id', $this->id)
            ->lockForUpdate()
            ->first();
        
        // If already calculated, return existing score
        if ($flowRun->score_calculated) {
            return $flowRun->total_score;
        }
        
        // Calculate and save within transaction
        $score = 0;
        $flowRun->load('results');
        foreach ($flowRun->results as $result) { ... }
        
        $flowRun->total_score = $score;
        $flowRun->score_calculated = true;
        $flowRun->save();
        
        return $score;
    });
}
```

**Files Changed:**
- `app/Models/FlowRun.php`

**Laravel Best Practices Applied:**
- ✅ `DB::transaction()` for atomicity
- ✅ `lockForUpdate()` for pessimistic locking
- ✅ Check `score_calculated` flag before recalculation
- ✅ Automatic rollback on exceptions
- ✅ Clean, documented code

**Concurrency Impact:**
- ✅ Race conditions eliminated
- ✅ Score calculated exactly once
- ✅ Thread-safe implementation
- ✅ Production-ready code quality

---

## Low Priority Issues - Pending

### ⚠️ Issue #5: Card Order Not Maintained in Public Flows

**Discovered:** 2026-01-27  
**Severity:** MEDIUM  
**Status:** ⚠️ PENDING

**Problem:**
In `PublicFlowController::getNextCard()`, cards are retrieved with `Card::whereIn('id', $run->flow->cards)->get()` which doesn't preserve order. The `flow->cards` array maintains order, but `whereIn` doesn't.

**Impact:**
- Cards may appear out of order
- Branching logic may not work correctly
- User experience degraded

**Location:**
- `app/Http/Controllers/PublicFlowController.php` line 184

**Suggested Fix:**
```php
private function getNextCard(FlowRun $run)
{
    $answeredCardIds = $run->results()->pluck('card_id')->toArray();
    
    // Maintain order from flow->cards array
    foreach ($run->flow->cards as $cardId) {
        if (!in_array($cardId, $answeredCardIds)) {
            return Card::find($cardId);
        }
    }
    
    return null;
}
```

**Reason Not Fixed Yet:** Requires testing of public flow functionality.

---

### ⚠️ Issue #6: Missing Branch Target Validation

**Discovered:** 2026-01-27  
**Severity:** MEDIUM  
**Status:** ⚠️ PENDING

**Problem:**
Branch target indices aren't validated to be within array bounds before use. Could cause undefined behavior if malicious data sent.

**Location:**
- `app/Services/FlowService.php` lines 62-68

**Suggested Fix:**
Add validation rule in FormRequest:
```php
'cards.*.branches.*' => [
    'nullable',
    'integer',
    function ($attribute, $value, $fail) {
        if ($value !== null) {
            $cardsCount = count(request()->input('cards', []));
            if ($value < 0 || $value >= $cardsCount) {
                $fail('Branch target index is out of bounds.');
            }
        }
    }
],
```

**Reason Not Fixed Yet:** Lower priority, requires FormRequest update and testing.

---

### ⚠️ Issue #7: N+1 Query Problem in Flow Cards

**Discovered:** 2026-01-27  
**Severity:** MEDIUM  
**Status:** ⚠️ PENDING

**Problem:**
`Flow->cards()` method executes a query every time it's called instead of using a proper relationship. Creates N+1 problems when multiple flows loaded.

**Location:**
- `app/Models/Flow.php` lines 49-52

**Impact:**
- Performance degradation
- Increased database load
- Slower page loads

**Suggested Fix:**
Convert to proper Eloquent relationship with eager loading support.

**Reason Not Fixed Yet:** Requires refactoring of relationship structure and testing across codebase.

---

## Low Priority Issues - Pending

### ℹ️ Issue #8: Information Disclosure via Error Messages

**Discovered:** 2026-01-27  
**Severity:** LOW  
**Status:** ℹ️ PENDING

**Problem:**
Error messages like `'Failed to create flow: ' . $e->getMessage()` could leak internal information to attackers.

**Location:**
- `app/Http/Controllers/Api/FlowController.php` lines 71, 117, 141, 173

**Suggested Fix:**
```php
} catch (\Exception $e) {
    Log::error('Flow creation failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'user_id' => auth()->id()
    ]);
    return $this->error('Failed to create flow. Please try again.', 500);
}
```

**Reason Not Fixed Yet:** Low security impact in current deployment, requires systematic review of all error handling.

---

### ℹ️ Issue #9: SQL Injection Pattern Risk

**Discovered:** 2026-01-27  
**Severity:** LOW  
**Status:** ℹ️ PENDING

**Problem:**
If `flow->cards` array ever populated from unsanitized user input, could create SQL injection vulnerability in `orderByRaw` usage.

**Current Status:** Not exploitable (cards array is controlled).

**Suggested Fix:**
Add setter validation in Flow model:
```php
public function setCardsAttribute($value)
{
    if (!is_array($value)) {
        throw new \InvalidArgumentException('Cards must be an array');
    }
    
    $sanitized = array_map(function($cardId) {
        if (!is_numeric($cardId) || $cardId < 0) {
            throw new \InvalidArgumentException('Invalid card ID');
        }
        return (int) $cardId;
    }, $value);
    
    $this->attributes['cards'] = json_encode($sanitized);
}
```

**Reason Not Fixed Yet:** Preventive measure, not currently exploitable.

---

### ℹ️ Issue #10: Missing CSRF Protection Verification

**Discovered:** 2026-01-27  
**Severity:** LOW  
**Status:** ℹ️ PENDING (Verification Needed)

**Problem:**
Need to verify that `web` middleware includes CSRF protection for API auth routes.

**Location:**
- `routes/api.php` lines 12-15

**Action Required:**
Verify in `app/Http/Kernel.php` or `bootstrap/app.php` that 'web' middleware includes `VerifyCsrfToken`.

**Reason Not Verified Yet:** Requires review of middleware configuration.

---

## How to Use This Document

### For Developers

1. **Before Starting Work:**
   - Review pending issues for context
   - Check if your changes affect any listed bugs

2. **When Finding New Bugs:**
   - Add to appropriate severity section
   - Include discovery date, location, impact
   - Mark as "PENDING" with status emoji

3. **When Fixing Bugs:**
   - Update status from PENDING to FIXED
   - Add fix details and file changes
   - Reference commit hash
   - Add migration requirements

### Issue States

- ✅ **FIXED** - Issue resolved and committed
- ⚠️ **PENDING** - Issue identified, fix pending
- ℹ️ **PENDING (Verification Needed)** - Requires further investigation
- 🔄 **IN PROGRESS** - Currently being worked on

### Severity Levels

- **CRITICAL** - Security bypass, data corruption, system down
- **HIGH** - Data integrity, significant functionality broken
- **MEDIUM** - Performance issues, UX degradation, edge cases
- **LOW** - Minor issues, code quality, preventive measures

---

## Related Documentation

- **[ENGINEERING_STANDARDS.md](./ENGINEERING_STANDARDS.md)** - Prevention guidelines
- **[API.md](./API.md)** - API security and validation rules
- **[README.md](../README.md)** - Project overview

---

## Review Schedule

This document should be reviewed:
- After each code review
- Before major releases
- Monthly for pending issues

**Next Review:** 2026-02-27
