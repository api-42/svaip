<?php

namespace Tests\Feature\Analytics;

use App\Models\Card;
use App\Models\Flow;
use App\Models\FlowRun;
use App\Models\ResultTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_access_analytics_page_for_own_flow(): void
    {
        $user = User::factory()->create();
        $flow = Flow::factory()->for($user)->create();

        $response = $this->actingAs($user)->get(route('flow.analytics', $flow->id));

        $response->assertStatus(200);
        $response->assertViewIs('flow.analytics');
        $response->assertViewHas('flow');
    }

    public function test_user_cannot_access_analytics_for_other_users_flow(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $flow = Flow::factory()->for($owner)->create();

        $response = $this->actingAs($otherUser)->get(route('flow.analytics', $flow->id));

        $response->assertStatus(403);
    }

    public function test_guest_cannot_access_analytics(): void
    {
        $user = User::factory()->create();
        $flow = Flow::factory()->for($user)->create();

        $response = $this->get(route('flow.analytics', $flow->id));

        $response->assertRedirect(route('login'));
    }

    public function test_analytics_data_returns_overview_metrics(): void
    {
        $user = User::factory()->create();
        $flow = Flow::factory()->for($user)->create();

        // Create some completed runs
        FlowRun::factory()->for($flow)->completed()->create(['total_score' => 10]);
        FlowRun::factory()->for($flow)->completed()->create(['total_score' => 20]);
        
        // Create an abandoned run
        FlowRun::factory()->for($flow)->create(['completed_at' => null]);

        $response = $this->actingAs($user)->get(route('flow.analytics.data', $flow->id));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'overview' => [
                'total_runs',
                'completed_runs',
                'abandoned_runs',
                'completion_rate',
                'average_score',
                'average_completion_time_seconds',
                'average_completion_time_formatted',
                'unique_visitors',
            ],
            'score_distribution',
            'trends',
            'per_card',
        ]);

        $data = $response->json();
        $this->assertEquals(3, $data['overview']['total_runs']);
        $this->assertEquals(2, $data['overview']['completed_runs']);
        $this->assertEquals(1, $data['overview']['abandoned_runs']);
        $this->assertEquals(66.7, $data['overview']['completion_rate']);
        $this->assertEquals(15, $data['overview']['average_score']);
    }

    public function test_analytics_filters_by_date_range(): void
    {
        $user = User::factory()->create();
        $flow = Flow::factory()->for($user)->create();

        // Create runs on different dates
        FlowRun::factory()->for($flow)->completed()->create([
            'created_at' => now()->subDays(10),
            'total_score' => 10,
        ]);
        FlowRun::factory()->for($flow)->completed()->create([
            'created_at' => now()->subDays(5),
            'total_score' => 20,
        ]);
        FlowRun::factory()->for($flow)->completed()->create([
            'created_at' => now()->subDay(),
            'total_score' => 30,
        ]);

        // Filter to last 7 days
        $response = $this->actingAs($user)->get(route('flow.analytics.data', [
            'id' => $flow->id,
            'start_date' => now()->subDays(7)->toDateString(),
        ]));

        $data = $response->json();
        $this->assertEquals(2, $data['overview']['total_runs']);
        $this->assertEquals(25, $data['overview']['average_score']); // (20 + 30) / 2
    }

    public function test_analytics_filters_by_completion_status(): void
    {
        $user = User::factory()->create();
        $flow = Flow::factory()->for($user)->create();

        FlowRun::factory()->for($flow)->completed()->count(3)->create();
        FlowRun::factory()->for($flow)->count(2)->create(['completed_at' => null]);

        // Filter completed only
        $response = $this->actingAs($user)->get(route('flow.analytics.data', [
            'id' => $flow->id,
            'status' => 'completed',
        ]));

        $data = $response->json();
        $this->assertEquals(3, $data['overview']['total_runs']);
        $this->assertEquals(3, $data['overview']['completed_runs']);

        // Filter abandoned only
        $response = $this->actingAs($user)->get(route('flow.analytics.data', [
            'id' => $flow->id,
            'status' => 'abandoned',
        ]));

        $data = $response->json();
        $this->assertEquals(2, $data['overview']['total_runs']);
        $this->assertEquals(0, $data['overview']['completed_runs']);
    }

    public function test_analytics_filters_by_result_template(): void
    {
        $user = User::factory()->create();
        $flow = Flow::factory()->for($user)->create();

        $template1 = ResultTemplate::factory()->for($flow)->create(['title' => 'Result A']);
        $template2 = ResultTemplate::factory()->for($flow)->create(['title' => 'Result B']);

        FlowRun::factory()->for($flow)->completed()->count(2)->create(['result_template_id' => $template1->id]);
        FlowRun::factory()->for($flow)->completed()->create(['result_template_id' => $template2->id]);

        $response = $this->actingAs($user)->get(route('flow.analytics.data', [
            'id' => $flow->id,
            'result_template_id' => $template1->id,
        ]));

        $data = $response->json();
        $this->assertEquals(2, $data['overview']['total_runs']);
    }

    public function test_analytics_calculates_score_distribution(): void
    {
        $user = User::factory()->create();
        $flow = Flow::factory()->for($user)->create();

        FlowRun::factory()->for($flow)->completed()->create(['total_score' => 10]);
        FlowRun::factory()->for($flow)->completed()->create(['total_score' => 50]);
        FlowRun::factory()->for($flow)->completed()->create(['total_score' => 90]);

        $response = $this->actingAs($user)->get(route('flow.analytics.data', $flow->id));

        $data = $response->json();
        $this->assertArrayHasKey('histogram', $data['score_distribution']);
        $this->assertEquals(10, $data['score_distribution']['min']);
        $this->assertEquals(90, $data['score_distribution']['max']);
        $this->assertEquals(50, $data['score_distribution']['median']);
    }

    public function test_analytics_tracks_completions_over_time(): void
    {
        $user = User::factory()->create();
        $flow = Flow::factory()->for($user)->create();

        FlowRun::factory()->for($flow)->completed()->create([
            'completed_at' => now()->subDays(2),
        ]);
        FlowRun::factory()->for($flow)->completed()->count(2)->create([
            'completed_at' => now()->subDay(),
        ]);
        FlowRun::factory()->for($flow)->completed()->create([
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('flow.analytics.data', $flow->id));

        $data = $response->json();
        $this->assertIsArray($data['trends']['completions_by_date']);
        $this->assertCount(3, $data['trends']['completions_by_date']);
    }

    public function test_analytics_provides_per_card_breakdown(): void
    {
        $user = User::factory()->create();
        
        $card1 = Card::factory()->withScoring(0, 10)->create(['question' => 'Question 1']);
        $card2 = Card::factory()->withScoring(0, 20)->create(['question' => 'Question 2']);
        
        $flow = Flow::factory()
            ->for($user)
            ->withCards([$card1->id, $card2->id])
            ->create();

        $run1 = FlowRun::factory()->for($flow)->completed()->create();
        $run1->results()->create(['card_id' => $card1->id, 'answer' => 1]);
        $run1->results()->create(['card_id' => $card2->id, 'answer' => 0]);

        $run2 = FlowRun::factory()->for($flow)->completed()->create();
        $run2->results()->create(['card_id' => $card1->id, 'answer' => 0]);
        $run2->results()->create(['card_id' => $card2->id, 'answer' => 1]);

        $response = $this->actingAs($user)->get(route('flow.analytics.data', $flow->id));

        $data = $response->json();
        $this->assertCount(2, $data['per_card']);
        
        $card1Data = collect($data['per_card'])->firstWhere('card_question', 'Question 1');
        $this->assertEquals(2, $card1Data['total_answered']);
        $this->assertEquals(1, $card1Data['answer_0_count']);
        $this->assertEquals(1, $card1Data['answer_1_count']);
    }

    public function test_analytics_handles_empty_flow(): void
    {
        $user = User::factory()->create();
        $flow = Flow::factory()->for($user)->create();

        $response = $this->actingAs($user)->get(route('flow.analytics.data', $flow->id));

        $response->assertStatus(200);
        $data = $response->json();
        
        $this->assertEquals(0, $data['overview']['total_runs']);
        $this->assertEquals(0, $data['overview']['completed_runs']);
        $this->assertEquals(0, $data['overview']['average_score']);
    }

    public function test_analytics_validates_date_range(): void
    {
        $user = User::factory()->create();
        $flow = Flow::factory()->for($user)->create();

        $response = $this->actingAs($user)->get(route('flow.analytics.data', [
            'id' => $flow->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->subDay()->toDateString(), // End before start
        ]));

        $response->assertStatus(302); // Validation error redirects
    }
}
