<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Http\Resources\CardResource;
use Illuminate\Http\Resources\Json\JsonResource;

class FlowRunResultsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            // 'card' => new CardResource($this->card),
            'card_id' => $this->card_id,
            'answer' => $this->answer,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
