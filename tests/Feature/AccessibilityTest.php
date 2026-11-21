<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Keyboard access that the page markup must provide (checked in a browser with axe-core as well). */
class AccessibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_page_starts_with_a_skip_link_to_a_focusable_content_area(): void
    {
        $this->seed(\Database\Seeders\SiteContentSeeder::class);

        foreach (['/', '/programs', '/news', '/sdgs', '/global'] as $url) {
            $html = $this->get($url)->assertOk()->getContent();

            $this->assertMatchesRegularExpression('/<a class="ac-skip" href="#content">[^<]+<\/a>/', $html, $url);
            $this->assertStringContainsString('<div id="content" tabindex="-1">', $html, $url);
        }
    }
}
