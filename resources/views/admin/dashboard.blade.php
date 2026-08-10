<x-layout.admin title="Tableau de bord">
    <x-ui.page-header
        title="Tableau de bord"
        description="Point d'entrée du back-office. Les modules de réservation viendront s'ajouter ici."
    />

    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <x-ui.card>
            <x-ui.icon-text icon="chart-pie" class="mb-2 text-secondary">Audience</x-ui.icon-text>
            <p class="text-[13px] text-secondary">
                Fréquentation du site, provenance des visiteuses, pages consultées et parcours complets.
            </p>
            <x-ui.button
                :href="route(config('analytics.dashboard.route_name', 'analytics').'.overview')"
                variant="secondary"
                size="compact"
                class="mt-4">
                Ouvrir Analytics
            </x-ui.button>
        </x-ui.card>

        <x-ui.card>
            <x-ui.icon-text icon="signal" class="mb-2 text-secondary">Temps réel</x-ui.icon-text>
            <p class="text-[13px] text-secondary">
                Qui est sur le site en ce moment, sur quelle page, et d'où la visite arrive.
            </p>
            <x-ui.button
                :href="route(config('analytics.dashboard.route_name', 'analytics').'.realtime')"
                variant="secondary"
                size="compact"
                class="mt-4">
                Voir le temps réel
            </x-ui.button>
        </x-ui.card>

        <x-ui.card>
            <x-ui.icon-text icon="megaphone" class="mb-2 text-secondary">Marketing</x-ui.icon-text>
            <p class="text-[13px] text-secondary">
                Performance des campagnes et des publicités, sans passer par les régies publicitaires.
            </p>
            <x-ui.button
                :href="route(config('analytics.marketing.route_name', 'marketing').'.dashboard')"
                variant="secondary"
                size="compact"
                class="mt-4">
                Ouvrir Marketing
            </x-ui.button>
        </x-ui.card>
    </div>
</x-layout.admin>
