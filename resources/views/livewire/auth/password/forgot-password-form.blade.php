<div>
    @if ($sent)
        <div class="space-y-4">
            <p class="text-[13px] text-secondary">
                Si cette adresse est celle d’un compte, un lien vient d’y partir. Il est valable {{ $minutes }} minutes.
            </p>

            <x-ui::button variant="secondary" :href="route('auth.login')" class="w-full">
                Revenir à la connexion
            </x-ui::button>
        </div>
    @else
        <form wire:submit="send" class="space-y-4">
            <p class="text-[13px] text-secondary">Saisissez votre adresse : vous recevrez un lien pour choisir un nouveau mot de passe.</p>

            <x-ui::form-group label="Adresse e-mail" for="email" required>
                <x-ui::input
                    type="email"
                    id="email"
                    wire:model.blur="email"
                    autocomplete="username"
                    autofocus
                    :error="$errors->has('email')"
                />
            </x-ui::form-group>

            <x-ui::button type="submit" target="send" loading class="w-full">
                Recevoir le lien
            </x-ui::button>

            <p class="text-center text-[13px]">
                <a href="{{ route('auth.login') }}" class="text-secondary underline underline-offset-2 hover:text-primary">Revenir à la connexion</a>
            </p>
        </form>
    @endif
</div>
