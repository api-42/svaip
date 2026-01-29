<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Flow extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name',
        'description', 
        'cards',
        'layout',
        'metadata',
        'is_public',
        'allow_anonymous',
    ];
    
    public $casts = [
        'cards' => 'array',
        'layout' => 'array',
        'metadata' => 'array',
        'is_public' => 'boolean',
        'allow_anonymous' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($flow) {
            if ($flow->is_public && !$flow->public_slug) {
                $flow->public_slug = $flow->generateUniqueSlug();
            }
        });
        
        static::updating(function ($flow) {
            if ($flow->is_public && !$flow->public_slug) {
                $flow->public_slug = $flow->generateUniqueSlug();
            }
        });
    }

    public function runs()
    {
        return $this->hasMany(FlowRun::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cards()
    {
        if (empty($this->cards)) {
            return collect([]);
        }
        
        // Get all cards and key by ID for fast lookup
        $cards = Card::whereIn('id', $this->cards)->get()->keyBy('id');
        
        // Preserve order from the cards array
        return collect($this->cards)->map(function($cardId) use ($cards) {
            return $cards->get($cardId);
        })->filter(); // Remove nulls if card was deleted
    }

    public function resultTemplates()
    {
        return $this->hasMany(ResultTemplate::class)->orderBy('order');
    }

    public function shortDescription()
    {
        return strlen($this->description) > 50
            ? substr($this->description, 0, 50) . '...'
            : $this->description;
    }

    public function isPublic(): bool
    {
        return $this->is_public;
    }

    public function generateUniqueSlug(): string
    {
        $slug = \Illuminate\Support\Str::slug($this->name);
        $slug = substr($slug, 0, 55); // Leave room for suffix
        
        // Check if slug exists
        $originalSlug = $slug;
        $counter = 1;
        
        while (static::where('public_slug', $slug)->where('id', '!=', $this->id)->exists()) {
            $suffix = '-' . \Illuminate\Support\Str::random(5);
            $slug = $originalSlug . $suffix;
            $counter++;
            
            if ($counter > 10) {
                $slug = $originalSlug . '-' . uniqid();
                break;
            }
        }
        
        return $slug;
    }

    public function publicUrl(): ?string
    {
        if (!$this->is_public || !$this->public_slug) {
            return null;
        }
        
        return url('/p/' . $this->public_slug);
    }
}
