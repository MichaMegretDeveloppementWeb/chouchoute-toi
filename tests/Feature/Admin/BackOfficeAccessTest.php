<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class BackOfficeAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Every screen of the back-office, the application's own and the two
     * modules mounted by falcon/analytics.
     *
     * Les noms sont écrits en clair, et c'est le paquet qui le demande · ils
     * sont fixes, seules leurs adresses se règlent. Les composer depuis une clé
     * de configuration a laissé cette liste viser `analytics.overview` bien
     * après que le paquet l'eut renommée, et rien ne l'a dit.
     *
     * @return array<string, array{string}>
     */
    public static function provideProtectedRoutes(): array
    {
        return [
            'dashboard' => ['admin.dashboard'],
            'analytics overview' => ['analytics.admin.overview'],
            'analytics realtime' => ['analytics.admin.realtime'],
            'analytics visitors' => ['analytics.admin.visitors'],
            'analytics sessions' => ['analytics.admin.sessions'],
            'analytics events' => ['analytics.admin.events'],
            'analytics funnels' => ['analytics.admin.funnels'],
            'marketing dashboard' => ['analytics.admin.marketing.dashboard'],
            'marketing campaigns' => ['analytics.admin.marketing.campaigns'],
            'marketing ads' => ['analytics.admin.marketing.ads'],
        ];
    }

    #[DataProvider('provideProtectedRoutes')]
    public function test_guest_is_redirected_to_the_login_screen(string $route): void
    {
        $this->get(route($route))->assertRedirect(route('admin.login'));
    }

    #[DataProvider('provideProtectedRoutes')]
    public function test_user_of_the_web_guard_cannot_reach_the_back_office(string $route): void
    {
        $this->actingAs(User::factory()->create(), 'web')
            ->get(route($route))
            ->assertRedirect(route('admin.login'));
    }

    #[DataProvider('provideProtectedRoutes')]
    public function test_signed_in_admin_reaches_every_screen(string $route): void
    {
        $this->actingAs(Admin::factory()->create(), 'admin')
            ->get(route($route))
            ->assertOk();
    }

    public function test_analytics_screens_render_inside_the_host_shell(): void
    {
        $response = $this->actingAs(Admin::factory()->create(), 'admin')->get(route('analytics.admin.overview'));

        $response->assertOk();
        // Markers of the host shell, absent from the package's own layout.
        $response->assertSee('Chouchoute-toi', escape: false);
        $response->assertSee(route('admin.dashboard'), escape: false);
    }

    public function test_shell_exposes_the_account_menu_entries(): void
    {
        $admin = Admin::factory()->create(['name' => 'Amandine', 'email' => 'amandine@example.test']);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Amandine', escape: false);
        $response->assertSee('amandine@example.test', escape: false);
        $response->assertSee(route('admin.profile'), escape: false);
        $response->assertSee(route('admin.logout'), escape: false);
        $response->assertSee(route('home'), escape: false);
    }
}
