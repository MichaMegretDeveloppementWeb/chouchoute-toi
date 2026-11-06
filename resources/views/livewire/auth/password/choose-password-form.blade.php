<div>
    @if ($usable)
        <form wire:submit="choose" class="space-y-4">
            <p class="text-[13px] text-secondary">
                {{ $purpose === 'invitation' ? 'Choisissez votre mot de passe' : 'Choisissez un nouveau mot de passe' }} pour
                <span class="font-medium text-primary">{{ $email }}</span>.
            </p>

            <x-ui::form-group label="Mot de passe" for="password" hint="Au moins 12 caractères." required>
                <x-ui::input
                    type="password"
                    id="password"
                    wire:model.blur="password"
                    autocomplete="new-password"
                    autofocus
                    :error="$errors->has('password')"
                />
            </x-ui::form-group>

            <x-ui::form-group label="Confirmez le mot de passe" for="passwordConfirmation" required>
                <x-ui::input
                    type="password"
                    id="passwordConfirmation"
                    wire:model.blur="passwordConfirmation"
                    autocomplete="new-password"
                    :error="$errors->has('passwordConfirmation')"
                />
            </x-ui::form-group>

            <x-ui::button type="submit" target="choose" loading class="w-full">
                {{ $purpose === 'invitation' ? 'Choisir mon mot de passe' : 'Changer mon mot de passe' }}
            </x-ui::button>
        </form>
    @else
        <div class="space-y-4">
            <p class="text-[13px] text-secondary">
                Ce lien a expiré ou a déjà servi.
                {{ $purpose === 'invitation' ? 'Demandez-en un nouveau au propriétaire.' : 'Vous pouvez en demander un nouveau.' }}
            </p>

            @if ($purpose === 'reset')
                <x-ui::button :href="route('auth.password.forgot')" class="w-full">Demander un nouveau lien</x-ui::button>
            @endif

            <x-ui::button variant="secondary" :href="route('auth.login')" class="w-full">Revenir à la connexion</x-ui::button>
        </div>
    @endif
</div>
