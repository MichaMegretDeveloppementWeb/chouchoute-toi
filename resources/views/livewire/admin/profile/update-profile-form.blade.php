<div>
    <form wire:submit="save" class="space-y-4">
        <x-ui.form-group label="Nom" for="name" required>
            <x-ui.input
                type="text"
                id="name"
                wire:model.blur="name"
                autocomplete="name"
                :error="$errors->has('name')"
            />
        </x-ui.form-group>

        <x-ui.form-group
            label="Adresse email"
            for="email"
            hint="C'est aussi l'identifiant de connexion au back-office."
            required>
            <x-ui.input
                type="email"
                id="email"
                wire:model.blur="email"
                autocomplete="username"
                :error="$errors->has('email')"
            />
        </x-ui.form-group>

        <div class="flex justify-end">
            <x-ui.button type="submit" target="save" loading>
                Enregistrer
            </x-ui.button>
        </div>
    </form>
</div>
