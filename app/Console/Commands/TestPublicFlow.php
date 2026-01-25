<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestPublicFlow extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:public-flow';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a test public flow';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $user = \App\Models\User::first();

        if (!$user) {
            $this->error('No users found. Please create a user first.');
            return 1;
        }

        $card1 = \App\Models\Card::create([
            'question' => 'Question 1',
            'description' => 'Do you like public flows?',
            'options' => ['No', 'Yes'],
            'scoring' => ['0' => 0, '1' => 10],
        ]);

        $card2 = \App\Models\Card::create([
            'question' => 'Question 2',
            'description' => 'Would you use this feature?',
            'options' => ['Maybe', 'Definitely'],
            'scoring' => ['0' => 5, '1' => 15],
        ]);

        $flow = \App\Models\Flow::create([
            'name' => 'Public Test Flow',
            'description' => 'A test flow to try public access',
            'user_id' => $user->id,
            'cards' => [$card1->id, $card2->id],
            'is_public' => true,
        ]);

        $this->info('✅ Flow created!');
        $this->line('   ID: ' . $flow->id);
        $this->line('   Slug: ' . $flow->public_slug);
        $this->line('   Public URL: ' . $flow->publicUrl());
        $this->newLine();
        $this->info('🌐 Access at: http://localhost:8001/p/' . $flow->public_slug);

        return 0;
    }
}
