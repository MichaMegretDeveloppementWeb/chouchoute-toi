<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Profile;

use App\Booking\Accounts;
use App\Models\Admin;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Identity of the back-office account: display name and sign-in email.
 *
 * Straight to Eloquent, no Action nor Service: a single write on a single
 * model, no transaction, no business rule beyond the validation itself.
 */
final class UpdateProfileForm extends Component
{
    public string $name = '';

    public string $email = '';

    private Accounts $accounts;

    public function boot(Accounts $accounts): void
    {
        $this->accounts = $accounts;
    }

    public function mount(): void
    {
        $admin = $this->admin();

        $this->name = $admin->name;
        $this->email = $admin->email;
    }

    /** Why the address cannot change here, for an account tied to a fiche · it is that fiche's address too. */
    public function whyTheAddressStays(): ?string
    {
        $admin = $this->admin();

        return match (true) {
            ! $this->accounts->aFicheKeeps($admin->id) => null,
            $admin->isOwner() => 'C’est aussi l’adresse de votre fiche, et elle se change depuis Praticiens.',
            default => 'C’est aussi l’adresse de votre fiche, et seul un propriétaire peut la changer.',
        };
    }

    /** @return array<string, array<int, mixed>> */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('admins', 'email')->ignore($this->admin()->id),
            ],
        ];
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'name.required' => 'Veuillez indiquer un nom.',
            'name.max' => 'Le nom ne doit pas dépasser 255 caractères.',
            'email.required' => 'Veuillez indiquer une adresse email.',
            'email.email' => 'Veuillez entrer une adresse email valide.',
            'email.unique' => 'Cette adresse email est déjà utilisée.',
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        $this->admin()->update($this->whyTheAddressStays() === null ? $validated : ['name' => $validated['name']]);

        $this->email = $this->admin()->email;

        $this->dispatch('ui-toast', type: 'success', title: 'Profil mis à jour.');
    }

    public function render(): View
    {
        return view('livewire.admin.profile.update-profile-form');
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth()->guard('admin')->user();

        return $admin;
    }
}
