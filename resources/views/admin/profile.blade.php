{{-- Deux formulaires courts · une colonne, pas la pleine largeur. --}}
<x-layout.admin title="Mon profil">
    <div class="mx-auto max-w-[90em] px-4 py-6 sm:px-6 sm:py-8">
        <x-ui::page-header
            title="Mon profil"
            description="Identifiants de connexion au back-office."
        />

        <div class="mt-6 grid gap-4 lg:grid-cols-2">
            <x-ui::card>
                <x-ui::section-header title="Informations" />
                <div class="mt-4">
                    <livewire:admin.profile.update-profile-form />
                </div>
            </x-ui::card>

            <x-ui::card>
                <x-ui::section-header title="Mot de passe" />
                <div class="mt-4">
                    <livewire:admin.profile.update-password-form />
                </div>
            </x-ui::card>
        </div>
    </div>
</x-layout.admin>
