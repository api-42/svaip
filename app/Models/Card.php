<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Card extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'question',
        'description',
        'image_url',
        'skipable',
        'options',
        'branches',
        'scoring',
    ];
    
    public $casts = [
        'options' => 'array',
        'branches' => 'array',
        'scoring' => 'array',
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

    /**
     * Get the score for a given answer
     * @param int $answer The answer index (0 or 1)
     * @return int The points for this answer
     */
    public function getScore($answer)
    {
        if (!$this->scoring || !isset($this->scoring[$answer])) {
            return 0;
        }
        
        return (int) $this->scoring[$answer];
    }

    /**
     * Validate that all branch targets are valid card IDs within the flow
     * 
     * @param array $flowCards Array of valid card IDs in the flow
     * @return array Validation errors (empty array if valid)
     */
    public function validateBranches(array $flowCards): array
    {
        $errors = [];
        
        if (!is_array($this->branches)) {
            return $errors;
        }
        
        foreach ($this->branches as $index => $branchTarget) {
            // null is valid (means "continue sequentially")
            if ($branchTarget === null) {
                continue;
            }
            
            // Check if branch target exists in the flow
            if (!in_array($branchTarget, $flowCards)) {
                $errors[] = sprintf(
                    'Branch target at option %d (card ID %s) is not part of this flow. Valid cards: [%s]',
                    $index,
                    $branchTarget,
                    implode(', ', $flowCards)
                );
            }
        }
        
        return $errors;
    }

    /**
     * Get all connections where this card is the source
     */
    public function outgoingConnections(): HasMany
    {
        return $this->hasMany(CardConnection::class, 'source_card_id');
    }

    /**
     * Get all connections where this card is the target
     */
    public function incomingConnections(): HasMany
    {
        return $this->hasMany(CardConnection::class, 'target_card_id');
    }
}
