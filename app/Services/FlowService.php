<?php

namespace App\Services;

use App\Models\Flow;
use App\Models\Card;
use App\Models\CardConnection;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FlowService
{
    /**
     * Create a new flow with cards
     *
     * @param User $user
     * @param array $data ['name', 'description', 'cards', 'end_cards', 'layout']
     * @return Flow
     * @throws \Exception
     */
    public function createFlow(User $user, array $data): Flow
    {
        DB::beginTransaction();

        try {
            // DEBUG: Log incoming data
            Log::info('[SAVE DEBUG] Incoming cards data', [
                'cards_count' => count($data['cards'] ?? []),
                'cards' => $data['cards'] ?? []
            ]);
            
            // Validate cycles before creation
            if (isset($data['cards']) && is_array($data['cards'])) {
                $this->validateCycles($data['cards']);
            }

            // Create cards (question cards only)
            $cards = [];
            $cardIdMapping = [];
            $layout = [];

            foreach ($data['cards'] ?? [] as $index => $cardData) {
                $card = Card::create([
                    'question' => $cardData['question'],
                    'description' => $cardData['description'] ?? null,
                    'skipable' => $cardData['skipable'] ?? false,
                    'options' => $cardData['options'] ?? ['Yes', 'No'],
                    'scoring' => $cardData['scoring'] ?? null,
                ]);

                $cards[] = $card;
                $cardIdMapping[$index] = $card->id;

                // Store position
                if (isset($cardData['position'])) {
                    $layout[$index] = [
                        'x' => (int)($cardData['position']['x'] ?? 0),
                        'y' => (int)($cardData['position']['y'] ?? 0),
                    ];
                }
            }

            // Create connections from branches data
            Log::info('[UPDATE DEBUG] Creating connections from branches');
            foreach ($data['cards'] ?? [] as $index => $cardData) {
                Log::info('[UPDATE DEBUG] Processing card', [
                    'index' => $index,
                    'has_branches' => isset($cardData['branches']),
                    'branches' => $cardData['branches'] ?? null,
                    'card_id_mapping' => $cardIdMapping
                ]);
                
                if (isset($cardData['branches']) && is_array($cardData['branches'])) {
                    foreach ($cardData['branches'] as $answer => $targetIndex) {
                        Log::info('[UPDATE DEBUG] Processing branch', [
                            'source_index' => $index,
                            'answer' => $answer,
                            'target_index' => $targetIndex,
                            'target_exists' => isset($cardIdMapping[$targetIndex])
                        ]);
                        
                        if ($targetIndex !== null && isset($cardIdMapping[$targetIndex])) {
                            $connection = CardConnection::create([
                                'source_card_id' => $cardIdMapping[$index],
                                'target_card_id' => $cardIdMapping[$targetIndex],
                                'source_option' => (int)$answer,
                            ]);
                            Log::info('[UPDATE DEBUG] Connection created', ['connection_id' => $connection->id]);
                        }
                    }
                }
            }

            // Prepare metadata with end cards
            $metadata = [];
            if (isset($data['end_cards'])) {
                $metadata['end_cards'] = $data['end_cards'];
                
                // Store end card positions in layout
                foreach ($data['end_cards'] as $index => $endCard) {
                    if (isset($endCard['position'])) {
                        $layout['end_' . $index] = [
                            'x' => (int)($endCard['position']['x'] ?? 0),
                            'y' => (int)($endCard['position']['y'] ?? 0),
                        ];
                    }
                }
            }

            // Create flow
            $flow = $user->flows()->create([
                'name' => $data['name'],
                'description' => $data['description'] ?? '',
                'cards' => collect($cards)->pluck('id')->toArray(),
                'layout' => !empty($layout) ? $layout : null,
                'metadata' => !empty($metadata) ? $metadata : null,
            ]);

            DB::commit();

            Log::info('Flow saved successfully', ['flow_id' => $flow->id, 'user_id' => $user->id]);

            return $flow;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Flow creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id
            ]);
            throw $e;
        }
    }

    /**
     * Update an existing flow
     *
     * @param Flow $flow
     * @param array $data ['name', 'description', 'cards', 'end_cards', 'layout']
     * @return Flow
     * @throws \Exception
     */
    public function updateFlow(Flow $flow, array $data): Flow
    {
        DB::beginTransaction();

        try {
            // DEBUG: Log incoming data
            Log::info('[UPDATE DEBUG] Incoming cards data', [
                'cards_count' => count($data['cards'] ?? []),
                'cards' => $data['cards'] ?? []
            ]);
            
            // Validate cycles before update
            if (isset($data['cards']) && is_array($data['cards'])) {
                $this->validateCycles($data['cards']);
            }

            // Update flow basic info
            $flow->name = $data['name'];
            $flow->description = $data['description'] ?? '';

            // Check for active flow runs before modifying structure
            $existingRuns = \App\Models\FlowRun::where('flow_id', $flow->id)
                ->whereNull('completed_at')
                ->exists();
            
            if ($existingRuns) {
                throw new \InvalidArgumentException(
                    'Cannot modify flow structure while active flow runs exist. ' .
                    'Please complete or archive existing runs before updating.'
                );
            }

            // Delete existing cards (connections will cascade delete automatically)
            if (!empty($flow->cards)) {
                Card::whereIn('id', $flow->cards)->delete();
            }

            // Create new cards
            $cards = [];
            $cardIdMapping = [];
            $layout = [];

            foreach ($data['cards'] ?? [] as $index => $cardData) {
                $card = Card::create([
                    'question' => $cardData['question'],
                    'description' => $cardData['description'] ?? null,
                    'skipable' => $cardData['skipable'] ?? false,
                    'options' => $cardData['options'] ?? ['Yes', 'No'],
                    'scoring' => $cardData['scoring'] ?? null,
                ]);

                $cards[] = $card;
                $cardIdMapping[$index] = $card->id;

                // Store position
                if (isset($cardData['position'])) {
                    $layout[$index] = [
                        'x' => (int)($cardData['position']['x'] ?? 0),
                        'y' => (int)($cardData['position']['y'] ?? 0),
                    ];
                }
            }

            // Create connections from branches data
            foreach ($data['cards'] ?? [] as $index => $cardData) {
                if (isset($cardData['branches']) && is_array($cardData['branches'])) {
                    foreach ($cardData['branches'] as $answer => $targetIndex) {
                        if ($targetIndex !== null && isset($cardIdMapping[$targetIndex])) {
                            CardConnection::create([
                                'source_card_id' => $cardIdMapping[$index],
                                'target_card_id' => $cardIdMapping[$targetIndex],
                                'source_option' => (int)$answer,
                            ]);
                        }
                    }
                }
            }

            // Update flow cards array
            $flow->cards = collect($cards)->pluck('id')->toArray();

            // Update metadata with end cards
            $metadata = $flow->metadata ?? [];
            if (isset($data['end_cards'])) {
                $metadata['end_cards'] = $data['end_cards'];
                
                // Update end card positions in layout
                foreach ($data['end_cards'] as $index => $endCard) {
                    if (isset($endCard['position'])) {
                        $layout['end_' . $index] = [
                            'x' => (int)($endCard['position']['x'] ?? 0),
                            'y' => (int)($endCard['position']['y'] ?? 0),
                        ];
                    }
                }
            }

            $flow->metadata = !empty($metadata) ? $metadata : null;
            $flow->layout = !empty($layout) ? $layout : null;

            $flow->save();

            DB::commit();

            Log::info('Flow updated successfully', ['flow_id' => $flow->id]);

            return $flow;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Flow update failed', [
                'flow_id' => $flow->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Delete a flow and its cards
     *
     * @param Flow $flow
     * @return bool
     */
    public function deleteFlow(Flow $flow): bool
    {
        DB::beginTransaction();

        try {
            // Delete associated cards
            if (!empty($flow->cards)) {
                Card::whereIn('id', $flow->cards)->delete();
            }

            // Delete flow
            $flow->delete();

            DB::commit();

            Log::info('Flow deleted successfully', ['flow_id' => $flow->id]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Flow deletion failed', [
                'flow_id' => $flow->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Validate that cards don't create cycles using Depth-First Search
     *
     * @param array $cards
     * @throws \InvalidArgumentException if cycle detected
     * @return bool
     */
    public function validateCycles(array $cards): bool
    {
        if (empty($cards)) {
            return true;
        }

        // Build adjacency list from branches
        $graph = [];
        foreach ($cards as $index => $card) {
            $graph[$index] = [];
            if (isset($card['branches']) && is_array($card['branches'])) {
                foreach ($card['branches'] as $targetIndex) {
                    if ($targetIndex !== null && isset($cards[$targetIndex])) {
                        $graph[$index][] = $targetIndex;
                    }
                }
            }
        }

        // DFS cycle detection
        $visited = []; // 0 = unvisited, 1 = visiting, 2 = visited
        foreach (array_keys($cards) as $node) {
            $visited[$node] = 0;
        }

        foreach (array_keys($cards) as $node) {
            if ($visited[$node] === 0) {
                if ($this->detectCyclesDFS($node, $graph, $visited)) {
                    throw new \InvalidArgumentException('Cards contain a cycle. Please remove circular connections.');
                }
            }
        }

        return true;
    }

    /**
     * DFS helper for cycle detection
     *
     * @param int $node
     * @param array $graph
     * @param array &$visited
     * @return bool
     */
    private function detectCyclesDFS(int $node, array $graph, array &$visited): bool
    {
        $visited[$node] = 1; // Mark as visiting

        if (isset($graph[$node])) {
            foreach ($graph[$node] as $neighbor) {
                if ($visited[$neighbor] === 1) {
                    // Back edge found - cycle detected
                    return true;
                }
                if ($visited[$neighbor] === 0) {
                    if ($this->detectCyclesDFS($neighbor, $graph, $visited)) {
                        return true;
                    }
                }
            }
        }

        $visited[$node] = 2; // Mark as visited
        return false;
    }

    /**
     * Separate end cards from question cards
     * (Currently handled by frontend, but can be used server-side)
     *
     * @param array $allCards
     * @return array ['question_cards' => [], 'end_cards' => []]
     */
    public function separateEndCards(array $allCards): array
    {
        $questionCards = [];
        $endCards = [];

        foreach ($allCards as $card) {
            if (isset($card['type']) && $card['type'] === 'end') {
                $endCards[] = $card;
            } else {
                $questionCards[] = $card;
            }
        }

        return [
            'question_cards' => $questionCards,
            'end_cards' => $endCards,
        ];
    }

    /**
     * Build layout data from cards
     *
     * @param array $cards
     * @param array $endCards
     * @return array
     */
    public function buildLayoutData(array $cards, array $endCards = []): array
    {
        $layout = [];

        // Question card positions
        foreach ($cards as $index => $card) {
            if (isset($card['x']) && isset($card['y'])) {
                $layout[$index] = [
                    'x' => (int)$card['x'],
                    'y' => (int)$card['y'],
                ];
            }
        }

        // End card positions
        foreach ($endCards as $index => $endCard) {
            if (isset($endCard['x']) && isset($endCard['y'])) {
                $layout['end_' . $index] = [
                    'x' => (int)$endCard['x'],
                    'y' => (int)$endCard['y'],
                ];
            }
        }

        return $layout;
    }
}
