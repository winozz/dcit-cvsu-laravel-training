<?php

namespace App\Providers;

use App\Models\ChallengeGameMatch;
use App\Policies\ChallengeGameMatchPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        ChallengeGameMatch::class => ChallengeGameMatchPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
