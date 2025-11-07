<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cards = [
            [
                'question' => 'Where should users use the program?',
                'description' => 'Some programs run as a webpage, terminal, or desktop.',
                'answers' => json_encode(['Desktop', 'Terminal', 'Web']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'Who will use the program?',
                'description' => 'Determine whether this is for yourself, a team, or public users.',
                'answers' => json_encode(['Only me', 'My team', 'Anyone online']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'What is the main purpose of the program?',
                'description' => 'Helps the system understand whether the software collects, shows, or automates information.',
                'answers' => json_encode(['Collect data', 'Show information', 'Automate tasks', 'Other']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'How often will you use it?',
                'description' => 'Define the intended usage frequency.',
                'answers' => json_encode(['Daily', 'Weekly', 'Occasionally', 'Continuously']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'Should it store information between sessions?',
                'description' => 'Some tools keep history or saved data, others reset each time.',
                'answers' => json_encode(['Yes', 'No', 'Not sure yet']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'What kind of information will it handle?',
                'description' => 'Identify the dominant data type to guide schema and defaults.',
                'answers' => json_encode(['Text', 'Numbers', 'Files or images', 'Mixed']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'Should it work automatically sometimes?',
                'description' => 'Automation level helps decide if background tasks are needed.',
                'answers' => json_encode(['Yes, automate tasks', 'No, only manual use', 'Maybe later']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'Should it be private or share data with others?',
                'description' => 'Privacy level affects storage and authentication defaults.',
                'answers' => json_encode(['Private', 'Shared with specific people', 'Public']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'Where should information be stored?',
                'description' => 'Choose between local files, databases, or cloud services.',
                'answers' => json_encode(['Local', 'Cloud', 'Both', 'Unsure']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'How should users interact with it?',
                'description' => 'Interaction method defines interface style and inputs.',
                'answers' => json_encode(['Buttons and forms', 'Command line', 'Voice or chat', 'Other']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'Should it show results or summaries?',
                'description' => 'Decide whether to include visual feedback or reporting.',
                'answers' => json_encode(['Yes, show summaries', 'No, background process', 'Maybe later']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'What kind of output is most useful?',
                'description' => 'Select preferred feedback style.',
                'answers' => json_encode(['Table or list', 'Chart or graph', 'Notification or alert', 'No output']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'Should it connect to the internet or external APIs?',
                'description' => 'Connectivity affects architecture and permissions.',
                'answers' => json_encode(['Yes', 'No', 'Maybe later']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'How complex should it be?',
                'description' => 'Decide the balance between simplicity and extensibility.',
                'answers' => json_encode(['Simple and focused', 'Expandable over time', 'Complex from start']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'Who maintains or updates it?',
                'description' => 'Clarify maintenance expectations for deployment model.',
                'answers' => json_encode(['Only me', 'Shared with others', 'Automatically by the system']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'Should it send notifications or reminders?',
                'description' => 'Helps configure background tasks and permissions.',
                'answers' => json_encode(['Yes', 'No', 'Maybe later']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'Will it require authentication or login?',
                'description' => 'Determines user management defaults.',
                'answers' => json_encode(['No login', 'Simple local login', 'External login (Google, etc.)']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'How long should the program exist?',
                'description' => 'Project lifetime influences persistence strategy.',
                'answers' => json_encode(['Short term', 'Medium term', 'Long term or continuous']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'Do you want to customize its look and feel?',
                'description' => 'Aesthetic preferences guide interface generation.',
                'answers' => json_encode(['Keep it minimal', 'Allow customization', 'Not important']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'Would you like to expand it later with AI assistance?',
                'description' => 'Determines whether to prepare for adaptive or generative behavior.',
                'answers' => json_encode(['Yes', 'No', 'Maybe']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('cards')->insert($cards);
    }
}
