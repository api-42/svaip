<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResultTemplate extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'flow_id',
        'title',
        'content',
        'image_url',
        'min_score',
        'max_score',
        'cta_text',
        'cta_url',
        'order',
    ];

    public function flow()
    {
        return $this->belongsTo(Flow::class);
    }

    public function flowRuns()
    {
        return $this->hasMany(FlowRun::class);
    }

    /**
     * Check if a score matches this result template's range
     */
    public function matchesScore(int $score): bool
    {
        $minMatch = $score >= $this->min_score;
        $maxMatch = $this->max_score === null || $score <= $this->max_score;
        
        return $minMatch && $maxMatch;
    }
}
