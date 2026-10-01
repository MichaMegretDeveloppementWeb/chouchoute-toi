<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Profile;

use App\Livewire\Admin\Profile\UpdatePasswordForm;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

final class UpdatePasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_is_replaced_when_the_current_one_is_given(): void
    {
        $admin = Admin::factory()->create();

        Livewire::actingAs($admin, 'admin')
            ->test(UpdatePasswordForm::class)
            ->set('currentPassword', 'password')
            ->set('password', 'un-mot-de-passe-solide')
            ->set('passwordConfirmation', 'un-mot-de-passe-solide')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('un-mot-de-passe-solide', $admin->refresh()->password));
    }

    public function test_fields_are_cleared_after_a_successful_change(): void
    {
        $admin = Admin::factory()->create();

        Livewire::actingAs($admin, 'admin')
            ->test(UpdatePasswordForm::class)
            ->set('currentPassword', 'password')
            ->set('password', 'un-mot-de-passe-solide')
            ->set('passwordConfirmation', 'un-mot-de-passe-solide')
            ->call('save')
            ->assertSet('currentPassword', '')
            ->assertSet('password', '')
            ->assertSet('passwordConfirmation', '');
    }

    public function test_wrong_current_password_is_rejected(): void
    {
        $admin = Admin::factory()->create();

        Livewire::actingAs($admin, 'admin')
            ->test(UpdatePasswordForm::class)
            ->set('currentPassword', 'mauvais-mot-de-passe')
            ->set('password', 'un-mot-de-passe-solide')
            ->set('passwordConfirmation', 'un-mot-de-passe-solide')
            ->call('save')
            ->assertHasErrors('currentPassword');

        $this->assertTrue(Hash::check('password', $admin->refresh()->password));
    }

    public function test_mismatched_confirmation_is_refused_under_the_confirmation(): void
    {
        $admin = Admin::factory()->create();

        $form = Livewire::actingAs($admin, 'admin')
            ->test(UpdatePasswordForm::class)
            ->set('currentPassword', 'password')
            ->set('password', 'un-mot-de-passe-solide')
            ->set('passwordConfirmation', 'autre-chose-entierement')
            ->call('save')
            ->assertHasErrors(['passwordConfirmation' => 'La confirmation ne correspond pas au nouveau mot de passe.'])
            ->assertHasNoErrors('password');

        $this->assertTrue($this->isMarkedRefused($form->html(), 'passwordConfirmation'));
        $this->assertMatchesRegularExpression(
            '/<p\s+id="ui-field-passwordConfirmation-message"[^>]*>La confirmation ne correspond pas au nouveau mot de passe\.<\/p>/',
            $form->html(),
            'Le refus ne s’affiche pas sous la confirmation.',
        );
        $this->assertFalse($this->isMarkedRefused($form->html(), 'password'), 'Le nouveau mot de passe est valide, il ne doit pas rougir.');
        $this->assertTrue(Hash::check('password', $admin->refresh()->password));
    }

    public function test_missing_confirmation_is_asked_for(): void
    {
        $admin = Admin::factory()->create();

        Livewire::actingAs($admin, 'admin')
            ->test(UpdatePasswordForm::class)
            ->set('currentPassword', 'password')
            ->set('password', 'un-mot-de-passe-solide')
            ->call('save')
            ->assertHasErrors(['passwordConfirmation' => 'Veuillez confirmer le nouveau mot de passe.']);

        $this->assertTrue(Hash::check('password', $admin->refresh()->password));
    }

    public function test_too_short_password_is_rejected(): void
    {
        $admin = Admin::factory()->create();

        $form = Livewire::actingAs($admin, 'admin')
            ->test(UpdatePasswordForm::class)
            ->set('currentPassword', 'password')
            ->set('password', 'court')
            ->set('passwordConfirmation', 'court')
            ->call('save')
            ->assertHasErrors(['password' => 'Le nouveau mot de passe doit faire au moins 12 caractères.'])
            ->assertHasNoErrors('passwordConfirmation');

        $this->assertFalse($this->isMarkedRefused($form->html(), 'passwordConfirmation'), 'La confirmation rougit pour une règle qui ne la concerne pas.');
        $this->assertTrue(Hash::check('password', $admin->refresh()->password));
    }

    private function isMarkedRefused(string $html, string $id): bool
    {
        $this->assertSame(1, preg_match('/<input\b[^>]*\bid="'.$id.'"[^>]*>/', $html, $field), "Le champ {$id} est introuvable.");

        return str_contains($field[0], 'aria-invalid="true"');
    }
}
