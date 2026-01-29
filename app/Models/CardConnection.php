<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CardConnection extends Model
{
    protected $fillable = [
        'source_card_id',
        'target_card_id',
        'source_option',
    ];

    protected $casts = [
        'source_option' => 'integer',
    ];

    /**
     * Get the source card that this connection starts from
     */
    public function sourceCard(): BelongsTo
    {
        return $this->belongsTo(Card::class, 'source_card_id');
    }

    /**
     * Get the target card that this connection points to
     */
    public function targetCard(): BelongsTo
    {
        return $this->belongsTo(Card::class, 'target_card_id');
    }
}
