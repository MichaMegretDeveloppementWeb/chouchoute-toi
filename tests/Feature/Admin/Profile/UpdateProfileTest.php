<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Profile;

use App\Livewire\Admin\Profile\UpdateProfileForm;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class UpdateProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_screen_is_reachable_by_a_signed_in_admin(): void
    {
        $this->actingAs(Admin::factory()->create(), 'admin')
            ->get(route('admin.profile'))
            ->assertOk();
    }

    public function test_profile_screen_is_closed_to_a_guest(): void
    {
        $this->get(route('admin.profile'))->assertRedirect(route('admin.login'));
    }

    public function test_form_is_prefilled_with_the_current_identity(): void
    {
        $admin = Admin::factory()->create(['name' => 'Amandine', 'email' => 'amandine@example.test']);

        Livewire::actingAs($admin, 'admin')
            ->test(UpdateProfileForm::class)
            ->assertSet('name', 'Amandine')
            ->assertSet('email', 'amandine@example.test');
    }

    public function test_name_and_email_are_persisted(): void
    {
        $admin = Admin::factory()->create(['name' => 'Amandine', 'email' => 'amandine@example.test']);

        Livewire::actingAs($admin, 'admin')
            ->test(UpdateProfileForm::class)
            ->set('name', 'Amandine David Cruz')
            ->set('email', 'contact@example.test')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('admins', [
            'id' => $admin->id,
            'name' => 'Amandine David Cruz',
            'email' => 'contact@example.test',
        ]);
    }

    public function test_keeping_the_same_email_does_not_trip_the_unique_rule(): void
    {
        $admin = Admin::factory()->create(['email' => 'amandine@example.test']);

        Livewire::actingAs($admin, 'admin')
            ->test(UpdateProfileForm::class)
            ->set('name', 'Amandine')
            ->call('save')
            ->assertHasNoErrors();
    }

    public function test_email_already_taken_by_another_admin_is_rejected(): void
    {
        Admin::factory()->create(['email' => 'taken@example.test']);
        $admin = Admin::factory()->create(['email' => 'amandine@example.test']);

        Livewire::actingAs($admin, 'admin')
            ->test(UpdateProfileForm::class)
            ->set('email', 'taken@example.test')
            ->call('save')
            ->assertHasErrors(['email' => 'unique']);

        $this->assertDatabaseHas('admins', ['id' => $admin->id, 'email' => 'amandine@example.test']);
    }

    public function test_invalid_email_is_rejected(): void
    {
        $admin = Admin::factory()->create();

        Livewire::actingAs($admin, 'admin')
            ->test(UpdateProfileForm::class)
            ->set('email', 'pas-une-adresse')
            ->call('save')
            ->assertHasErrors(['email' => 'email']);
    }
}
