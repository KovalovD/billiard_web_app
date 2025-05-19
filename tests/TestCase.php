<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Mockery;
use Mockery\MockInterface;

abstract class TestCase extends BaseTestCase
{
    /**
     * Ensure all mocks are properly closed after each test.
     * This prevents "Cannot redeclare Mockery_*" errors when using facades.
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        // Close any Mockery containers to prevent redeclaration errors
        Mockery::close();
    }

    /**
     * Create a mock instance for the specified class that fails gracefully.
     *
     * @param  string  $class
     * @return MockInterface
     */
    protected function mockSafe(string $class): MockInterface
    {
        // Create a fresh mock instead of potentially reusing an existing one
        return Mockery::mock($class);
    }
}
