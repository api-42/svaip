<?php

namespace Tests\Feature\Scoring;

use App\Models\Card;
use App\Models\Flow;
use App\Models\FlowRun;
use App\Models\ResultTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicResultsTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_flow_run_results_are_publicly_accessible(): void
    {
        $user = User::factory()->create();
        $card = Card::factory()->withScoring(0, 10)->create();
        
        $flow = Flow::factory()
            ->for($user)
            ->withCards([$card->id])
            ->create(['name' => 'Test Flow']);

        $flowRun = FlowRun::factory()
            ->for($flow)
            ->completed()
            ->withScore(10)
            ->create();

        $response = $this->get(route('results.show', $flowRun->share_token));

        $response->assertStatus(200);
        $response->assertViewIs('results.show');
        $response->assertSee('Test Flow');
        $response->assertSee('10'); // Score
    }

    public function test_results_page_displays_result_template_content(): void
    {
        $user = User::factory()->create();
        $card = Card::factory()->withScoring(0, 50)->create();
        
        $flow = Flow::factory()
            ->for($user)
            ->withCards([$card->id])
            ->create();

        $template = ResultTemplate::factory()
            ->for($flow)
            ->forScoreRange(40, null)
            ->create([
                'title' => 'Expert Level!',
                'content' => 'You have mastered this topic.',
            ]);

        $flowRun = FlowRun::factory()
            ->for($flow)
            ->completed()
            ->withScore(50)
            ->create(['result_template_id' => $template->id]);

        $response = $this->get(route('results.show', $flowRun->share_token));

        $response->assertStatus(200);
        $response->assertSee('Expert Level!');
        $response->assertSee('You have mastered this topic.');
    }

    public function test_results_page_displays_cta_button_when_present(): void
    {
        $user = User::factory()->create();
        $flow = Flow::factory()->for($user)->create();

        $template = ResultTemplate::factory()
            ->for($flow)
            ->create([
                'cta_text' => 'Apply Now',
                'cta_url' => 'https://example.com/apply',
            ]);

        $flowRun = FlowRun::factory()
            ->for($flow)
            ->completed()
            ->create(['result_template_id' => $template->id]);

        $response = $this->get(route('results.show', $flowRun->share_token));

        $response->assertStatus(200);
        $response->assertSee('Apply Now');
        $response->assertSee('https://example.com/apply');
    }

    public function test_incomplete_flow_run_results_are_not_accessible(): void
    {
        $flowRun = FlowRun::factory()->create(['completed_at' => null]);

        $response = $this->get(route('results.show', $flowRun->share_token));

        $response->assertStatus(404);
    }

    public function test_invalid_share_token_returns_404(): void
    {
        $response = $this->get(route('results.show', 'invalid-token-12345'));

        $response->assertStatus(404);
    }

    public function test_results_page_displays_share_url(): void
    {
        $user = User::factory()->create();
        $flow = Flow::factory()->for($user)->create();
        $flowRun = FlowRun::factory()->for($flow)->completed()->create();

        $response = $this->get(route('results.show', $flowRun->share_token));

        $response->assertStatus(200);
        $response->assertSee(route('results.show', $flowRun->share_token));
    }

    public function test_results_page_displays_completion_date(): void
    {
        $user = User::factory()->create();
        $flow = Flow::factory()->for($user)->create();
        
        $completionDate = now()->subDays(2);
        $flowRun = FlowRun::factory()
            ->for($flow)
            ->create([
                'started_at' => $completionDate->copy()->subMinutes(10),
                'completed_at' => $completionDate,
            ]);

        $response = $this->get(route('results.show', $flowRun->share_token));

        $response->assertStatus(200);
        $response->assertSee($completionDate->format('F j, Y'));
    }

    public function test_results_page_works_without_result_template(): void
    {
        $user = User::factory()->create();
        $flow = Flow::factory()->for($user)->create(['name' => 'Simple Flow']);
        
        $flowRun = FlowRun::factory()
            ->for($flow)
            ->completed()
            ->withScore(25)
            ->create(['result_template_id' => null]);

        $response = $this->get(route('results.show', $flowRun->share_token));

        $response->assertStatus(200);
        $response->assertSee('Simple Flow');
        $response->assertSee('25');
        $response->assertSee('Thank you for completing this flow!');
    }

    public function test_results_page_displays_result_template_image_when_present(): void
    {
        $user = User::factory()->create();
        $flow = Flow::factory()->for($user)->create();

        $template = ResultTemplate::factory()
            ->for($flow)
            ->create(['image_url' => 'https://example.com/trophy.jpg']);

        $flowRun = FlowRun::factory()
            ->for($flow)
            ->completed()
            ->create(['result_template_id' => $template->id]);

        $response = $this->get(route('results.show', $flowRun->share_token));

        $response->assertStatus(200);
        $response->assertSee('https://example.com/trophy.jpg');
    }

    public function test_results_page_is_accessible_without_authentication(): void
    {
        $user = User::factory()->create();
        $flow = Flow::factory()->for($user)->create();
        $flowRun = FlowRun::factory()->for($flow)->completed()->create();

        // Ensure we're not authenticated
        $this->assertGuest();

        $response = $this->get(route('results.show', $flowRun->share_token));

        $response->assertStatus(200);
    }

    public function test_share_token_route_is_named_correctly(): void
    {
        $this->assertTrue(
            \Illuminate\Support\Facades\Route::has('results.show')
        );
    }
}
