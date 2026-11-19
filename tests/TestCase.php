<?php

namespace Tests;

use Database\Seeders\SiteContentSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** Every test database starts with the installed site settings and lists, like a fresh install. */
    protected bool $seed = true;

    protected string $seeder = SiteContentSeeder::class;
}
