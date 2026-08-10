<div>
    <form wire:submit="login" class="space-y-4">
        <x-ui.form-group label="Adresse email" for="email" required>
            <x-ui.input
                type="email"
                id="email"
                wire:model.blur="email"
                autocomplete="username"
                autofocus
                placeholder="amandine@chouchoute-toi.com"
                :error="$errors->has('email')"
            />
        </x-ui.form-group>

        <x-ui.form-group label="Mot de passe" for="password" required>
            <x-ui.input
                type="password"
                id="password"
                wire:model.blur="password"
                autocomplete="current-password"
                placeholder="••••••••"
                :error="$errors->has('password')"
            />
        </x-ui.form-group>

        <x-ui.checkbox id="remember" wire:model="remember" label="Rester connectée" />

        <x-ui.button type="submit" target="login" loading class="w-full justify-center">
            Se connecter
        </x-ui.button>
    </form>
</div>
