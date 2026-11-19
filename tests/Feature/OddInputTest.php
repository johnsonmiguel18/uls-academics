<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Public pages answer odd query strings (arrays where text is expected) without a server error. */
class OddInputTest extends TestCase
{
    use RefreshDatabase;

    public function test_array_query_values_do_not_cause_server_errors(): void
    {
        $this->seed(\Database\Seeders\SiteContentSeeder::class);

        foreach ([
            '/news?search[]=x', '/news?category[]=x', '/news?college[]=x', '/news?sdg[]=4', '/news?page[]=2',
            '/programs?q[]=x', '/programs?college[]=x',
            '/search/suggestions?q[]=xx',
        ] as $url) {
            $status = $this->get($url)->getStatusCode();
            $this->assertTrue($status < 500, "$url answered $status");
        }

        $this->postJson('/vitals', ['path' => ['x'], 'metrics' => ['LCP' => 1200]])->assertNoContent();
    }
}
