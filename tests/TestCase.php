<?php

namespace Tests;

use Database\Seeders\AccessPolicySeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Always seed access policies so tests can reference them by code.
        $this->seed(AccessPolicySeeder::class);
    }
}
