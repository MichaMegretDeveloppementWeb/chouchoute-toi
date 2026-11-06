<?php

declare(strict_types=1);

namespace Tests\Feature\Auth\SignIn;

use App\Livewire\Auth\SignIn\LoginForm;
use App\Models\Admin;
use Falcon\Booking\Models\Practitioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

final class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_is_reachable_by_a_guest(): void
    {
        $this->get(route('auth.login'))->assertOk();
    }

    public function test_the_remember_box_speaks_in_the_generic_masculine(): void
    {
        $this->get(route('auth.login'))
            ->assertSee('Rester connecté')
            ->assertDontSee('Rester connectée');
    }

    public function test_signed_in_admin_is_redirected_away_from_the_login_screen(): void
    {
        $this->actingAs(Admin::factory()->create(), 'admin')
            ->get(route('auth.login'))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_a_practitioner_lands_on_the_planning_wherever_the_sign_in_leads(): void
    {
        $member = Admin::factory()->practitioner(Practitioner::factory()->create()->id)->create(['email' => 'julie@example.test']);

        Livewire::test(LoginForm::class)
            ->set('email', 'julie@example.test')
            ->set('password', 'password')
            ->call('login')
            ->assertRedirect(route('booking.admin.agenda'));

        $this->actingAs($member, 'admin')
            ->get(route('auth.login'))
            ->assertRedirect(route('booking.admin.agenda'));
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

    public function test_an_empty_or_malformed_form_says_what_to_write(): void
    {
        Livewire::test(LoginForm::class)
            ->call('login')
            ->assertHasErrors([
                'email' => 'Indiquez votre adresse e-mail.',
                'password' => 'Indiquez votre mot de passe.',
            ])
            ->set('email', 'amandine')
            ->call('login')
            ->assertHasErrors(['email' => 'Indiquez une adresse e-mail valide.']);
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
            ->post(route('auth.logout'))
            ->assertRedirect(route('auth.login'));

        $this->assertFalse(auth()->guard('admin')->check());
    }

    protected function tearDown(): void
    {
        RateLimiter::clear(mb_strtolower('amandine@example.test').'|127.0.0.1');

        parent::tearDown();
    }
}
