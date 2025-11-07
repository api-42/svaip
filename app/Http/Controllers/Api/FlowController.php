<?php

namespace App\Http\Controllers\Api;

use App\Models\Card;
use App\Models\Flow;
use App\Http\Controllers\Controller;
use App\Http\Resources\FlowResource;

class FlowController extends Controller
{
    public function show($id)
    {
        $flow = Flow::findOrFail($id);

        return new FlowResource($flow);
    }

    public function index()
    {
        return response()->json(Flow::all());
    }

    public function store()
    {
        request()->validate([
            'cards' => 'required|array|min:1',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'cards.*.skipable' => 'required|boolean',
            'cards.*.options' => 'required|array|min:2|max:2',
            'cards.*.options.*' => 'required|string|max:255',
            'cards.*.description' => 'nullable|string|max:255',
            'cards.*.question' => 'required|string|max:255|min:1',
        ]);

        $cards = [];
        foreach (request('cards', []) as $card) {
            $cards[] = Card::create($card);
        }

        $flow = Flow::create([
            'name' => request('name'),
            'cards' => collect($cards)->pluck('id'),
            'description' => request('description'),
        ]);

        return response()->json($flow, 201);
    }
}
