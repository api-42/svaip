<?php

namespace Tests\Feature\Scoring;

use App\Models\Card;
use App\Models\Flow;
use App\Models\FlowRun;
use App\Models\ResultTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResultTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_result_template_matches_score_within_range(): void
    {
        $flow = Flow::factory()->create();
        $template = ResultTemplate::factory()
            ->for($flow)
            ->forScoreRange(10, 20)
            ->create();

        $this->assertTrue($template->matchesScore(10));
        $this->assertTrue($template->matchesScore(15));
        $this->assertTrue($template->matchesScore(20));
        $this->assertFalse($template->matchesScore(9));
        $this->assertFalse($template->matchesScore(21));
    }

    public function test_result_template_with_null_max_score_matches_any_high_score(): void
    {
        $flow = Flow::factory()->create();
        $template = ResultTemplate::factory()
            ->for($flow)
            ->forScoreRange(50, null)
            ->create();

        $this->assertTrue($template->matchesScore(50));
        $this->assertTrue($template->matchesScore(100));
        $this->assertTrue($template->matchesScore(1000));
        $this->assertFalse($template->matchesScore(49));
    }

    public function test_flow_run_assigns_correct_result_template(): void
    {
        $user = User::factory()->create();
        $card = Card::factory()->withScoring(0, 30)->create();
        
        $flow = Flow::factory()
            ->for($user)
            ->withCards([$card->id])
            ->create();

        // Create result templates
        $lowTemplate = ResultTemplate::factory()
            ->for($flow)
            ->forScoreRange(0, 15)
            ->create(['title' => 'Low Score']);

        $highTemplate = ResultTemplate::factory()
            ->for($flow)
            ->forScoreRange(16, null)
            ->create(['title' => 'High Score']);

        // Create flow run with high score
        $flowRun = FlowRun::factory()->for($flow)->create();
        $flowRun->results()->create(['card_id' => $card->id, 'answer' => 1]); // 30 points

        $flowRun->calculateScore();
        $template = $flowRun->assignResultTemplate();

        $this->assertNotNull($template);
        $this->assertEquals('High Score', $template->title);
        $this->assertEquals($highTemplate->id, $flowRun->fresh()->result_template_id);
    }

    public function test_multiple_result_templates_prioritize_by_order(): void
    {
        $user = User::factory()->create();
        $card = Card::factory()->withScoring(0, 15)->create();
        
        $flow = Flow::factory()
            ->for($user)
            ->withCards([$card->id])
            ->create();

        // Create overlapping templates with different order
        $template1 = ResultTemplate::factory()
            ->for($flow)
            ->forScoreRange(10, 20)
            ->create(['title' => 'First Template', 'order' => 0]);

        $template2 = ResultTemplate::factory()
            ->for($flow)
            ->forScoreRange(10, 20)
            ->create(['title' => 'Second Template', 'order' => 1]);

        $flowRun = FlowRun::factory()->for($flow)->create();
        $flowRun->results()->create(['card_id' => $card->id, 'answer' => 1]); // 15 points

        $flowRun->calculateScore();
        $template = $flowRun->assignResultTemplate();

        // Should match first template (lower order value)
        $this->assertEquals('First Template', $template->title);
    }

    public function test_flow_run_with_no_matching_template_returns_null(): void
    {
        $user = User::factory()->create();
        $card = Card::factory()->withScoring(0, 100)->create();
        
        $flow = Flow::factory()
            ->for($user)
            ->withCards([$card->id])
            ->create();

        // Create template that won't match
        ResultTemplate::factory()
            ->for($flow)
            ->forScoreRange(0, 50)
            ->create();

        $flowRun = FlowRun::factory()->for($flow)->create();
        $flowRun->results()->create(['card_id' => $card->id, 'answer' => 1]); // 100 points

        $flowRun->calculateScore();
        $template = $flowRun->assignResultTemplate();

        $this->assertNull($template);
        $this->assertNull($flowRun->fresh()->result_template_id);
    }

    public function test_result_template_can_have_cta_button(): void
    {
        $flow = Flow::factory()->create();
        $template = ResultTemplate::factory()
            ->for($flow)
            ->create([
                'cta_text' => 'Apply Now',
                'cta_url' => 'https://example.com/apply',
            ]);

        $this->assertEquals('Apply Now', $template->cta_text);
        $this->assertEquals('https://example.com/apply', $template->cta_url);
    }

    public function test_result_template_can_have_no_cta_button(): void
    {
        $flow = Flow::factory()->create();
        $template = ResultTemplate::factory()
            ->for($flow)
            ->withoutCta()
            ->create();

        $this->assertNull($template->cta_text);
        $this->assertNull($template->cta_url);
    }

    public function test_result_template_can_have_image(): void
    {
        $flow = Flow::factory()->create();
        $template = ResultTemplate::factory()
            ->for($flow)
            ->create(['image_url' => 'https://example.com/image.jpg']);

        $this->assertEquals('https://example.com/image.jpg', $template->image_url);
    }

    public function test_result_template_belongs_to_flow(): void
    {
        $flow = Flow::factory()->create();
        $template = ResultTemplate::factory()->for($flow)->create();

        $this->assertEquals($flow->id, $template->flow->id);
    }

    public function test_flow_can_have_multiple_result_templates(): void
    {
        $flow = Flow::factory()->create();
        
        ResultTemplate::factory()->for($flow)->forScoreRange(0, 33)->create(['title' => 'Low']);
        ResultTemplate::factory()->for($flow)->forScoreRange(34, 66)->create(['title' => 'Medium']);
        ResultTemplate::factory()->for($flow)->forScoreRange(67, null)->create(['title' => 'High']);

        $this->assertCount(3, $flow->resultTemplates);
    }

    public function test_assign_result_template_also_calculates_score(): void
    {
        $user = User::factory()->create();
        $card = Card::factory()->withScoring(0, 50)->create();
        
        $flow = Flow::factory()
            ->for($user)
            ->withCards([$card->id])
            ->create();

        ResultTemplate::factory()
            ->for($flow)
            ->forScoreRange(40, null)
            ->create(['title' => 'High Score']);

        $flowRun = FlowRun::factory()->for($flow)->create();
        $flowRun->results()->create(['card_id' => $card->id, 'answer' => 1]); // 50 points

        // Don't manually calculate score, just assign template
        $template = $flowRun->assignResultTemplate();

        $this->assertEquals(50, $flowRun->fresh()->total_score);
        $this->assertNotNull($template);
    }
}
