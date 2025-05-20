<?php

namespace tests;

use Closure;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Mockery;
use Mockery\MockInterface;

abstract class TestCase extends BaseTestCase
{
    /**
     * Map of internal class method parameters
     *
     * @var array
     */
    protected static array $internalClassMethodParamMap = [];

    /**
     * Ensure all mocks are properly closed after each test.
     * This prevents "Cannot redeclare Mockery_*" errors when using facades.
     */
    protected function tearDown(): void
    {
        // Close any Mockery containers before parent tearDown to prevent redeclaration errors
        if ($container = Mockery::getContainer()) {
            $this->addToAssertionCount($container->mockery_getExpectationCount());
        }

        Mockery::close();

        // Clear facade mocks
        $this->setInternalClassMethodParamMap([]);

        parent::tearDown();
    }

    /**
     * Mock a class or interface.
     *
     * @param  string  $abstract
     * @param  Closure|null  $mock
     * @return MockInterface
     */
    protected function mock($abstract, ?Closure $mock = null): MockInterface
    {
        $mockObject = Mockery::mock($abstract);

        if ($mock) {
            $mock($mockObject);
        }

        $this->app->instance($abstract, $mockObject);

        return $mockObject;
    }

    /**
     * Create a spy for a class or interface.
     *
     * @param  string  $abstract
     * @param  Closure|null  $mock
     * @return MockInterface
     */
    protected function spy($abstract, ?Closure $mock = null): MockInterface
    {
        $spyObject = Mockery::spy($abstract);

        if ($mock) {
            $mock($spyObject);
        }

        $this->app->instance($abstract, $spyObject);

        return $spyObject;
    }

    /**
     * Create a partial mock for a class.
     *
     * @param  string  $abstract
     * @param  Closure|null  $mock
     * @return MockInterface
     */
    protected function partialMock($abstract, ?Closure $mock = null): MockInterface
    {
        $mockObject = Mockery::mock($abstract)->makePartial();

        if ($mock) {
            $mock($mockObject);
        }

        $this->app->instance($abstract, $mockObject);

        return $mockObject;
    }

    /**
     * Swap the given facade for a mock.
     *
     * @param  string  $abstract
     * @param  mixed  $instance
     * @return void
     */
    protected function swap($abstract, $instance): void
    {
        $this->app->instance($abstract, $instance);
    }

    /**
     * Mock a facade in a way that prevents redeclaration errors.
     *
     * @param  string  $facade  The facade class name
     * @param  Closure|null  $mock
     * @return MockInterface
     */
    protected function mockFacade(string $facade, ?Closure $mock = null): MockInterface
    {
        // Create a unique alias to prevent redeclaration issues
        $alias = 'MockFacade'.md5(uniqid('', true));

        // Create the mock with the unique alias
        $mockObject = Mockery::mock($alias);

        if ($mock) {
            $mock($mockObject);
        }

        // Replace the facade root
        $facade::swap($mockObject);

        return $mockObject;
    }

    /**
     * Set the internal class method parameter map
     *
     * @param  mixed  $class  Either class name or array of mappings
     * @param  string|null  $method  Method name (optional)
     * @param  array|null  $map  Parameter map (optional)
     * @return void
     */
    public function setInternalClassMethodParamMap(mixed $class, ?string $method = null, ?array $map = null): void
    {
        // If only one parameter is provided and it's an array, replace the entire map
        if (is_array($class) && $method === null && $map === null) {
            static::$internalClassMethodParamMap = $class;
            return;
        }

        // Otherwise, update specific class/method mapping
        if (!isset(static::$internalClassMethodParamMap[$class])) {
            static::$internalClassMethodParamMap[$class] = [];
        }

        static::$internalClassMethodParamMap[$class][$method] = $map;
    }

    /**
     * Create a mock instance of a facade that won't cause redeclaration issues
     *
     * @param  string  $facadeClass  The facade class to mock (e.g., Auth::class)
     * @param  callable|null  $expectations  Optional callback to set expectations
     * @return MockInterface
     */
    protected function mockStaticFacade(string $facadeClass, ?callable $expectations = null): MockInterface
    {
        // Create a unique alias with valid class name characters
        $alias = 'MockFacade'.md5(uniqid('', true));

        // Store the mapping to avoid conflicts
        $this->setInternalClassMethodParamMap('mockery_facades', $facadeClass, [$alias]);

        // Create the mock with a valid class name format
        $mock = Mockery::mock('alias:'.$alias);

        // Register expectations if provided
        if ($expectations) {
            $expectations($mock);
        }

        // Replace the facade root instance
        if (method_exists($facadeClass, 'swap')) {
            $facadeClass::swap($mock);
        } else {
            // Alternative approach if swap is not available
            $facadeClass::shouldReceive()->andReturn($mock);
        }

        return $mock;
    }

    /**
     * Get the internal class method parameter map
     *
     * @param  string|null  $class
     * @param  string|null  $method
     * @return array|mixed|null
     */
    public function getInternalClassMethodParamMap(?string $class = null, ?string $method = null): mixed
    {
        // Return entire map if no parameters specified
        if ($class === null) {
            return static::$internalClassMethodParamMap;
        }

        // Return map for specific class if only class specified
        if ($method === null) {
            return static::$internalClassMethodParamMap[$class] ?? null;
        }

        // Return map for specific method
        return static::$internalClassMethodParamMap[$class][$method] ?? null;
    }
}
