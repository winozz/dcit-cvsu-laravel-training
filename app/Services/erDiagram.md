```mermaid
erDiagram
    PLAYERS {
        bigint id PK
        string username
        string session_token
        string password
        uint wins
        uint losses
        uint games_played
        timestamps
    }

    CHALLENGE_GAME_MATCHES {
        bigint id PK
        string code UNIQUE
        bigint host_player_id FK -> PLAYERS.id
        bigint guest_player_id FK -> PLAYERS.id
        string status
        bool host_done
        bool guest_done
        bool host_forfeit
        bool guest_forfeit
        string host_result
        string guest_result
        timestamp expires_at
        timestamp ended_at
        timestamps
    }

    CHALLENGE_GAME_ITEMS {
        bigint id PK
        string word
        string category
        string clue
        string difficulty
        int max_tries
        bool is_active
        int times_played
        int times_solved
        timestamps
    }

    CHALLENGE_GAME_RUNS {
        bigint id PK
        string game_slug
        string category
        string word
        int tries
        int max_tries
        bool won
        json correct
        json wrong
        json used_words
        json found_words
        timestamps
    }

    CHALLENGE_GAME_AUDITS {
        bigint id PK
        string game_slug
        string status  // e.g., depleted
        json used_words
        json found_words
        timestamps
    }

    PLAYERS ||--o{ CHALLENGE_GAME_MATCHES : hosts
    PLAYERS ||--o{ CHALLENGE_GAME_MATCHES : guests
```
