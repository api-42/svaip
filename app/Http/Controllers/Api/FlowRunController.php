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
}
