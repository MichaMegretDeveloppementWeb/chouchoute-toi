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
        $this->assertNotNull($panel->querySelector('form[action="'.route('admin.logout').'"] button[type="submit"]'));
    }
}
