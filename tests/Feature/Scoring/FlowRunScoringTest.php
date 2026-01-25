<?php

namespace Tests\Feature\Scoring;

use App\Models\Card;
use App\Models\Flow;
use App\Models\FlowRun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlowRunScoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_flow_run_calculates_total_score_from_answers(): void
    {
        $user = User::factory()->create();
        
        $card1 = Card::factory()->withScoring(0, 10)->create();
        $card2 = Card::factory()->withScoring(0, 20)->create();
        
        $flow = Flow::factory()
            ->for($user)
            ->withCards([$card1->id, $card2->id])
            ->create();

        $flowRun = FlowRun::factory()->for($flow)->create();

        // Create results with answers
        $flowRun->results()->create(['card_id' => $card1->id, 'answer' => 1]); // 10 points
        $flowRun->results()->create(['card_id' => $card2->id, 'answer' => 1]); // 20 points

        $score = $flowRun->calculateScore();

        $this->assertEquals(30, $score);
        $this->assertEquals(30, $flowRun->fresh()->total_score);
    }

    public function test_flow_run_handles_zero_score_answers(): void
    {
        $user = User::factory()->create();
        
        $card1 = Card::factory()->withScoring(0, 10)->create();
        $card2 = Card::factory()->withScoring(0, 20)->create();
        
        $flow = Flow::factory()
            ->for($user)
            ->withCards([$card1->id, $card2->id])
            ->create();

        $flowRun = FlowRun::factory()->for($flow)->create();

        $flowRun->results()->create(['card_id' => $card1->id, 'answer' => 0]); // 0 points
        $flowRun->results()->create(['card_id' => $card2->id, 'answer' => 0]); // 0 points

        $score = $flowRun->calculateScore();

        $this->assertEquals(0, $score);
    }

    public function test_flow_run_handles_mixed_scored_and_unscored_cards(): void
    {
        $user = User::factory()->create();
        
        $card1 = Card::factory()->withScoring(0, 10)->create();
        $card2 = Card::factory()->create(['scoring' => null]); // No scoring
        
        $flow = Flow::factory()
            ->for($user)
            ->withCards([$card1->id, $card2->id])
            ->create();

        $flowRun = FlowRun::factory()->for($flow)->create();

        $flowRun->results()->create(['card_id' => $card1->id, 'answer' => 1]); // 10 points
        $flowRun->results()->create(['card_id' => $card2->id, 'answer' => 1]); // 0 points (no scoring)

        $score = $flowRun->calculateScore();

        $this->assertEquals(10, $score);
    }

    public function test_flow_run_ignores_unanswered_cards(): void
    {
        $user = User::factory()->create();
        
        $card1 = Card::factory()->withScoring(0, 10)->create();
        $card2 = Card::factory()->withScoring(0, 20)->create();
        
        $flow = Flow::factory()
            ->for($user)
            ->withCards([$card1->id, $card2->id])
            ->create();

        $flowRun = FlowRun::factory()->for($flow)->create();

        $flowRun->results()->create(['card_id' => $card1->id, 'answer' => 1]); // 10 points
        $flowRun->results()->create(['card_id' => $card2->id, 'answer' => null]); // No answer

        $score = $flowRun->calculateScore();

        $this->assertEquals(10, $score);
    }

    public function test_flow_run_handles_negative_scores(): void
    {
        $user = User::factory()->create();
        
        $card1 = Card::factory()->withScoring(-5, 10)->create();
        $card2 = Card::factory()->withScoring(-10, 5)->create();
        
        $flow = Flow::factory()
            ->for($user)
            ->withCards([$card1->id, $card2->id])
            ->create();

        $flowRun = FlowRun::factory()->for($flow)->create();

        $flowRun->results()->create(['card_id' => $card1->id, 'answer' => 0]); // -5 points
        $flowRun->results()->create(['card_id' => $card2->id, 'answer' => 0]); // -10 points

        $score = $flowRun->calculateScore();

        $this->assertEquals(-15, $score);
    }

    public function test_flow_run_updates_existing_score_on_recalculation(): void
    {
        $user = User::factory()->create();
        
        $card = Card::factory()->withScoring(0, 10)->create();
        
        $flow = Flow::factory()
            ->for($user)
            ->withCards([$card->id])
            ->create();

        $flowRun = FlowRun::factory()->for($flow)->create();

        $result = $flowRun->results()->create(['card_id' => $card->id, 'answer' => 1]);

        // First calculation
        $flowRun->calculateScore();
        $this->assertEquals(10, $flowRun->fresh()->total_score);

        // Change answer
        $result->update(['answer' => 0]);

        // Recalculate
        $flowRun->calculateScore();
        $this->assertEquals(0, $flowRun->fresh()->total_score);
    }

    public function test_share_token_is_generated_automatically(): void
    {
        $flowRun = FlowRun::factory()->create();

        $this->assertNotNull($flowRun->share_token);
        $this->assertEquals(32, strlen($flowRun->share_token));
    }

    public function test_share_token_is_unique(): void
    {
        $flowRun1 = FlowRun::factory()->create();
        $flowRun2 = FlowRun::factory()->create();

        $this->assertNotEquals($flowRun1->share_token, $flowRun2->share_token);
    }

    public function test_flow_run_score_persists_to_database(): void
    {
        $user = User::factory()->create();
        
        $card = Card::factory()->withScoring(0, 25)->create();
        
        $flow = Flow::factory()
            ->for($user)
            ->withCards([$card->id])
            ->create();

        $flowRun = FlowRun::factory()->for($flow)->create();
        $flowRun->results()->create(['card_id' => $card->id, 'answer' => 1]);

        $flowRun->calculateScore();

        $this->assertDatabaseHas('flow_runs', [
            'id' => $flowRun->id,
            'total_score' => 25,
        ]);
    }
}
