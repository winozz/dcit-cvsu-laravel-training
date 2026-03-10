<?php

namespace App\Providers;

use App\Services\Contracts\GameCatalogContract;
use App\Services\Contracts\GameServiceContract;
use App\Services\GameCatalogService;
use App\Services\GameService;
use Illuminate\Support\ServiceProvider;

class ServiceBindingProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(GameCatalogContract::class, GameCatalogService::class);
        $this->app->bind(GameServiceContract::class, GameService::class);
    }
}
