<?php

namespace Tests\Feature;

use App\Filament\AvatarProviders\InitialsAvatar;
use App\Models\College;
use App\Models\News;
use App\Models\Program;
use App\Models\User;
use App\Support\Theme;
use Database\Seeders\SiteContentSeeder;
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
        $this->seed(SiteContentSeeder::class);
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
            ->assertHeader('Cross-Origin-Resource-Policy', 'same-site')
            ->assertHeader('X-Permitted-Cross-Domain-Policies', 'none')
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
            $this->assertStringContainsString('nonce="'.$nonce[1].'"', $tag);
        }
    }

    public function test_public_pages_need_no_unsafe_sources_for_an_a_plus_grade(): void
    {
        // Content on every kind of public page: SDG labels, college colors, a header photo.
        $college = College::create(['name' => 'College of Law', 'slug' => 'law', 'status' => 'published',
            'color' => '#6B21A8', 'banner_image' => 'colleges/law.jpg']);
        $program = Program::create(['name' => 'Juris Doctor', 'slug' => 'jd', 'college_id' => $college->id,
            'level' => 'graduate', 'degree_type' => 'juris-doctor', 'status' => 'published', 'is_featured' => true]);
        News::create(['title' => 'Story', 'slug' => 'story', 'category' => 'research', 'content' => 'Body', 'college_id' => $college->id,
            'status' => 'published', 'published_at' => now()->subDay(), 'sdgs' => [4, 16]]);

        foreach (['/', '/news', '/news?sdg=4', '/news/story', '/colleges/law', '/programs', '/sdgs', '/sdgs/4', '/global'] as $url) {
            $response = $this->get($url)->assertOk();
            $policy = $response->headers->get('Content-Security-Policy-Report-Only');
            $this->assertStringNotContainsString("'unsafe-inline'", $policy, $url);
            $this->assertStringNotContainsString("'unsafe-eval'", $policy, $url);
            preg_match("/style-src 'self' 'nonce-([^']+)'/", $policy, $nonce);
            $this->assertNotEmpty($nonce, "$url: style-src with the nonce");

            $html = $response->getContent();
            $this->assertDoesNotMatchRegularExpression('/<[a-z][^>]*\sstyle="/i', $html, "$url has a style attribute");
            preg_match_all('/<style\b[^>]*>/', $html, $tags);
            foreach ($tags[0] as $tag) {
                $this->assertStringContainsString('nonce="'.$nonce[1].'"', $tag, $url);
            }
        }

        // The colors and the photo arrive as classes and rules in that block.
        $this->get('/colleges/law')->assertSee(".college-{$college->id}{--college:#6B21A8;", false)
            ->assertSee('.ac-hero-split{--ac-hero-image:url("', false);
        $this->get('/sdgs/4')->assertSee('.sdg-4{--sdg:', false)->assertSee('ac-sdg-hero sdg-4', false);
    }

    public function test_a_kept_page_gets_each_visits_own_nonce_and_a_stable_etag(): void
    {
        // /sdgs has no query string: the second visit is served from the page cache.
        $first = $this->get('/sdgs')->assertHeader('X-Page-Cache', 'MISS');
        $second = $this->get('/sdgs')->assertHeader('X-Page-Cache', 'HIT');

        foreach ([$first, $second] as $response) {
            preg_match("/'nonce-([^']+)'/", $response->headers->get('Content-Security-Policy-Report-Only'), $nonce);
            $this->assertStringContainsString('<style nonce="'.$nonce[1].'"', $response->getContent());
            $this->assertStringNotContainsString('@@csp-nonce@@', $response->getContent());
        }
        $this->assertNotSame($first->headers->get('Content-Security-Policy-Report-Only'), $second->headers->get('Content-Security-Policy-Report-Only'));

        // Same page, same ETag, so browsers still get "304 Not Modified".
        $this->assertSame($first->headers->get('ETag'), $second->headers->get('ETag'));
        // The 304 brings no new policy: the browser keeps the one whose nonce matches the page it kept.
        $this->get('/sdgs', ['If-None-Match' => $second->headers->get('ETag')])->assertStatus(304)
            ->assertHeaderMissing('Content-Security-Policy-Report-Only')->assertHeaderMissing('Content-Security-Policy');
    }

    public function test_theme_rules_skip_values_that_are_not_colors_and_urls_cannot_break_out(): void
    {
        College::create(['name' => 'X', 'slug' => 'x', 'status' => 'published', 'color' => 'red;}</style><script>']);
        $this->assertStringNotContainsString('</style>', Theme::css());
        $this->assertSame('.a{--b:url("x\\"y%3C/style%3E")}', Theme::url('.a', '--b', 'x"y</style>'));
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
        $admin = User::factory()->create(['name' => '[SYSTEM] Paula Mercado']);
        $admin->assignRole('super_admin');

        $html = $this->actingAs($admin)->get('/admin')->assertOk()->getContent();
        $this->assertStringNotContainsString('ui-avatars.com', $html);
        $this->assertStringContainsString('data:image/svg+xml;base64,', $html);

        $src = (new InitialsAvatar)->get($admin);
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
