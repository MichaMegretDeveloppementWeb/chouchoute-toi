<div>
    <form wire:submit="login" class="space-y-4">
        <x-ui::form-group label="Adresse e-mail" for="email" required>
            <x-ui::input
                type="email"
                id="email"
                wire:model.blur="email"
                autocomplete="username"
                autofocus
                placeholder="amandine@chouchoute-toi.com"
                :error="$errors->has('email')"
            />
        </x-ui::form-group>

        <x-ui::form-group label="Mot de passe" for="password" required>
            <x-ui::input
                type="password"
                id="password"
                wire:model.blur="password"
                autocomplete="current-password"
                placeholder="••••••••"
                :error="$errors->has('password')"
            />
        </x-ui::form-group>

        <div class="flex items-center justify-between gap-x-4">
            <x-ui::checkbox id="remember" wire:model="remember" label="Rester connecté" />

            <a href="{{ route('auth.password.forgot') }}" class="text-[13px] text-secondary underline underline-offset-2 hover:text-primary">Mot de passe oublié ?</a>
        </div>

        <x-ui::button type="submit" target="login" loading class="w-full">
            Se connecter
        </x-ui::button>
    </form>
</div>
