<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Card>
 */
class CardFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'question' => fake()->sentence(6) . '?',
            'description' => fake()->optional()->sentence(),
            'image_url' => fake()->optional()->imageUrl(),
            'skipable' => fake()->boolean(20),
            'options' => ['No', 'Yes'],
            'branches' => null,
            'scoring' => null,
        ];
    }

    public function withScoring(int $leftPoints = 0, int $rightPoints = 10): static
    {
        return $this->state(fn (array $attributes) => [
            'scoring' => ['0' => $leftPoints, '1' => $rightPoints],
        ]);
    }

    public function withBranching(?int $leftBranch, ?int $rightBranch): static
    {
        return $this->state(fn (array $attributes) => [
            'branches' => ['0' => $leftBranch, '1' => $rightBranch],
        ]);
    }
}
