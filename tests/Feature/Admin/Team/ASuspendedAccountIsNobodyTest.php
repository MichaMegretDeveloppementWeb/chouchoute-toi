<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Team;

use App\Livewire\Admin\Auth\LoginForm;
use App\Models\Admin;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Suspending an account shuts every door at once · the sign-in form, a session
 * already open, and the site and both packages behind `auth:admin`.
 */
final class ASuspendedAccountIsNobodyTest extends TestCase
{
    use RefreshDatabase;

    /** Where the guard keeps who is signed in, the session being all a browser brings back. */
    private const string SESSION_KEY = 'login_admin_59ba36addc2b2f9401580f014c7f58ea4e30989d';

    public function test_a_suspended_account_cannot_sign_in_and_is_told_nothing_more(): void
    {
        $admin = Admin::factory()->create(['suspended_at' => CarbonImmutable::now()]);

        Livewire::test(LoginForm::class)
            ->set('email', $admin->email)
            ->set('password', 'password')
            ->call('login')
            ->assertHasErrors('email')
            ->assertSee('Ces identifiants ne correspondent à aucun compte.');

        $this->assertFalse(Auth::guard('admin')->check());
    }

    public function test_an_account_in_service_still_signs_in(): void
    {
        $admin = Admin::factory()->create();

        Livewire::test(LoginForm::class)
            ->set('email', $admin->email)
            ->set('password', 'password')
            ->call('login')
            ->assertHasNoErrors();

        $this->assertTrue(Auth::guard('admin')->check());
    }

    /** The session stays in the browser, and the next click finds nobody behind it. */
    public function test_a_session_already_open_stops_at_its_next_click_everywhere(): void
    {
        $admin = Admin::factory()->create();

        foreach (['admin.profile', 'booking.admin.agenda', 'analytics.admin.overview'] as $route) {
            $this->withSession([self::SESSION_KEY => $admin->id])->get(route($route))->assertOk();
        }

        $admin->forceFill(['suspended_at' => CarbonImmutable::now()])->save();

        foreach (['admin.profile', 'booking.admin.agenda', 'analytics.admin.overview'] as $route) {
            Auth::forgetGuards();

            $this->withSession([self::SESSION_KEY => $admin->id])->get(route($route))->assertRedirect(route('admin.login'));
        }
    }

    public function test_an_account_given_back_signs_in_with_the_same_password(): void
    {
        $admin = Admin::factory()->create(['suspended_at' => CarbonImmutable::now()]);

        $admin->forceFill(['suspended_at' => null])->save();

        Livewire::test(LoginForm::class)
            ->set('email', $admin->email)
            ->set('password', 'password')
            ->call('login')
            ->assertHasNoErrors();

        $this->assertTrue(Auth::guard('admin')->check());
    }
}
