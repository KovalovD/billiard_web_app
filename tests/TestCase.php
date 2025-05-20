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
     * Create a mock instance for the specified class.
     *
     * @param  string  $class
     * @return MockInterface
     */
    protected function mock(string $class): MockInterface
    {
        // Use a container-specific mock to avoid contamination between tests
        return $this->app->instance($class, Mockery::mock($class));
    }

    /**
     * Create a spy instance for the specified class.
     *
     * @param  string  $class
     * @return MockInterface
     */
    protected function spy(string $class): MockInterface
    {
        return Mockery::spy($class);
    }

    /**
     * Create a partial mock for the specified class.
     *
     * @param  string  $class
     * @param  callable|null  $expectations
     * @return MockInterface
     */
    protected function partialMock(string $class, callable $expectations = null): MockInterface
    {
        $mock = Mockery::mock($class)->makePartial();

        if ($expectations) {
            $expectations($mock);
        }

        return $this->app->instance($class, $mock);
    }

    /**
     * Swap the given facade for a mock.
     *
     * @param  string  $facade
     * @param  object  $mock
     * @return void
     */
    protected function swap(string $facade, $mock): void
    {
        $this->app->instance($facade, $mock);
    }
}
