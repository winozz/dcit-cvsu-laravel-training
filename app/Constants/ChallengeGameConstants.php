<?php

namespace App\Constants;

final class ChallengeGameConstants
{
    public const CATEGORIES = [
        'animals' => ['dog', 'elephant', 'penguin', 'lion', 'tiger', 'bear', 'giraffe', 'monkey', 'zebra', 'dolphin', 'eagle'],
        'countries' => ['Japan', 'Brazil', 'Norway', 'France', 'Germany', 'Canada', 'Australia', 'Mexico', 'India', 'Spain', 'Italy'],
        'programming_language' => ['PHP', 'Python', 'JavaScript', 'Java', 'C++', 'C#', 'Ruby', 'Go', 'Rust', 'TypeScript', 'Kotlin'],
    ];

    public const CLUES = [
        'animals' => [
            'dog' => 'A common domestic pet known as human\'s best friend.',
            'elephant' => 'The largest land animal with a long trunk.',
            'penguin' => 'A flightless bird that lives in cold regions.',
            'lion' => 'Often called the king of the jungle.',
            'tiger' => 'A big cat with distinctive orange and black stripes.',
            'bear' => 'A large mammal that can hibernate during winter.',
            'giraffe' => 'The tallest land animal with a very long neck.',
            'monkey' => 'A playful primate that often lives in trees.',
            'zebra' => 'An African animal known for its black-and-white stripes.',
            'dolphin' => 'An intelligent marine mammal known for clicks and whistles.',
            'eagle' => 'A bird of prey with sharp eyesight.',
        ],
        'countries' => [
            'Japan' => 'An island nation famous for sushi and Mount Fuji.',
            'Brazil' => 'Home of the Amazon rainforest and Carnival.',
            'Norway' => 'A Scandinavian country known for fjords.',
            'France' => 'European country famous for the Eiffel Tower.',
            'Germany' => 'European country known for engineering and Oktoberfest.',
            'Canada' => 'North American country known for maple syrup.',
            'Australia' => 'Country and continent known for kangaroos and the Outback.',
            'Mexico' => 'Country known for tacos, mariachi, and ancient pyramids.',
            'India' => 'South Asian country known for the Taj Mahal.',
            'Spain' => 'European country known for flamenco and paella.',
            'Italy' => 'European country known for pizza, pasta, and Rome.',
        ],
        'programming_language' => [
            'PHP' => 'A server-side scripting language widely used with Laravel.',
            'Python' => 'A beginner-friendly language known for readability.',
            'JavaScript' => 'The language of the web browser.',
            'Java' => 'A language known for the slogan: write once, run anywhere.',
            'C++' => 'An extension of C with object-oriented features.',
            'C#' => 'A language developed by Microsoft for .NET development.',
            'Ruby' => 'A language famous for the Ruby on Rails framework.',
            'Go' => 'A language by Google known for simplicity and concurrency.',
            'Rust' => 'A systems language focused on memory safety and speed.',
            'TypeScript' => 'A typed superset of JavaScript.',
            'Kotlin' => 'A modern language officially supported for Android development.',
        ],
    ];

    public const MAX_TRIES = 5;

    public const SESSION_WORD = 'word';
    public const SESSION_CATEGORY = 'category';
    public const SESSION_CORRECT = 'correct';
    public const SESSION_WRONG = 'wrong';
    public const SESSION_USED_WORDS = 'used_words';
    public const SESSION_FOUND_WORDS = 'found_words';

    public const SESSION_KEYS = [
        self::SESSION_WORD,
        self::SESSION_CATEGORY,
        self::SESSION_CORRECT,
        self::SESSION_WRONG,
    ];
}
