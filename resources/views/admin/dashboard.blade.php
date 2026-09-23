{{-- La coquille rend l'espace, l'écran le cadre · trois cartes se lisent dans
     une colonne, pas sur une ligne de 2800 pixels. --}}
<x-layout.admin title="Tableau de bord">
    <div class="mx-auto max-w-[90em] px-4 py-6 sm:px-6 sm:py-8">
        <x-ui::page-header
            title="Tableau de bord"
            description="Point d'entrée du back-office. Les modules de réservation viendront s'ajouter ici."
        />

        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <x-ui::card>
                <x-ui::icon-text icon="chart-pie" class="mb-2 text-secondary">Audience</x-ui::icon-text>
                <p class="text-[13px] text-secondary">
                    Fréquentation du site, provenance des visiteurs, pages consultées et parcours complets.
                </p>
                <x-ui::button
                    :href="route('analytics.admin.overview')"
                    variant="secondary"
                    size="compact"
                    class="mt-4">
                    Ouvrir l'audience
                </x-ui::button>
            </x-ui::card>

            <x-ui::card>
                <x-ui::icon-text icon="signal" class="mb-2 text-secondary">Temps réel</x-ui::icon-text>
                <p class="text-[13px] text-secondary">
                    Qui est sur le site en ce moment, sur quelle page, et d'où la visite arrive.
                </p>
                <x-ui::button
                    :href="route('analytics.admin.realtime')"
                    variant="secondary"
                    size="compact"
                    class="mt-4">
                    Voir le temps réel
                </x-ui::button>
            </x-ui::card>

            <x-ui::card>
                <x-ui::icon-text icon="megaphone" class="mb-2 text-secondary">Marketing</x-ui::icon-text>
                <p class="text-[13px] text-secondary">
                    Performance des campagnes et des publicités, sans passer par les régies publicitaires.
                </p>
                <x-ui::button
                    :href="route('analytics.admin.marketing.dashboard')"
                    variant="secondary"
                    size="compact"
                    class="mt-4">
                    Ouvrir Marketing
                </x-ui::button>
            </x-ui::card>
        </div>
    </div>
</x-layout.admin>
