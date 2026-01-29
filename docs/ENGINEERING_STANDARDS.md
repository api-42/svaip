# SVAIP Engineering Standards

> **Purpose**: This document defines the technical standards, design patterns, and decision-making framework for the SVAIP project. Follow these guidelines to maintain consistency and quality.

---

## Table of Contents
1. [Laravel Best Practices](#laravel-best-practices)
2. [Architecture Principles](#architecture-principles)
3. [UI/UX Guidelines](#uiux-guidelines)
4. [Code Standards](#code-standards)
5. [API Design](#api-design)
6. [Testing Requirements](#testing-requirements)
7. [Decision Framework](#decision-framework)

---

## Laravel Best Practices

> **Core Principle**: Follow "The Laravel Way" - use Laravel's built-in features and conventions rather than inventing custom solutions.

### Eloquent ORM

**✅ DO:**
- Use Eloquent relationships (`hasMany`, `belongsTo`, `belongsToMany`)
- Use route model binding in controllers
- Use mass assignment protection (`$fillable` or `$guarded`)
- Use Eloquent casts for JSON columns
- Use query scopes for reusable queries
- Use soft deletes when appropriate

**❌ DON'T:**
- Create custom methods that mimic relationships
- Query the database with raw SQL unless absolutely necessary
- Build JSON manually - use casts

**Example:**
```php
// ✅ GOOD - Eloquent relationship
class Flow extends Model {
    public function cards() {
        return $this->hasMany(Card::class);
    }
}

// ❌ BAD - Custom method
class Flow extends Model {
    public function cards() {
        return json_decode($this->cards_json, true);
    }
}
```

### Controllers

**✅ DO:**
- Use resource controllers (`php artisan make:controller --resource`)
- Use route model binding (automatic injection)
- Use dependency injection in constructors
- Keep methods under 50 lines
- Return consistent response formats

**❌ DON'T:**
- Create non-RESTful method names
- Use `Request::all()` without validation
- Put business logic in controllers

**Example:**
```php
// ✅ GOOD - Route model binding + thin controller
public function update(UpdateFlowRequest $request, Flow $flow) {
    $this->authorize('update', $flow);
    $flow = $this->flowService->updateFlow($flow, $request->validated());
    return response()->json(['success' => true, 'data' => $flow]);
}

// ❌ BAD - Manual lookup + fat controller
public function update(Request $request, $id) {
    $flow = Flow::find($id);
    if (!$flow || $flow->user_id !== auth()->id()) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }
    // ... 50 more lines of business logic
}
```

### Validation

**✅ DO:**
- Use FormRequest classes for validation
- Use Laravel's built-in validation rules
- Return JSON validation errors automatically
- Use custom validation rules in `app/Rules/` for complex logic

**❌ DON'T:**
- Validate in controllers with `$request->validate()`
- Create custom validation from scratch
- Return non-standard error formats

**Example:**
```php
// ✅ GOOD - FormRequest
class UpdateFlowRequest extends FormRequest {
    public function rules() {
        return [
            'title' => 'required|string|max:255',
            'cards' => 'required|array|min:1',
            'cards.*.question' => 'required|string|min:1',
        ];
    }
}

// ❌ BAD - Controller validation
public function update(Request $request, $id) {
    if (!$request->title || strlen($request->title) > 255) {
        return response()->json(['error' => 'Invalid title'], 422);
    }
    // ...
}
```

### Authorization

**✅ DO:**
- Use policies for all authorization logic
- Use `$this->authorize()` in controllers
- Let Laravel auto-discover policies (Laravel 11)
- Use policy methods that match controller actions

**❌ DON'T:**
- Check authorization inline (`if ($flow->user_id === auth()->id())`)
- Mix authorization with business logic
- Return custom 403 responses - let Laravel handle it

**Example:**
```php
// ✅ GOOD - Policy
class FlowPolicy {
    public function update(User $user, Flow $flow) {
        return $user->id === $flow->user_id;
    }
}

// In controller
$this->authorize('update', $flow);

// ❌ BAD - Inline check
if ($flow->user_id !== auth()->id()) {
    abort(403);
}
```

### Database Transactions

**✅ DO:**
- Use `DB::transaction()` for multi-step operations
- Let Laravel handle rollback on exceptions
- Use try-catch for custom error handling

**❌ DON'T:**
- Use manual `DB::beginTransaction()` + `DB::commit()` unless needed
- Forget to rollback on errors

**Example:**
```php
// ✅ GOOD - Automatic rollback
return DB::transaction(function () use ($data) {
    $flow = Flow::create($data['flow']);
    $flow->cards()->createMany($data['cards']);
    return $flow;
});

// ❌ BAD - Manual transaction management
DB::beginTransaction();
try {
    $flow = Flow::create($data);
    DB::commit();
} catch (Exception $e) {
    DB::rollBack();
    throw $e;
}
```

### Routing

**✅ DO:**
- Use resource routes (`Route::resource()`)
- Use route groups for middleware
- Use route model binding
- Use API versioning (`/api/v1`)
- Use named routes

**❌ DON'T:**
- Define individual routes when resource routes work
- Hardcode URLs in code - use `route()` helper

**Example:**
```php
// ✅ GOOD - Resource routes
Route::middleware(['web', 'auth'])->prefix('v1')->group(function () {
    Route::apiResource('flows', FlowController::class);
});

// ❌ BAD - Manual routes
Route::get('/api/v1/flows', [FlowController::class, 'index']);
Route::post('/api/v1/flows', [FlowController::class, 'store']);
// ... etc
```

### Service Container

**✅ DO:**
- Use dependency injection in constructors
- Bind services in `AppServiceProvider` if needed
- Use Laravel's service container, don't use `new`

**❌ DON'T:**
- Instantiate services with `new` keyword in controllers
- Create singletons unless necessary

**Example:**
```php
// ✅ GOOD - Dependency injection
class FlowController extends Controller {
    public function __construct(
        protected FlowService $flowService
    ) {}
}

// ❌ BAD - Manual instantiation
class FlowController extends Controller {
    public function store(Request $request) {
        $service = new FlowService();
        // ...
    }
}
```

### Jobs & Queues

**✅ DO:**
- Use jobs for time-consuming tasks
- Use `php artisan make:job`
- Dispatch with `YourJob::dispatch()`
- Use queue workers in production

**❌ DON'T:**
- Process heavy tasks in HTTP requests
- Create custom queue implementations

### Events & Listeners

**✅ DO:**
- Use events for decoupled logic (emails, notifications)
- Use `php artisan make:event` and `php artisan make:listener`
- Register in `EventServiceProvider`

**❌ DON'T:**
- Put side effects in core business logic
- Create custom event systems

### Blade Components

**✅ DO:**
- Use Blade components for reusable UI
- Use `php artisan make:component`
- Use slots and props
- Keep logic minimal in components

**❌ DON'T:**
- Copy-paste blade code
- Put business logic in blade files

### Artisan Commands

**✅ DO:**
- Use `php artisan make:command` for CLI tasks
- Follow command naming conventions
- Use command signatures with arguments/options

**❌ DON'T:**
- Create custom CLI scripts outside Laravel

### Configuration

**✅ DO:**
- Use `config/*.php` files for configuration
- Use environment variables with `env()`
- Cache config in production (`php artisan config:cache`)

**❌ DON'T:**
- Hardcode configuration values
- Use `env()` outside config files

### Summary

**The Laravel Way means:**
- Use the framework's features (don't reinvent)
- Follow conventions (resource controllers, RESTful routing)
- Use built-in tools (artisan, service container, validation)
- Keep code in the right place (policies, requests, services)
- Trust Laravel's magic (auto-discovery, binding, routing)

When in doubt: **Check the Laravel documentation first!**

---

## Architecture Principles

### Layered Architecture
```
┌─────────────────────────────────────┐
│  Presentation (Blade + Alpine.js)   │
├─────────────────────────────────────┤
│  API Controllers (Thin)             │
├─────────────────────────────────────┤
│  Service Layer (Business Logic)     │
├─────────────────────────────────────┤
│  Authorization (Policies)           │
├─────────────────────────────────────┤
│  Data Layer (Models)                │
└─────────────────────────────────────┘
```

### Core Rules

**✅ DO:**
- Put business logic in service classes (`app/Services/`)
- Use policies for all authorization (`app/Policies/`)
- Use FormRequest classes for validation (`app/Http/Requests/`)
- Use database transactions for multi-step operations
- Delegate from controllers to services
- Keep controllers thin (< 200 lines)
- Use API endpoints for all data operations (no form submissions)

**❌ DON'T:**
- Put business logic in controllers
- Use inline authorization checks
- Use inline validation in controllers
- Mix concerns
- Create hybrid endpoints

[View full document at: docs/ENGINEERING_STANDARDS.md]
