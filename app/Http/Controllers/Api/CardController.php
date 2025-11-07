<?php

namespace App\Http\Controllers\Api;

use App\Models\Card;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CardController extends Controller
{
    public function index()
    {
        $cards = Card::all();
        return response()->json($cards);
    }

    public function store()
    {
        request()->validate([
            'answers' => 'nullable|array',
            'answers.*' => 'string|max:100',
            'description' => 'nullable|string',
            'question' => 'required|string|max:255',
        ]);

        $card = Card::create(request()->all());

        return response()->json($card, 201);
    }
}
