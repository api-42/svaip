<?php

namespace App\Models;

use App\Models\Card;
use App\Models\Flow;
use App\Models\FlowRunResult;
use Illuminate\Database\Eloquent\Model;

class FlowRun extends Model
{
    protected $guarded = [];
    public $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

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

    public function cards()
    {
        return Card::whereIn('id', $this->flow->cards)->get();
    }
}
