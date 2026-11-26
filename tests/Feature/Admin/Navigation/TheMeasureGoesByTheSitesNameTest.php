<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Navigation;

use App\Booking\Roles;
use App\Enums\Account\Role;
use App\Models\Admin;
use Dom\HTMLDocument;
use Falcon\Analytics\Livewire\Admin\Figures\OverviewFigures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The measure goes by the name the site gives it, `analytics.admin.label` ·
 * « Analytics » · in the bar, on the dashboard, under the owner's role, in the
 * title of each of its tabs and on its page for what is not there. The bar,
 * the dashboard and the role read the setting, never a name of their own. The
 * « Audience » section of the overview names what it shows, not the space, and
 * keeps its name.
 */
final class TheMeasureGoesByTheSitesNameTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_site_calls_the_measure_analytics(): void
    {
        $this->assertSame('Analytics', config('analytics.admin.label'));
    }

    public function test_the_bar_and_the_dashboard_read_the_name_the_setting_gives(): void
    {
        config()->set('analytics.admin.label', 'Fréquentation');
        $this->actingAs(Admin::factory()->create(), 'admin');

        $page = $this->get(route('admin.dashboard'))->assertOk();
        $document = HTMLDocument::createFromString((string) $page->getContent(), LIBXML_NOERROR);
        $sections = [];

        foreach ($document->querySelectorAll('.ui-sidebar-collapsible > a .ui-sidebar-label') as $label) {
            $sections[] = trim((string) $label->textContent);
        }

        $this->assertContains('Fréquentation', $sections, 'La section de la barre ne lit pas le nom que donne le réglage.');
        $page->assertSee('Ouvrir Fréquentation');
        $page->assertDontSee('Audience');
    }

    public function test_the_owners_role_says_it_opens_the_measure_by_its_name(): void
    {
        config()->set('analytics.admin.label', 'Fréquentation');

        $owner = collect(app(Roles::class)->roles())->firstWhere('value', Role::Owner->value);

        $this->assertNotNull($owner);
        $this->assertSame('Tout le back-office, réglages, Fréquentation et marketing compris.', $owner->opens);
    }

    public function test_an_analytics_screen_and_its_missing_page_are_titled_with_the_sites_name(): void
    {
        $this->actingAs(Admin::factory()->create(), 'admin');

        $this->get(route('analytics.admin.realtime'))
            ->assertOk()
            ->assertSee('<title>Temps réel · Analytics', false);

        $this->get('/admin/analytics/nulle-part')
            ->assertNotFound()
            ->assertSee('<title>Page introuvable · Analytics', false);
    }

    public function test_the_overview_keeps_its_audience_section(): void
    {
        $this->actingAs(Admin::factory()->create(), 'admin');

        Livewire::test(OverviewFigures::class, ['period' => '30'])
            ->call('$refresh')
            ->assertSee('Audience');
    }
}
