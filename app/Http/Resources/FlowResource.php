<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Http\Resources\CardResource;
use Illuminate\Http\Resources\Json\JsonResource;

class FlowResource extends JsonResource
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
            'name' => $this->name,
            'cards' => CardResource::collection($this->cards()),
            'runs' => FlowRunResource::collection($this->runs),
            'description' => $this->description,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
