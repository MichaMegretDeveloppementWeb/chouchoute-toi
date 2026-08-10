<x-layout.admin title="Mon profil">
    <x-ui.page-header
        title="Mon profil"
        description="Identifiants de connexion au back-office."
    />

    <div class="mt-6 grid gap-4 lg:grid-cols-2">
        <x-ui.card>
            <x-ui.section-header title="Informations" />
            <div class="mt-4">
                <livewire:admin.profile.update-profile-form />
            </div>
        </x-ui.card>

        <x-ui.card>
            <x-ui.section-header title="Mot de passe" />
            <div class="mt-4">
                <livewire:admin.profile.update-password-form />
            </div>
        </x-ui.card>
    </div>
</x-layout.admin>
