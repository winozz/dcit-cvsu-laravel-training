<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PreventConcurrentLogin
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->session()->has('player_id') && $request->session()->has('player_token')) {
            // Already authenticated; send to lobby instead of login/register.
            return redirect()->route('lobby.index');
        }

        return $next($request);
    }
}
