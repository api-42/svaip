<?php

namespace App\Http\Controllers\Api;

use App\Models\Card;
use App\Models\Flow;
use Illuminate\Support\Str;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Http\Resources\FlowResource;

class FlowController extends Controller
{
    public function run($id)
    {
        $flow = Flow::findOrFail($id);
        $this->ensureOwnsFlow($flow);

        $newRun = $flow->runs()->create(['id' => Str::uuid()]);

        return redirect()->route('flow-run-start', ['id' => $newRun->id]);
    }

    public function show($id)
    {
        $flow = Flow::findOrFail($id);
        $this->ensureOwnsFlow($flow);

        return new FlowResource($flow);
    }

    public function index()
    {
        return response()->json(
            Flow::where('user_id', auth()->id())->get()
        );
    }

    public function store()
    {
        request()->validate([
            'cards' => 'required|array|min:1',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'cards.*.skipable' => 'sometimes|accepted',
            'cards.*.options' => 'required|array|min:2|max:2',
            'cards.*.options.*' => 'required|string|max:255',
            'cards.*.description' => 'nullable|string|max:255',
            'cards.*.question' => 'required|string|max:255|min:1',
            'cards.*.branches' => 'nullable|array',
        ]);

        $cards = [];
        $cardIdMapping = []; // Map from index to actual card ID
        
        // First pass: create all cards
        foreach (request('cards', []) as $index => $cardData) {
            $card = Card::create([
                'question' => $cardData['question'],
                'description' => $cardData['description'] ?? null,
                'skipable' => $cardData['skipable'],
                'options' => $cardData['options'],
                'branches' => null, // We'll update this in second pass
            ]);
            $cards[] = $card;
            $cardIdMapping[$index + 1] = $card->id; // Map 1-based index to card ID
        }

        // Second pass: update branches with actual card IDs
        foreach (request('cards', []) as $index => $cardData) {
            if (isset($cardData['branches']) && is_array($cardData['branches'])) {
                $branches = [];
                foreach ($cardData['branches'] as $answer => $targetIndex) {
                    if ($targetIndex !== null && isset($cardIdMapping[$targetIndex])) {
                        $branches[$answer] = $cardIdMapping[$targetIndex];
                    } else {
                        $branches[$answer] = null;
                    }
                }
                $cards[$index]->branches = $branches;
                $cards[$index]->save();
            }
        }

        $flow = auth()->user()->flows()->create([
            'name' => request('name'),
            'cards' => collect($cards)->pluck('id'),
            'description' => request('description'),
        ]);

        return redirect()->route('flow.index');
    }

    private function ensureOwnsFlow(Flow $flow): void
    {
        if ($flow->user_id !== auth()->id()) {
            abort(Response::HTTP_FORBIDDEN, 'Not authorized to access this flow.');
        }
    }
}
