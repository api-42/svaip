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
    public function answer($runId)
    {
        $flowRun = FlowRun::findOrFail($runId);
        $this->ensureOwnsFlowRun($flowRun);

        request()->validate([
            'answer' => 'required|integer|min:0|max:1',
            'card_id' => [
                'required',
                'integer',
                Rule::in($flowRun->flow->cards ?? []),
            ],
        ]);

        $flowRun->results()->updateOrCreate(
            [
                'card_id' => request('card_id'),
            ],
            [
                'answer' => request('answer'),
            ]
        );

        return response()->json([], Response::HTTP_CREATED);
    }

    public function create($id)
    {
        $flow = Flow::findOrFail($id);
        $this->ensureOwnsFlow($flow);

        $flowRun = $flow->runs()->create();

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
        $flowRun->stopped();

        return new FlowRunResource($flowRun);
    }

    private function ensureOwnsFlow(Flow $flow): void
    {
        if ($flow->user_id !== auth()->id()) {
            abort(Response::HTTP_FORBIDDEN, 'Not authorized to access this flow.');
        }
    }

    private function ensureOwnsFlowRun(FlowRun $flowRun): void
    {
        $flowRun->loadMissing('flow');

        if (!$flowRun->flow || $flowRun->flow->user_id !== auth()->id()) {
            abort(Response::HTTP_FORBIDDEN, 'Not authorized to access this run.');
        }
    }

    public function answer($id, $flowRunId)
    {
        $flow = Flow::findOrFail($id);
        $flowRun = $flow->runs()->where('id', $flowRunId)->firstOrFail();

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
