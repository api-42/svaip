<?php

namespace App\Models;

use App\Models\Card;
use App\Models\Flow;
use App\Models\FlowRunResult;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class FlowRun extends Model
{
    use HasFactory;
    
    public $incrementing = false;
    protected $keyType = 'string';
    
    // Only allow safe fields for mass assignment
    protected $fillable = ['id', 'user_id', 'flow_id', 'started_at'];
    public $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'score_calculated' => 'boolean',
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
        if (empty($this->flow->cards)) {
            return collect([]);
        }
        
        // Get all cards and key by ID for fast lookup
        $cards = Card::whereIn('id', $this->flow->cards)->get()->keyBy('id');
        
        // Preserve order from the flow's cards array
        return collect($this->flow->cards)->map(function($cardId) use ($cards) {
            return $cards->get($cardId);
        })->filter(); // Remove nulls if card was deleted
    }

    public function resultTemplate()
    {
        return $this->belongsTo(ResultTemplate::class);
    }
    
    /**
     * Get the form responses for this flow run
     */
    public function formResponses()
    {
        return $this->hasMany(FlowRunFormResponse::class, 'flow_run_id', 'id');
    }
    
    /**
     * Calculate time spent on each card
     * Returns array: [card_id => duration_seconds]
     */
    public function getCardTimings(): array
    {
        $results = $this->results()
            ->whereNotNull('answered_at')
            ->orderBy('answered_at')
            ->get();
        
        if ($results->isEmpty()) {
            return [];
        }
        
        $timings = [];
        $previousTime = $this->started_at;
        
        foreach ($results as $result) {
            if ($result->answered_at) {
                $duration = $previousTime->diffInSeconds($result->answered_at);
                $timings[$result->card_id] = $duration;
                $previousTime = $result->answered_at;
            }
        }
        
        return $timings;
    }

    /**
     * Calculate total score based on answers and card scoring
     * Uses pessimistic locking to prevent race conditions
     */
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
            
            $score = 0;
            
            // Reload results to get fresh data within transaction
            $flowRun->load('results');
            
            foreach ($flowRun->results as $result) {
                if ($result->answer !== null) {
                    $card = Card::find($result->card_id);
                    if ($card) {
                        $score += $card->getScore($result->answer);
                    }
                }
            }
            
            // Update and save within transaction
            $flowRun->total_score = $score;
            $flowRun->score_calculated = true;
            $flowRun->save();
            
            // Update current instance
            $this->total_score = $score;
            $this->score_calculated = true;
            
            return $score;
        });
    }

    /**
     * Determine and assign the appropriate result template based on score
     */
    public function assignResultTemplate(): ?ResultTemplate
    {
        // Calculate score if not already calculated
        if (!$this->score_calculated) {
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
