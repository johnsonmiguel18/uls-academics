<?php

namespace Tests\Unit;

use App\Support\Sdg;
use PHPUnit\Framework\TestCase;

/** Badge text on the 17 official SDG colors meets WCAG AA (4.5:1 for small text). */
class SdgInkTest extends TestCase
{
    private const COLORS = [
        1 => '#E5243B', 2 => '#DDA63A', 3 => '#4C9F38', 4 => '#C5192D', 5 => '#FF3A21', 6 => '#26BDE2',
        7 => '#FCC30B', 8 => '#A21942', 9 => '#FD6925', 10 => '#DD1367', 11 => '#FD9D24', 12 => '#BF8B2E',
        13 => '#3F7E44', 14 => '#0A97D9', 15 => '#56C02B', 16 => '#00689D', 17 => '#19486A',
    ];

    public function test_every_goal_badge_passes_aa(): void
    {
        foreach (self::COLORS as $goal => $color) {
            $ink = Sdg::ink($color);
            $this->assertGreaterThanOrEqual(4.5, Sdg::contrast($ink, $color), "SDG $goal: $ink on $color");
        }
    }

    public function test_white_stays_where_it_passes_and_black_replaces_dark_navy(): void
    {
        // White text on SDG 1, 4, 8, 10, 13, 16 and 17, as before.
        foreach ([1, 4, 8, 10, 13, 16, 17] as $goal) {
            $this->assertSame('#FFFFFF', Sdg::ink(self::COLORS[$goal]), "SDG $goal");
        }

        // SDG 3 had 4.71:1 and SDG 5 4.38:1 with the dark navy; black gives 6.32:1 and 5.88:1.
        $this->assertSame('#000000', Sdg::ink(self::COLORS[3]));
        $this->assertSame('#000000', Sdg::ink(self::COLORS[5]));
    }
}
