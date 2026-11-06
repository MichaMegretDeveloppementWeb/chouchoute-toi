<?php

declare(strict_types=1);

namespace Tests\Feature\Auth\Password;

use App\Models\Admin;
use App\Providers\AuthServiceProvider;
use App\Services\Auth\Password\PasswordLinksService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * A link opened where an account is signed in says which, and offers to sign
 * out and open it · and opening links is limited, each costing a hash.
 */
final class ALinkOpenedWhileSignedInSaysWhoTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{string, string, string}> */
    public static function provideTheLinks(): array
    {
        return [
            'an invitation' => ['auth.invitation', 'invitation', PasswordLinksService::INVITATIONS],
            'a forgotten password' => ['auth.password.reset', 'reset', PasswordLinksService::PASSWORD_RESETS],
        ];
    }

    #[DataProvider('provideTheLinks')]
    public function test_a_signed_in_account_reads_who_it_is_and_the_way_out(string $route, string $purpose, string $kind): void
    {
        $member = Admin::factory()->collaborator()->create(['email' => 'julie@exemple.fr', 'email_verified_at' => null]);
        $token = app(PasswordLinksService::class)->links($kind)->createToken($member);
        $owner = Admin::factory()->create(['name' => 'Amandine']);

        $this->actingAs($owner, 'admin')
            ->get(route($route, ['account' => $member->id, 'token' => $token]))
            ->assertOk()
            ->assertSee('Vous êtes connecté en tant que')
            ->assertSee('Amandine')
            ->assertSee('Se déconnecter et ouvrir le lien')
            ->assertSee(route('auth.link.sign-out', ['purpose' => $purpose, 'account' => $member->id, 'token' => $token]), false)
            ->assertDontSee('julie@exemple.fr');
    }

    #[DataProvider('provideTheLinks')]
    public function test_signing_out_opens_the_link(string $route, string $purpose, string $kind): void
    {
        $member = Admin::factory()->collaborator()->create(['email' => 'julie@exemple.fr', 'email_verified_at' => null]);
        $token = app(PasswordLinksService::class)->links($kind)->createToken($member);

        $this->actingAs(Admin::factory()->create(), 'admin')
            ->post(route('auth.link.sign-out', ['purpose' => $purpose, 'account' => $member->id, 'token' => $token]))
            ->assertRedirect(route($route, ['account' => $member->id, 'token' => $token]));

        $this->assertGuest('admin');

        $this->get(route($route, ['account' => $member->id, 'token' => $token]))
            ->assertOk()
            ->assertSee('julie@exemple.fr')
            ->assertDontSee('Vous êtes connecté en tant que');
    }

    public function test_only_a_signed_in_account_signs_out_for_a_link(): void
    {
        $this->post(route('auth.link.sign-out', ['purpose' => 'reset', 'account' => 1, 'token' => 'abc']))
            ->assertRedirect(route('auth.login'));
    }

    /** A visitor opens a few links a minute, never hundreds · the one too many is refused. */
    public function test_opening_links_is_limited(): void
    {
        $link = route('auth.password.reset', ['account' => 1, 'token' => 'abc']);

        for ($visit = 1; $visit <= AuthServiceProvider::LINK_VISITS_PER_MINUTE; $visit++) {
            $this->get($link)->assertOk();
        }

        $this->get($link)->assertTooManyRequests();
        $this->get(route('auth.invitation', ['account' => 1, 'token' => 'abc']))->assertTooManyRequests();
    }
}
