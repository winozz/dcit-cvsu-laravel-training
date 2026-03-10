<?php

namespace Database\Factories;

use App\Models\ChallengeGameRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChallengeGameRun>
 */
class ChallengeGameRunFactory extends Factory
{
    protected $model = ChallengeGameRun::class;

    public function definition(): array
    {
        return [
            'game_slug' => $this->faker->word(),
            'category' => $this->faker->randomElement(['animals', 'countries', 'sports']),
            'word' => $this->faker->word(),
            'tries' => $this->faker->numberBetween(0, 10),
            'max_tries' => $this->faker->numberBetween(5, 10),
            'won' => $this->faker->boolean(),
            'correct' => [$this->faker->randomLetter()],
            'wrong' => [$this->faker->randomLetter()],
            'used_words' => [$this->faker->word()],
            'found_words' => [$this->faker->word()],
        ];
    }
}
