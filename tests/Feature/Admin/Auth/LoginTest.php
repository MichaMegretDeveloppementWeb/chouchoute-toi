<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Auth;

use App\Livewire\Admin\Auth\LoginForm;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

final class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_is_reachable_by_a_guest(): void
    {
        $this->get(route('admin.login'))->assertOk();
    }

    public function test_signed_in_admin_is_redirected_away_from_the_login_screen(): void
    {
        $this->actingAs(Admin::factory()->create(), 'admin')
            ->get(route('admin.login'))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_valid_credentials_authenticate_on_the_admin_guard_only(): void
    {
        $admin = Admin::factory()->create(['email' => 'amandine@example.test']);

        Livewire::test(LoginForm::class)
            ->set('email', 'amandine@example.test')
            ->set('password', 'password')
            ->call('login')
            ->assertRedirect(route('admin.dashboard'));

        $this->assertSame($admin->id, auth()->guard('admin')->id());
        $this->assertFalse(auth()->guard('web')->check());
    }

    public function test_wrong_password_is_rejected_and_leaves_the_guard_closed(): void
    {
        Admin::factory()->create(['email' => 'amandine@example.test']);

        Livewire::test(LoginForm::class)
            ->set('email', 'amandine@example.test')
            ->set('password', 'wrong-password')
            ->call('login')
            ->assertHasErrors('email');

        $this->assertFalse(auth()->guard('admin')->check());
    }

    public function test_unknown_email_is_rejected(): void
    {
        Livewire::test(LoginForm::class)
            ->set('email', 'nobody@example.test')
            ->set('password', 'password')
            ->call('login')
            ->assertHasErrors('email');

        $this->assertFalse(auth()->guard('admin')->check());
    }

    public function test_form_locks_after_five_failed_attempts(): void
    {
        Admin::factory()->create(['email' => 'amandine@example.test']);

        $component = Livewire::test(LoginForm::class)
            ->set('email', 'amandine@example.test')
            ->set('password', 'wrong-password');

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $component->call('login')->assertHasErrors('email');
        }

        // The sixth attempt is refused by the throttle, with the right password.
        $component->set('password', 'password')->call('login');

        $this->assertFalse(auth()->guard('admin')->check());
    }

    public function test_logout_closes_the_admin_session(): void
    {
        $this->actingAs(Admin::factory()->create(), 'admin')
            ->post(route('admin.logout'))
            ->assertRedirect(route('admin.login'));

        $this->assertFalse(auth()->guard('admin')->check());
    }

    protected function tearDown(): void
    {
        RateLimiter::clear(mb_strtolower('amandine@example.test').'|127.0.0.1');

        parent::tearDown();
    }
}
