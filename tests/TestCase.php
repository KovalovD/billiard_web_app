<?php

namespace Tests;

use Closure;
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

    protected function mock($abstract, ?Closure $mock = null)
    {
        // Use a container-specific mock to avoid contamination between tests
        return $this->app->instance($abstract, Mockery::mock($abstract));
    }


    protected function spy($abstract, ?Closure $mock = null): MockInterface
    {
        return Mockery::spy($abstract);
    }

    protected function partialMock($abstract, ?Closure $mock = null): MockInterface
    {
        $abstractMock = Mockery::mock($abstract)->makePartial();

        if ($mock) {
            $mock($abstractMock);
        }

        return $this->app->instance($abstract, $abstractMock);
    }

    /**
     * Swap the given facade for a mock.
     *
     * @param  mixed  $abstract
     * @param  mixed  $instance
     * @return void
     */
    protected function swap($abstract, $instance): void
    {
        $this->app->instance($abstract, $instance);
    }
}
