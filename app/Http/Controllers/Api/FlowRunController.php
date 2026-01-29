<?php

namespace App\Http\Controllers\Api;

use App\Models\Flow;
use App\Models\FlowRun;
use App\Http\Controllers\Controller;
use App\Http\Resources\FlowRunResource;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class FlowRunController extends Controller
{
    /**
     * Ensure the authenticated user owns the flow.
     */
    private function ensureOwnsFlow(Flow $flow): void
    {
        if ($flow->user_id !== auth()->id()) {
            abort(403, 'Not authorized to access this flow.');
        }
    }

    /**
     * Ensure the authenticated user owns the flow run.
     */
    private function ensureOwnsFlowRun(FlowRun $flowRun): void
    {
        if ($flowRun->user_id !== auth()->id()) {
            abort(403, 'Not authorized to access this flow run.');
        }
    }

    public function create($id)
    {
        $flow = Flow::findOrFail($id);
        $this->ensureOwnsFlow($flow);

        $flowRun = $flow->runs()->create([
            'id' => \Illuminate\Support\Str::uuid(),
            'user_id' => auth()->id(),
            'started_at' => now(),
        ]);

        foreach ($flow->cards() as $card) {
            $flowRun->results()->create([
                'card_id' => $card->id,
            ]);
        }

        return new FlowRunResource($flowRun);
    }

    public function start($flowRunId)
    {
        $flowRun = FlowRun::findOrFail($flowRunId);
        $this->ensureOwnsFlowRun($flowRun);
        $flowRun->started();

        return new FlowRunResource($flowRun);
    }

    public function stop($flowRunId)
    {
        $flowRun = FlowRun::findOrFail($flowRunId);
        $this->ensureOwnsFlowRun($flowRun);
        
        // Validate and store form field responses if provided
        if (request()->has('form_data') && is_array(request('form_data'))) {
            foreach (request('form_data') as $fieldName => $fieldValue) {
                \App\Models\FlowRunFormResponse::create([
                    'flow_run_id' => $flowRun->id,
                    'field_name' => $fieldName,
                    'field_value' => $fieldValue,
                ]);
            }
        }
        
        $flowRun->stopped();
        
        // Calculate score and assign result template when flow is completed
        $flowRun->calculateScore();
        $flowRun->assignResultTemplate();

        return new FlowRunResource($flowRun);
    }

    public function answer($id, $flowRunId)
    {
        $flow = Flow::findOrFail($id);
        $flowRun = $flow->runs()->where('id', $flowRunId)->firstOrFail();
        $this->ensureOwnsFlow($flow);

        request()->validate([
            'card_id' => 'required|exists:cards,id',
            'answer' => 'required|integer|in:0,1',
        ]);

        $cardId = request('card_id');
        $answer = request('answer');

        // Record the answer
        $result = $flowRun->results()
            ->where('card_id', $cardId)
            ->firstOrFail();
        
        $result->answer = $answer;
        $result->answered_at = now();
        $result->save();

        // Determine next card using branching logic
        $card = \App\Models\Card::findOrFail($cardId);
        $nextCardId = $card->getNextCardId($answer);

        if ($nextCardId !== null) {
            // Branch to specific card
            $nextCard = \App\Models\Card::find($nextCardId);
        } else {
            // Continue to next card in sequence
            $cards = $flowRun->cards();
            $currentIndex = $cards->search(function($c) use ($cardId) {
                return $c->id == $cardId;
            });
            
            $nextCard = ($currentIndex !== false && $currentIndex < $cards->count() - 1) 
                ? $cards[$currentIndex + 1] 
                : null;
        }

        return response()->json([
            'success' => true,
            'next_card' => $nextCard ? [
                'id' => $nextCard->id,
                'question' => $nextCard->question,
                'description' => $nextCard->description,
                'options' => $nextCard->options,
                'skipable' => $nextCard->skipable,
            ] : null,
        ]);
    }
}
