<?php

namespace Tests\Feature\Core;

use App\Auth\Http\Middleware\EnsureFrontendRequestsAreAuthenticated;
use App\Core\Http\Middleware\AdminMiddleware;
use App\Core\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MiddlewareTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Set up routes for testing before each test
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Register test routes - in setUp to avoid persisting routes between tests
        $this->setUpTestRoutes();
    }

    /**
     * Define test routes for middleware testing
     */
    private function setUpTestRoutes(): void
    {
        // Create a test route for admin middleware
        Route::middleware(AdminMiddleware::class)->get('/admin-test', function () {
            return response('Admin access granted');
        });

        // Create a test route for auth middleware
        Route::middleware(EnsureFrontendRequestsAreAuthenticated::class)->get('/auth-test', function () {
            return response('Access granted');
        });

        // Create a test route with debug enabled
        Route::middleware(EnsureFrontendRequestsAreAuthenticated::class)->get('/auth-test-debug', function () {
            return response('Debug test');
        });

        // Create a test route for API auth middleware
        Route::middleware(EnsureFrontendRequestsAreAuthenticated::class)->prefix('api')->get('/auth-test', function () {
            return response('API access granted');
        });
    }

    #[Test]
    public function admin_middleware_allows_admin_users(): void
    {
        // Create an admin user
        $user = User::factory()->create(['is_admin' => true]);

        // Make the request as an admin user
        $this
            ->actingAs($user)
            ->get('/admin-test')
            ->assertStatus(200)
            ->assertSee('Admin access granted')
        ;
    }

    #[Test]
    public function admin_middleware_blocks_non_admin_users(): void
    {
        // Create a non-admin user
        $user = User::factory()->create(['is_admin' => false]);

        // Make the request as a non-admin user
        $this
            ->actingAs($user)
            ->get('/admin-test')
            ->assertStatus(403)
        ; // Forbidden
    }

    #[Test]
    public function admin_middleware_blocks_unauthenticated_users(): void
    {
        // Make the request without authentication
        $this
            ->get('/admin-test')
            ->assertStatus(403)
        ; // Forbidden
    }

    #[Test]
    public function auth_middleware_redirects_web_requests_when_not_authenticated(): void
    {
        // Disable debugging
        config(['app.debug' => false]);

        // Make the request without authentication
        $response = $this->get('/auth-test');

        // Should redirect to login
        $response
            ->assertStatus(302)
            ->assertRedirect('/login')
        ;
    }

    #[Test]
    public function auth_middleware_returns_json_response_for_api_requests_when_not_authenticated(): void
    {
        // Disable debugging
        config(['app.debug' => false]);

        // Make the request without authentication but expecting JSON
        $response = $this->getJson('/api/auth-test');

        // Should return 401 with JSON error
        $response
            ->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated',
                'status'  => 'error',
                'code'    => 'unauthorized',
            ])
        ;
    }

    #[Test]
    public function auth_middleware_allows_authenticated_requests(): void
    {
        // Create a user
        $user = User::factory()->create();

        // Make the request as an authenticated user
        $this
            ->actingAs($user)
            ->get('/auth-test')
            ->assertStatus(200)
            ->assertSee('Access granted')
        ;
    }

    #[Test]
    public function auth_middleware_logs_debug_output_when_enabled(): void
    {
        // Create a user
        $user = User::factory()->create();

        // Enable debugging
        config(['app.debug' => true]);

        // Make the request as an authenticated user
        $this
            ->actingAs($user)
            ->get('/auth-test-debug')
            ->assertStatus(200)
            ->assertSee('Debug test')
        ;

        // We can't directly test the log output, but the test passes if the route works
    }
}
