<?php

namespace App\Providers;

use App\Events\PlayerVerificationRequested;
use App\Listeners\SendPlayerVerificationOtp;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        PlayerVerificationRequested::class => [
            SendPlayerVerificationOtp::class,
        ],
    ];
}
