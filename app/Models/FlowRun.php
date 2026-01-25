<?php

namespace App\Models;

use App\Models\Card;
use App\Models\Flow;
use App\Models\FlowRunResult;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlowRun extends Model
{
    use HasFactory;
    
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];
    public $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($flowRun) {
            $flowRun->share_token = \Illuminate\Support\Str::random(32);
            
            // Generate session token for anonymous users
            if (!$flowRun->user_id) {
                $flowRun->session_token = \Illuminate\Support\Str::random(32);
            }
        });
    }

    public function started()
    {
        if (!$this->started_at) {
            $this->started_at = now();
            $this->save();
        }
    }

    public function stopped()
    {
        if (!$this->completed_at) {
            $this->completed_at = now();
            $this->save();
        }
    }

    public function results()
    {
        return $this->hasMany(FlowRunResult::class);
    }

    public function flow()
    {
        return $this->belongsTo(Flow::class);
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeAnonymous($query)
    {
        return $query->whereNull('user_id');
    }

    public function scopeAuthenticated($query)
    {
        return $query->whereNotNull('user_id');
    }

    public function isAnonymous(): bool
    {
        return $this->user_id === null;
    }

    public function cards()
    {
        return Card::whereIn('id', $this->flow->cards)->get();
    }

    public function resultTemplate()
    {
        return $this->belongsTo(ResultTemplate::class);
    }

    /**
     * Calculate total score based on answers and card scoring
     */
    public function calculateScore(): int
    {
        $score = 0;
        
        // Reload results to get fresh data
        $this->load('results');
        
        foreach ($this->results as $result) {
            if ($result->answer !== null) {
                $card = Card::find($result->card_id);
                if ($card) {
                    $score += $card->getScore($result->answer);
                }
            }
        }
        
        $this->total_score = $score;
        $this->save();
        
        return $score;
    }

    /**
     * Determine and assign the appropriate result template based on score
     */
    public function assignResultTemplate(): ?ResultTemplate
    {
        // Calculate score if not already calculated
        if ($this->total_score === 0 && !$this->isDirty('total_score')) {
            $this->calculateScore();
        }
        
        $score = $this->total_score;
        
        $template = $this->flow->resultTemplates()
            ->get()
            ->first(function ($template) use ($score) {
                return $template->matchesScore($score);
            });
        
        if ($template) {
            $this->result_template_id = $template->id;
            $this->save();
        }
        
        return $template;
    }
}
