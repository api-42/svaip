<?php

namespace Tests\Feature\Scoring;

use App\Models\Card;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CardScoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_card_can_be_created_with_scoring(): void
    {
        $card = Card::factory()->withScoring(0, 10)->create();

        $this->assertDatabaseHas('cards', [
            'id' => $card->id,
        ]);

        $this->assertEquals(['0' => 0, '1' => 10], $card->scoring);
    }

    public function test_card_can_be_created_without_scoring(): void
    {
        $card = Card::factory()->create();

        $this->assertNull($card->scoring);
    }

    public function test_card_returns_correct_score_for_answer(): void
    {
        $card = Card::factory()->withScoring(5, 15)->create();

        $this->assertEquals(5, $card->getScore(0));
        $this->assertEquals(15, $card->getScore(1));
    }

    public function test_card_returns_zero_for_missing_scoring(): void
    {
        $card = Card::factory()->create(['scoring' => null]);

        $this->assertEquals(0, $card->getScore(0));
        $this->assertEquals(0, $card->getScore(1));
    }

    public function test_card_returns_zero_for_invalid_answer_index(): void
    {
        $card = Card::factory()->withScoring(10, 20)->create();

        $this->assertEquals(0, $card->getScore(2));
        $this->assertEquals(0, $card->getScore(99));
    }

    public function test_card_scoring_can_use_negative_values(): void
    {
        $card = Card::factory()->withScoring(-10, 5)->create();

        $this->assertEquals(-10, $card->getScore(0));
        $this->assertEquals(5, $card->getScore(1));
    }

    public function test_card_scoring_can_have_same_values_for_both_options(): void
    {
        $card = Card::factory()->withScoring(10, 10)->create();

        $this->assertEquals(10, $card->getScore(0));
        $this->assertEquals(10, $card->getScore(1));
    }

    public function test_card_scoring_is_stored_as_json(): void
    {
        $card = Card::factory()->withScoring(0, 100)->create();

        $this->assertIsArray($card->scoring);
        $this->assertArrayHasKey('0', $card->scoring);
        $this->assertArrayHasKey('1', $card->scoring);
    }

    public function test_multiple_cards_can_have_different_scoring(): void
    {
        $card1 = Card::factory()->withScoring(1, 2)->create();
        $card2 = Card::factory()->withScoring(10, 20)->create();
        $card3 = Card::factory()->create(['scoring' => null]);

        $this->assertEquals(2, $card1->getScore(1));
        $this->assertEquals(20, $card2->getScore(1));
        $this->assertEquals(0, $card3->getScore(1));
    }
}
