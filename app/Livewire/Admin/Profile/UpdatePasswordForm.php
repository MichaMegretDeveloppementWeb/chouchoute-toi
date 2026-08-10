<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Profile;

use App\Models\Admin;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Livewire\Component;

final class UpdatePasswordForm extends Component
{
    public string $currentPassword = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    /** @return array<string, array<int, mixed>> */
    protected function rules(): array
    {
        return [
            // `current_password:admin` replays the guard, so a stolen session
            // alone is not enough to take the account over.
            'currentPassword' => ['required', 'string', 'current_password:admin'],
            'password' => ['required', 'string', Password::min(12), 'confirmed:passwordConfirmation'],
        ];
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'currentPassword.required' => 'Veuillez indiquer votre mot de passe actuel.',
            'currentPassword.current_password' => 'Le mot de passe actuel est incorrect.',
            'password.required' => 'Veuillez indiquer un nouveau mot de passe.',
            'password.min' => 'Le nouveau mot de passe doit faire au moins 12 caractères.',
            'password.confirmed' => 'La confirmation ne correspond pas au nouveau mot de passe.',
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        $this->admin()->update(['password' => $validated['password']]);

        $this->reset(['currentPassword', 'password', 'passwordConfirmation']);

        $this->dispatch('toast', type: 'success', title: 'Mot de passe mis à jour.');
    }

    public function render(): View
    {
        return view('livewire.admin.profile.update-password-form');
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth()->guard('admin')->user();

        return $admin;
    }
}
