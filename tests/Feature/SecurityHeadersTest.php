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
        $response = $this->get('/news')->assertOk();
        $this->assertBasicHeaders($response);

        // Report-only by default; nothing is blocked until CSP_REPORT_ONLY=false.
        $response->assertHeaderMissing('Content-Security-Policy');
        $policy = $response->headers->get('Content-Security-Policy-Report-Only');

        $this->assertMatchesRegularExpression("/script-src 'self' 'nonce-([^']+)'/", $policy);
        $this->assertStringNotContainsString("'unsafe-eval'", $policy);
        $this->assertStringContainsString("frame-ancestors 'self'", $policy);
        $this->assertStringContainsString("object-src 'none'", $policy);

        // Every inline script on the page carries this request's nonce.
        preg_match("/'nonce-([^']+)'/", $policy, $nonce);
        $html = $response->getContent();
        $this->assertSame(substr_count($html, '<script'), substr_count($html, 'nonce="' . $nonce[1] . '"'));
        $this->assertGreaterThan(0, substr_count($html, '<script'));
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
