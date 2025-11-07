<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Http\Resources\FlowRunResultsResource;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\CardResource;

class FlowRunResource extends JsonResource
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
            'cards' => CardResource::collection($this->cards()),
            'results' => FlowRunResultsResource::collection($this->results),
            'started_at' => $this->started_at,
            'completed_at' => $this->completed_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
