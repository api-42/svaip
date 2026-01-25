<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ResultTemplate>
 */
class ResultTemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'flow_id' => \App\Models\Flow::factory(),
            'title' => fake()->sentence(4),
            'content' => fake()->paragraph(),
            'image_url' => fake()->optional()->imageUrl(),
            'min_score' => 0,
            'max_score' => 100,
            'cta_text' => fake()->optional()->words(2, true),
            'cta_url' => fake()->optional()->url(),
            'order' => 0,
        ];
    }

    public function forScoreRange(int $minScore, ?int $maxScore = null): static
    {
        return $this->state(fn (array $attributes) => [
            'min_score' => $minScore,
            'max_score' => $maxScore,
        ]);
    }

    public function withoutCta(): static
    {
        return $this->state(fn (array $attributes) => [
            'cta_text' => null,
            'cta_url' => null,
        ]);
    }
}
