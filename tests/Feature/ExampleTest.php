<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_panel_root_redirects_to_panel_dashboard(): void
    {
        $response = $this->get('/panel');

        $response->assertRedirect('/panel/dashboard');
    }
}
