<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlowRunFormResponse extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'flow_run_id',
        'field_name',
        'field_value',
    ];
    
    /**
     * Get the flow run that owns this form response
     */
    public function flowRun()
    {
        return $this->belongsTo(FlowRun::class, 'flow_run_id', 'id');
    }
}
