<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Card extends Model
{
    protected $guarded = [];
    public $casts = [
        'options' => 'array',
        'branches' => 'array',
    ];

    /**
     * Get the next card ID based on the answer
     * @param int $answer The answer index (0 for left, 1 for right)
     * @return int|null The next card ID or null to continue in sequence
     */
    public function getNextCardId($answer)
    {
        if (!$this->branches || !isset($this->branches[$answer])) {
            return null; // Continue to next card in sequence
        }
        
        return $this->branches[$answer];
    }
}
