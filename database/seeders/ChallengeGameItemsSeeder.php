<?php

namespace Database\Seeders;

use App\Models\ChallengeGameItem;
use Illuminate\Database\Seeder;

class ChallengeGameItemsSeeder extends Seeder
{
    public function run(): void
    {
        $maxTries = 5;
        $items = [
            // animals
            ['word' => 'dog', 'category' => 'animals', 'clue' => "A common domestic pet known as human's best friend."],
            ['word' => 'elephant', 'category' => 'animals', 'clue' => 'The largest land animal with a long trunk.'],
            ['word' => 'penguin', 'category' => 'animals', 'clue' => 'A flightless bird that lives in cold regions.'],
            ['word' => 'lion', 'category' => 'animals', 'clue' => 'Often called the king of the jungle.'],
            ['word' => 'tiger', 'category' => 'animals', 'clue' => 'A big cat with distinctive orange and black stripes.'],
            ['word' => 'bear', 'category' => 'animals', 'clue' => 'A large mammal that can hibernate during winter.'],
            ['word' => 'giraffe', 'category' => 'animals', 'clue' => 'The tallest land animal with a very long neck.'],
            ['word' => 'monkey', 'category' => 'animals', 'clue' => 'A playful primate that often lives in trees.'],
            ['word' => 'zebra', 'category' => 'animals', 'clue' => 'An African animal known for its black-and-white stripes.'],
            ['word' => 'dolphin', 'category' => 'animals', 'clue' => 'An intelligent marine mammal known for clicks and whistles.'],
            ['word' => 'eagle', 'category' => 'animals', 'clue' => 'A bird of prey with sharp eyesight.'],

            // countries
            ['word' => 'Japan', 'category' => 'countries', 'clue' => 'An island nation famous for sushi and Mount Fuji.'],
            ['word' => 'Brazil', 'category' => 'countries', 'clue' => 'Home of the Amazon rainforest and Carnival.'],
            ['word' => 'Norway', 'category' => 'countries', 'clue' => 'A Scandinavian country known for fjords.'],
            ['word' => 'France', 'category' => 'countries', 'clue' => 'European country famous for the Eiffel Tower.'],
            ['word' => 'Germany', 'category' => 'countries', 'clue' => 'European country known for engineering and Oktoberfest.'],
            ['word' => 'Canada', 'category' => 'countries', 'clue' => 'North American country known for maple syrup.'],
            ['word' => 'Australia', 'category' => 'countries', 'clue' => 'Country and continent known for kangaroos and the Outback.'],
            ['word' => 'Mexico', 'category' => 'countries', 'clue' => 'Country known for tacos, mariachi, and ancient pyramids.'],
            ['word' => 'India', 'category' => 'countries', 'clue' => 'South Asian country known for the Taj Mahal.'],
            ['word' => 'Spain', 'category' => 'countries', 'clue' => 'European country known for flamenco and paella.'],
            ['word' => 'Italy', 'category' => 'countries', 'clue' => 'European country known for pizza, pasta, and Rome.'],

            // programming_language
            ['word' => 'PHP', 'category' => 'programming_language', 'clue' => 'A server-side scripting language widely used with Laravel.'],
            ['word' => 'Python', 'category' => 'programming_language', 'clue' => 'A beginner-friendly language known for readability.'],
            ['word' => 'JavaScript', 'category' => 'programming_language', 'clue' => 'The language of the web browser.'],
            ['word' => 'Java', 'category' => 'programming_language', 'clue' => 'A language known for the slogan: write once, run anywhere.'],
            ['word' => 'C++', 'category' => 'programming_language', 'clue' => 'An extension of C with object-oriented features.'],
            ['word' => 'C#', 'category' => 'programming_language', 'clue' => 'A language developed by Microsoft for .NET development.'],
            ['word' => 'Ruby', 'category' => 'programming_language', 'clue' => 'A language famous for the Ruby on Rails framework.'],
            ['word' => 'Go', 'category' => 'programming_language', 'clue' => 'A language by Google known for simplicity and concurrency.'],
            ['word' => 'Rust', 'category' => 'programming_language', 'clue' => 'A systems language focused on memory safety and speed.'],
            ['word' => 'TypeScript', 'category' => 'programming_language', 'clue' => 'A typed superset of JavaScript.'],
            ['word' => 'Kotlin', 'category' => 'programming_language', 'clue' => 'A modern language officially supported for Android development.'],
        ];

        $payload = array_map(function (array $item) use ($maxTries) {
            return [
                'word' => $item['word'],
                'category' => $item['category'],
                'clue' => $item['clue'],
                'difficulty' => $this->difficultyForWord($item['word']),
                'max_tries' => $maxTries,
                'is_active' => true,
                'times_played' => 0,
                'times_solved' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }, $items);

        ChallengeGameItem::query()->upsert(
            $payload,
            ['word', 'category'],
            ['clue', 'difficulty', 'max_tries', 'is_active', 'updated_at']
        );
    }

    private function difficultyForWord(string $word): int
    {
        $length = mb_strlen($word);
        return match (true) {
            $length <= 5 => 1,      // Easy
            $length <= 8 => 2,      // Medium
            default => 3,           // Hard
        };
    }
}
