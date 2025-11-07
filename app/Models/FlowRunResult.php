<?php

namespace App\Models;

use App\Models\Card;
use Illuminate\Database\Eloquent\Model;

class FlowRunResult extends Model
{
    protected $guarded = [];
    public $casts = [
        'answer' => 'array',
    ];

    public function card()
    {
        return $this->belongsTo(Card::class);
    }
}
