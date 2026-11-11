<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Admin;
use Dom\HTMLDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * The account menu at the foot of the bar is the kit's menu, placed from its
 * button · a panel as wide as the bar was cut to forty pixels once it folded.
 */
final class TheAccountMenuIsTheKitsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_account_opens_the_kits_menu_with_its_three_entries(): void
    {
        $this->actingAs(Admin::factory()->create(), 'admin');

        $bar = HTMLDocument::createFromString('<!DOCTYPE html><html><body>'.Blade::render('<x-layout.admin></x-layout.admin>').'</body></html>', LIBXML_NOERROR);

        $menu = $bar->querySelector('.ui-dropdown:has(.admin-account)');
        $this->assertNotNull($menu, 'The account button opens no menu of the kit.');

        $panel = $menu->querySelector('[x-ref="panel"]');
        $this->assertNotNull($panel);
        $this->assertNotNull($panel->querySelector('a[href="'.route('admin.profile').'"]'));
        $this->assertNotNull($panel->querySelector('a[href="'.route('home').'"]'));
        $this->assertNotNull($panel->querySelector('form[action="'.route('auth.logout').'"] button[type="submit"]'));
    }

    /**
     * The account runs across the whole bar, as in the drawer · its row reaches
     * the edges, and its button keeps its content off them at every width.
     */
    public function test_the_account_runs_across_the_whole_bar(): void
    {
        $this->actingAs(Admin::factory()->create(), 'admin');

        $page = HTMLDocument::createFromString('<!DOCTYPE html><html><body>'.Blade::render('<x-layout.admin></x-layout.admin>').'</body></html>', LIBXML_NOERROR);
        $button = $page->querySelector('.ui-sidebar .admin-account');

        $this->assertNotNull($button);
        $classes = explode(' ', trim((string) preg_replace('/\s+/', ' ', (string) $button->getAttribute('class'))));
        $this->assertContains('px-5', $classes);
        $this->assertSame([], array_values(array_filter($classes, static fn (string $class): bool => str_starts_with($class, 'lg:px'))), 'Au-dessus de lg, le bouton du compte ne retient plus son contenu des bords.');

        $sheet = (string) file_get_contents(resource_path('css/admin/sidebar.css'));
        $this->assertMatchesRegularExpression('/\.ui-sidebar li:has\(\.admin-account\) \{\s*margin-inline: -20px;\s*\}/', $sheet, 'La ligne du compte ne va plus jusqu’aux bords de la barre.');
    }
}
