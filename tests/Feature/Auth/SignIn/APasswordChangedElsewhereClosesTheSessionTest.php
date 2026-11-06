<?php

declare(strict_types=1);

namespace Tests\Feature\Auth\SignIn;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * A password changed, by a link or from the profile, closes every other
 * session of the account at its next click · on the site's screens and on the
 * packages' alike.
 */
final class APasswordChangedElsewhereClosesTheSessionTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{string}> */
    public static function provideTheScreens(): array
    {
        return [
            'the dashboard' => ['admin.dashboard'],
            'the profile' => ['admin.profile'],
            'the planning' => ['booking.admin.agenda'],
            'the audience' => ['analytics.admin.overview'],
            'the marketing' => ['analytics.admin.marketing.dashboard'],
        ];
    }

    #[DataProvider('provideTheScreens')]
    public function test_a_session_opened_before_the_password_changed_is_closed(string $screen): void
    {
        $account = Admin::factory()->create();

        $this->actingAs($account, 'admin')->get(route($screen))->assertOk();

        $account->forceFill(['password' => bin2hex(random_bytes(8))])->save();

        $this->get(route($screen))->assertRedirect(route('auth.login'));
        $this->assertGuest('admin');
    }

    public function test_a_session_whose_password_did_not_change_stays(): void
    {
        $account = Admin::factory()->create();

        $this->actingAs($account, 'admin')->get(route('admin.profile'))->assertOk();
        $this->get(route('booking.admin.agenda'))->assertOk();
        $this->assertAuthenticatedAs($account, 'admin');
    }

    /**
     * Each screen checks after its guard, and a click replays it · which is
     * also what keeps the session that changes its own password open.
     */
    #[DataProvider('provideTheScreens')]
    public function test_each_screen_and_each_click_checks_the_session_after_its_guard(string $screen): void
    {
        $route = Route::getRoutes()->getByName($screen);
        $this->assertNotNull($route);

        $middleware = Route::gatherRouteMiddleware($route);
        $guard = array_search('Illuminate\Auth\Middleware\Authenticate:admin', $middleware, true);
        $check = array_search(AuthenticateSession::class, $middleware, true);

        $this->assertIsInt($guard, $screen.' asks no admin guard.');
        $this->assertIsInt($check, $screen.' does not check its session.');
        $this->assertGreaterThan($guard, $check, 'The session is checked once the guard is the admin’s.');
        $this->assertContains(AuthenticateSession::class, Livewire::getPersistentMiddleware());
    }
}
