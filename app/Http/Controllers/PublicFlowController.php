<?php

namespace App\Http\Controllers;

use App\Models\Flow;
use App\Models\FlowRun;
use App\Models\Card;
use App\Models\FlowRunResult;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PublicFlowController extends Controller
{
    /**
     * Show public flow start page
     */
    public function show($slug)
    {
        $flow = Flow::where('public_slug', $slug)
            ->where('is_public', true)
            ->firstOrFail();

        return view('public.flow-start', compact('flow'));
    }

    /**
     * Start an anonymous flow run
     */
    public function start(Request $request, $slug)
    {
        $flow = Flow::where('public_slug', $slug)
            ->where('is_public', true)
            ->firstOrFail();

        if (!$flow->allow_anonymous) {
            return redirect()->route('login')
                ->with('message', 'This flow requires authentication.');
        }

        // Create anonymous run
        $run = FlowRun::create([
            'id' => Str::uuid(),
            'flow_id' => $flow->id,
            'user_id' => null,
            'started_at' => now(),
        ]);

        // Store session token in cookie for tracking
        return redirect()
            ->route('public.flow.run', ['slug' => $slug, 'runId' => $run->id])
            ->cookie('svaip_session', $run->session_token, 60 * 24 * 7); // 7 days
    }

    /**
     * Show current card in the run
     */
    public function run(Request $request, $slug, $runId)
    {
        $flow = Flow::where('public_slug', $slug)
            ->where('is_public', true)
            ->firstOrFail();

        $run = FlowRun::findOrFail($runId);

        if ($run->flow_id !== $flow->id) {
            abort(404);
        }

        // Verify session token for anonymous users
        if ($run->isAnonymous()) {
            $sessionToken = $request->cookie('svaip_session');
            if (!$sessionToken || $sessionToken !== $run->session_token) {
                abort(403, 'Invalid session');
            }
        }

        // Check if completed
        if ($run->completed_at) {
            return redirect()->route('public.flow.result', [
                'slug' => $slug,
                'runId' => $runId
            ]);
        }

        // Get next card
        $card = $this->getNextCard($run);

        if (!$card) {
            // No more cards, complete the run
            return $this->complete($run, $slug, $runId);
        }

        return view('public.flow-run', compact('flow', 'run', 'card'));
    }

    /**
     * Submit answer to current card
     */
    public function answer(Request $request, $slug, $runId)
    {
        $request->validate([
            'card_id' => 'required|exists:cards,id',
            'answer' => 'required|in:0,1',
        ]);

        $flow = Flow::where('public_slug', $slug)
            ->where('is_public', true)
            ->firstOrFail();

        $run = FlowRun::findOrFail($runId);

        if ($run->flow_id !== $flow->id) {
            abort(404);
        }

        // Verify session token for anonymous users
        if ($run->isAnonymous()) {
            $sessionToken = $request->cookie('svaip_session');
            if (!$sessionToken || $sessionToken !== $run->session_token) {
                abort(403, 'Invalid session');
            }
        }

        // Store the answer
        FlowRunResult::updateOrCreate(
            [
                'flow_run_id' => $run->id,
                'card_id' => $request->card_id,
            ],
            [
                'answer' => $request->answer,
            ]
        );

        return redirect()->route('public.flow.run', [
            'slug' => $slug,
            'runId' => $runId
        ]);
    }

    /**
     * Show results page
     */
    public function result(Request $request, $slug, $runId)
    {
        $flow = Flow::where('public_slug', $slug)
            ->where('is_public', true)
            ->firstOrFail();

        $run = FlowRun::with('resultTemplate')->findOrFail($runId);

        if ($run->flow_id !== $flow->id) {
            abort(404);
        }

        // Verify session token for anonymous users
        if ($run->isAnonymous()) {
            $sessionToken = $request->cookie('svaip_session');
            if (!$sessionToken || $sessionToken !== $run->session_token) {
                abort(403, 'Invalid session');
            }
        }

        if (!$run->completed_at) {
            return redirect()->route('public.flow.run', [
                'slug' => $slug,
                'runId' => $runId
            ]);
        }

        return view('public.flow-result', compact('flow', 'run'));
    }

    /**
     * Get the next card to display
     */
    private function getNextCard(FlowRun $run)
    {
        $answeredCardIds = $run->results()->pluck('card_id')->toArray();
        $cards = Card::whereIn('id', $run->flow->cards)->get();

        // Get the last answered card to check for branching
        $lastResult = $run->results()->latest('updated_at')->first();

        if ($lastResult) {
            $lastCard = Card::find($lastResult->card_id);
            $nextCardId = $lastCard?->getNextCardId($lastResult->answer);

            if ($nextCardId) {
                $nextCard = Card::find($nextCardId);
                if ($nextCard && !in_array($nextCard->id, $answeredCardIds)) {
                    return $nextCard;
                }
            }
        }

        // Return first unanswered card
        foreach ($cards as $card) {
            if (!in_array($card->id, $answeredCardIds)) {
                return $card;
            }
        }

        return null;
    }

    /**
     * Complete the run
     */
    private function complete(FlowRun $run, $slug, $runId)
    {
        $run->stopped();
        $run->calculateScore();
        $run->assignResultTemplate();

        return redirect()->route('public.flow.result', [
            'slug' => $slug,
            'runId' => $runId
        ]);
    }
}

