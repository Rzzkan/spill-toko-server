<?php

namespace Tests;

use App\Services\Settings;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Settings::flush();   // cache statis Settings tidak boleh bocor antar tes
    }
}
