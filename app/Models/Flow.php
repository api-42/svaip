<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Flow extends Model
{
    protected $guarded = [];
    public $casts = [
        'cards' => 'array',
    ];

    public function runs()
    {
        return $this->hasMany(FlowRun::class);
    }

    public function cards()
    {
        return Card::whereIn('id', $this->cards)->get();
    }

    public function shortDescription()
    {
        return strlen($this->description) > 50
            ? substr($this->description, 0, 50) . '...'
            : $this->description;
    }
}
