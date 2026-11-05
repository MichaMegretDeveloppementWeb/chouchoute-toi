{{-- A screen of the office the account does not open · said inside the office, with the way back. --}}
<x-layout.admin title="Accès refusé">
    <div class="mx-auto max-w-[90em] px-4 py-6 sm:px-6 sm:py-8">
        <x-ui::card>
            <x-ui::empty-state
                icon="lock-closed"
                title="Cet écran ne fait pas partie de votre accès"
                description="S’il vous le faut, demandez-le au propriétaire.">
                <x-ui::button :href="route($role->home())">{{ $role->backHome() }}</x-ui::button>
            </x-ui::empty-state>
        </x-ui::card>
    </div>
</x-layout.admin>
