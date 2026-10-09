<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_homepage_renders_the_landing_for_guests(): void
    {
        $response = $this->get('/');

        $response->assertOk();
    }
}
