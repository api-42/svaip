<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FlowRun>
 */
class FlowRunFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => \Illuminate\Support\Str::uuid(),
            'flow_id' => \App\Models\Flow::factory(),
            'started_at' => null,
            'completed_at' => null,
            'total_score' => 0,
            'result_template_id' => null,
        ];
    }

    public function started(): static
    {
        return $this->state(fn (array $attributes) => [
            'started_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'started_at' => now()->subMinutes(10),
            'completed_at' => now(),
        ]);
    }

    public function withScore(int $score): static
    {
        return $this->state(fn (array $attributes) => [
            'total_score' => $score,
        ]);
    }
}
