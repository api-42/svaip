<?php

namespace App\Models;

use App\Models\Card;
use Illuminate\Database\Eloquent\Model;

class FlowRunResult extends Model
{
    protected $fillable = [
        'flow_run_id',
        'card_id',
        'answer',
        'answered_at',
    ];
    
    public $casts = [
        'answer' => 'integer',
        'answered_at' => 'datetime',
    ];

    public function card()
    {
        return $this->belongsTo(Card::class);
    }
}
