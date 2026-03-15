<?php

namespace App\Providers;

use App\Services\Contracts\GameCatalogInterface;
use App\Services\Contracts\GameServiceInterface;
use App\Services\SvcImplem\GameCatalogService;
use App\Services\SvcImplem\GameService;
use Illuminate\Support\ServiceProvider;

class ServiceBindingProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(GameCatalogInterface::class, GameCatalogService::class);
        $this->app->bind(GameServiceInterface::class, GameService::class);
    }
}
