<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckFlows extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:flows';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check flows in database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $flows = \App\Models\Flow::all();
        
        $this->info('Total flows: ' . $flows->count());
        $this->newLine();
        
        foreach ($flows as $flow) {
            $this->line('Flow #' . $flow->id . ': ' . $flow->name);
            $this->line('  User ID: ' . ($flow->user_id ?? 'NULL'));
            $this->line('  Cards: ' . count($flow->cards));
            $this->line('  Public: ' . ($flow->is_public ? 'Yes' : 'No'));
            $this->line('  Slug: ' . ($flow->public_slug ?? 'none'));
            $this->newLine();
        }
        
        $users = \App\Models\User::all();
        $this->info('Total users: ' . $users->count());
        foreach ($users as $user) {
            $this->line('User #' . $user->id . ': ' . $user->email);
            $this->line('  Flows: ' . $user->flows()->count());
        }
        
        return 0;
    }
}
