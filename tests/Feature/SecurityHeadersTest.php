<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/** Security headers and Content-Security-Policy (App\Http\Middleware\SecurityHeaders). */
class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\SiteContentSeeder::class);
    }

    protected function tearDown(): void
    {
        TrustProxies::flushState();
        parent::tearDown();
    }

    private function assertBasicHeaders(TestResponse $response): void
    {
        $response->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Cross-Origin-Opener-Policy', 'same-origin')
            ->assertHeaderMissing('X-Powered-By')
            ->assertHeaderMissing('Strict-Transport-Security'); // not on plain HTTP

        $this->assertStringContainsString('camera=()', $response->headers->get('Permissions-Policy'));
        $this->assertStringContainsString('geolocation=()', $response->headers->get('Permissions-Policy'));
    }

    public function test_a_public_page_gets_the_headers_and_a_nonce_policy_its_scripts_match(): void
    {
        // The home page always has a JSON-LD block; the built site script joins it when "npm run build" has run.
        $response = $this->get('/')->assertOk();
        $this->assertBasicHeaders($response);

        // Report-only by default; nothing is blocked until CSP_REPORT_ONLY=false.
        $response->assertHeaderMissing('Content-Security-Policy');
        $policy = $response->headers->get('Content-Security-Policy-Report-Only');

        $this->assertMatchesRegularExpression("/script-src 'self' 'nonce-([^']+)'/", $policy);
        $this->assertStringNotContainsString("'unsafe-eval'", $policy);
        $this->assertStringContainsString("frame-ancestors 'self'", $policy);
        $this->assertStringContainsString("object-src 'none'", $policy);

        // Every script tag on the page carries the nonce.
        preg_match("/'nonce-([^']+)'/", $policy, $nonce);
        preg_match_all('/<script\b[^>]*>/', $response->getContent(), $tags);
        $this->assertNotEmpty($tags[0]);
        foreach ($tags[0] as $tag) {
            $this->assertStringContainsString('nonce="' . $nonce[1] . '"', $tag);
        }
    }

    public function test_a_json_route_gets_the_headers(): void
    {
        $response = $this->getJson('/search/suggestions?q=ab')->assertOk();

        $this->assertBasicHeaders($response);
        $this->assertNotNull($response->headers->get('Content-Security-Policy-Report-Only'));
    }

    public function test_the_admin_gets_the_policy_filament_needs(): void
    {
        $response = $this->get('/admin/login')->assertOk();
        $this->assertBasicHeaders($response);

        $policy = $response->headers->get('Content-Security-Policy-Report-Only');
        $this->assertStringContainsString("script-src 'self' 'unsafe-inline' 'unsafe-eval'", $policy);
        $this->assertStringNotContainsString("'nonce-", $policy); // a nonce would switch 'unsafe-inline' off
        $this->assertStringNotContainsString('ui-avatars.com', $policy);
        $this->assertStringContainsString("worker-src 'self' blob:", $policy); // upload field shrinks photos in a worker
    }

    public function test_admin_avatars_are_drawn_locally_without_an_outside_service(): void
    {
        $admin = \App\Models\User::factory()->create(['name' => '[SYSTEM] Paula Mercado']);
        $admin->assignRole('super_admin');

        $html = $this->actingAs($admin)->get('/admin')->assertOk()->getContent();
        $this->assertStringNotContainsString('ui-avatars.com', $html);
        $this->assertStringContainsString('data:image/svg+xml;base64,', $html);

        $src = (new \App\Filament\AvatarProviders\InitialsAvatar)->get($admin);
        $this->assertStringContainsString('>SP</text>', base64_decode(substr($src, strlen('data:image/svg+xml;base64,'))));
    }

    public function test_the_policy_is_enforced_when_report_only_is_off(): void
    {
        config(['security.csp_report_only' => false]);

        $this->get('/news')
            ->assertHeaderMissing('Content-Security-Policy-Report-Only')
            ->assertHeader('Content-Security-Policy');
    }

    public function test_hsts_only_over_https_in_production(): void
    {
        $this->app['env'] = 'production';

        $this->get('http://localhost/news')->assertHeaderMissing('Strict-Transport-Security');
        $this->get('https://localhost/news')->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }

    public function test_https_behind_a_trusted_proxy_counts_as_secure(): void
    {
        $this->app['env'] = 'production';
        $forwarded = ['X-Forwarded-Proto' => 'https', 'X-Forwarded-Port' => '443'];

        // Not trusted: the forwarded header is ignored.
        $this->get('/news', $forwarded)->assertHeaderMissing('Strict-Transport-Security');

        // TRUSTED_PROXIES=* (AppServiceProvider passes it to TrustProxies).
        TrustProxies::at('*');
        $this->get('/news', $forwarded)->assertHeader('Strict-Transport-Security');
    }
}
