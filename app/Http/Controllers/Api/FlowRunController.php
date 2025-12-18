<?php

namespace App\Http\Controllers\Api;

use App\Models\Flow;
use App\Models\FlowRun;
use App\Http\Controllers\Controller;
use App\Http\Resources\FlowRunResource;

class FlowRunController extends Controller
{
    public function create($id)
    {
        $flow = Flow::findOrFail($id);

        $flowRun = $flow->runs()->create();

        foreach ($flow->cards() as $card) {
            $flowRun->results()->create([
                'card_id' => $card->id,
            ]);
        }

        return new FlowRunResource($flowRun);
    }

    public function start($id, $flowRunId)
    {
        $flow = Flow::findOrFail($id);

        $flowRun = $flow->runs()->where('id', $flowRunId)->firstOrFail();
        $flowRun->started();

        return new FlowRunResource($flowRun);
    }

    public function stop($id, $flowRunId)
    {
        $flow = Flow::findOrFail($id);

        $flowRun = $flow->runs()->where('id', $flowRunId)->firstOrFail();
        $flowRun->stopped();
        $flowRun->save();

        return new FlowRunResource($flowRun);
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
