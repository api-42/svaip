<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BinaryCardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cards = [
            [
                'question' => 'Should the program open in a web browser?',
                'description' => 'People could use it from any device without installing anything.',
                'answers' => json_encode(['No', 'Yes']),
            ],
            [
                'question' => 'Should it also work on a computer without the internet?',
                'description' => 'Useful if you want to use it while traveling or offline.',
                'answers' => json_encode(['No', 'Yes']),
            ],
            [
                'question' => 'Should the program remember things between uses?',
                'description' => 'That way it can keep your past notes, progress, or history.',
                'answers' => json_encode(['No', 'Yes']),
            ],
            [
                'question' => 'Will more than one person use it?',
                'description' => 'Say yes if friends, coworkers, or a group should have access too.',
                'answers' => json_encode(['No', 'Yes']),
            ],
            [
                'question' => 'Should people sign in before using it?',
                'description' => 'A sign-in lets everyone keep their own space or saved work.',
                'answers' => json_encode(['No', 'Yes']),
            ],
            [
                'question' => 'Should it ask people to enter information?',
                'description' => 'For example, writing notes, logging numbers, or uploading something.',
                'answers' => json_encode(['No', 'Yes']),
            ],
            [
                'question' => 'Should it show summaries or simple overviews?',
                'description' => 'Seeing quick totals or trends can make it easier to understand progress.',
                'answers' => json_encode(['No', 'Yes']),
            ],
            [
                'question' => 'Should the program send reminders or messages?',
                'description' => 'That could help people remember tasks or get updates automatically.',
                'answers' => json_encode(['No', 'Yes']),
            ],
            [
                'question' => 'Should it do some things automatically?',
                'description' => 'It could handle routine steps so users don’t have to.',
                'answers' => json_encode(['No', 'Yes']),
            ],
            [
                'question' => 'Should it connect with other tools or websites?',
                'description' => 'This lets it share or pull information from services you already use.',
                'answers' => json_encode(['No', 'Yes']),
            ],
            [
                'question' => 'Should people be able to share what they make or collect?',
                'description' => 'Others could view or contribute to the same content.',
                'answers' => json_encode(['No', 'Yes']),
            ],
            [
                'question' => 'Should it keep working quietly in the background?',
                'description' => 'It could keep things up to date even when you’re not using it.',
                'answers' => json_encode(['No', 'Yes']),
            ],
            [
                'question' => 'Is it mainly for your own daily use?',
                'description' => 'A personal tool often stays simple and quick to open.',
                'answers' => json_encode(['No', 'Yes']),
            ],
            [
                'question' => 'Will it handle private or personal information?',
                'description' => 'That might include notes, health data, or anything sensitive to you.',
                'answers' => json_encode(['No', 'Yes']),
            ],
            [
                'question' => 'Would you like it to get smarter over time?',
                'description' => 'It could start suggesting ideas or improving how it helps you.',
                'answers' => json_encode(['No', 'Yes']),
            ],
        ];

        DB::table('cards')->insert($cards);
    }
}
