<div>
    <form wire:submit="save" class="space-y-4">
        <x-ui.form-group label="Mot de passe actuel" for="currentPassword" required>
            <x-ui.input
                type="password"
                id="currentPassword"
                wire:model.blur="currentPassword"
                autocomplete="current-password"
                :error="$errors->has('currentPassword')"
            />
        </x-ui.form-group>

        <x-ui.form-group
            label="Nouveau mot de passe"
            for="password"
            hint="12 caractères minimum."
            required>
            <x-ui.input
                type="password"
                id="password"
                wire:model.blur="password"
                autocomplete="new-password"
                :error="$errors->has('password')"
            />
        </x-ui.form-group>

        <x-ui.form-group label="Confirmer le nouveau mot de passe" for="passwordConfirmation" required>
            <x-ui.input
                type="password"
                id="passwordConfirmation"
                wire:model.blur="passwordConfirmation"
                autocomplete="new-password"
                :error="$errors->has('password')"
            />
        </x-ui.form-group>

        <div class="flex justify-end">
            <x-ui.button type="submit" target="save" loading>
                Changer le mot de passe
            </x-ui.button>
        </div>
    </form>
</div>
