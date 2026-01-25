<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Flow>
 */
class FlowFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'cards' => [],
            'user_id' => \App\Models\User::factory(),
        ];
    }

    public function withCards(array $cardIds): static
    {
        return $this->state(fn (array $attributes) => [
            'cards' => $cardIds,
        ]);
    }
}
