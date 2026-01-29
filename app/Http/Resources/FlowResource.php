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
            'description' => $this->description,
            'is_public' => $this->is_public,
            'public_slug' => $this->public_slug,
            'public_url' => $this->when($this->is_public, fn() => $this->publicUrl()),
            'cards' => CardResource::collection($this->whenLoaded('cardsRelation')),
            'end_cards' => $this->when(
                isset($this->metadata['end_cards']),
                fn() => $this->metadata['end_cards']
            ),
            'layout' => $this->layout,
            'metadata' => $this->metadata,
            'user_id' => $this->user_id,
            'runs' => FlowRunResource::collection($this->when(request()->query('include') === 'runs', fn() => $this->runs)),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
