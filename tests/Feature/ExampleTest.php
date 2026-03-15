<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_home_route_redirects_to_the_lobby(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('lobby.index'));
    }
}
