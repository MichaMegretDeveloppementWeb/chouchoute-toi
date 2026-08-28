{{--
    Le message du back-office, publié depuis falcon/ui-kit et retravaillé.

    Trois écarts avec l'amont, et un seul motif : dans un coin, à 320 px et sans
    rien dire du temps qui reste, un message passait inaperçu sur un grand écran
    — le regard est au milieu de la page, là où l'on vient de cliquer.

    - **En haut, centré**, et l'entrée glisse depuis le haut : un glissement
      latéral ne veut plus rien dire sur un objet centré.
    - **Plus grand sur desktop seulement**, hauteur minimale comprise. Sous
      640 px la place est comptée et la boîte garde sa hauteur naturelle.
    - **Une barre au bas** qui décroît sur la durée réelle d'affichage.

    La graduation du texte ne bouge pas — 13 px le titre, 12 px la description :
    c'est la boîte qui grandit. Une échelle à part ferait de ce message le seul
    de son espèce dans tout le back-office.

    **Ce fichier a un jumeau**, dans la pile de falcon/booking, pour que le
    dessin voyage avec le paquet. Les deux sont identiques au caractère près et
    `PublishedComponentsTest` le tient : deux copies rédigées séparément dérivent
    sans que rien ne le montre, une seule étant à l'écran à la fois.
--}}
@props([
    // Déclarée pour être avalée, jamais lue. Le tableau de bord de
    // falcon/analytics passe `top-right` et vit dans `vendor/`, hors de portée :
    // l'honorer rendrait au back-office les deux positions qu'on vient de lui
    // retirer. Une position par back-office, pas une par appelant. Déclarée
    // quand même, sinon elle atterrirait en attribut HTML sur la racine.
    'position' => 'top-center',
])

@php
    // Collect flash session toasts
    $flashToasts = [];

    foreach (['success', 'error', 'warning', 'info'] as $type) {
        $key = "toast-{$type}";
        if (session()->has($key)) {
            $messages = session($key);
            foreach ((array) $messages as $msg) {
                $flashToasts[] = is_array($msg)
                    ? array_merge(['type' => $type], $msg)
                    : ['type' => $type, 'title' => (string) $msg];
            }
        }
    }

    // Also support generic 'toasts' session key (array of full toast objects)
    if (session()->has('toasts') && is_array(session('toasts'))) {
        foreach (session('toasts') as $toast) {
            if (is_array($toast) && isset($toast['type'], $toast['title'])) {
                $flashToasts[] = $toast;
            }
        }
    }
@endphp

{{-- Les images-clés vivent ici et non dans une feuille : les deux copies
     restent alors identiques, et ni la feuille du site ni celle du paquet n'a
     besoin d'être touchée. `@@` échappe l'arobase, que Blade lirait sinon comme
     le début d'une directive. --}}
<style>
    @@keyframes fb-toast-countdown {
        from { transform: scaleX(1); }
        to { transform: scaleX(0); }
    }
</style>

<div
    x-data="{
        toasts: [],
        counter: 0,
        add(data) {
            let toast = this.normalize(data);
            if (!toast) return;
            const id = ++this.counter;
            // Combien de temps il reste, décidé **une seule fois** : le minuteur
            // qui le retire et l'animation qui vide la barre lisent cette même
            // valeur. Deux durées écrites à part dériveraient, et la barre
            // annoncerait un temps qui n'est plus celui du minuteur.
            //
            // A toast carrying an action has to be read AND clicked; four seconds is the budget
            // for reading alone.
            const life = toast.action ? 10000 : 4000;
            this.toasts.push({ ...toast, id, life, visible: true });
            if (this.toasts.length > 5) this.remove(this.toasts[0].id);
            setTimeout(() => this.remove(id), life);
        },
        normalize(data) {
            if (!data) return null;
            // Already a proper toast object (Alpine $dispatch, Livewire named params)
            if (typeof data === 'object' && !Array.isArray(data) && data.type) return data;
            // Array wrapping (Livewire positional params or edge case)
            if (Array.isArray(data)) {
                if (data.length && typeof data[0] === 'object' && data[0].type) return data[0];
                if (data.length >= 2) return { type: data[0], title: data[1], description: data[2] || '' };
                return null;
            }
            // String fallback
            if (typeof data === 'string') return { type: 'info', title: data };
            // Object without type key but with indexed keys (rare Livewire edge case)
            if (typeof data === 'object' && data[0]) return this.normalize(Object.values(data));
            return null;
        },
        remove(id) {
            const t = this.toasts.find(t => t.id === id);
            if (t) t.visible = false;
            setTimeout(() => { this.toasts = this.toasts.filter(t => t.id !== id); }, 300);
        },
        /*
         | An undo offer, for gestures that are reversible but leave no trace on screen once
         | done. The action names a Livewire event, dispatched globally: the component that
         | undoes the gesture is rarely the one still under the cursor.
         |
         | $dispatch('toast', {type, title, action: {label, event, params}})
         */
        run(toast) {
            if (toast.action?.event) {
                window.Livewire?.dispatch(toast.action.event, toast.action.params ?? {});
            }

            this.remove(toast.id);
        }
    }"
    @if(count($flashToasts))
    x-init="
        const flashes = {{ Js::from($flashToasts) }};
        flashes.forEach((t, i) => setTimeout(() => add(t), i * 150));
    "
    @endif
    @toast.window="add($event.detail)"
    {{-- Pleine largeur avec un retrait, plutôt qu'un centrage par
         transformation : une largeur prise sur la fenêtre compte la barre de
         défilement là où le centrage ne la compte pas, ce qui décalait la boîte
         de six pixels — mesuré sur un écran de 412 px. Ici la gouttière est la
         même des deux côtés par construction.

         `pointer-events-none` sur le conteneur, qui barre toute la largeur en
         haut de la page ; chaque message reprend le clic pour lui. --}}
    class="pointer-events-none fixed inset-x-0 top-4 z-50 flex flex-col items-center gap-2 px-4"
>
    <template x-for="toast in toasts" :key="toast.id">
        {{-- `items-center` et non `items-start` : la hauteur minimale laisse de
             l'air sous un message d'une ligne, et un contenu collé en haut y
             lirait comme une boîte mal remplie. --}}
        <div x-show="toast.visible"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="-translate-y-4 opacity-0"
             x-transition:enter-end="translate-y-0 opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="translate-y-0 opacity-100"
             x-transition:leave-end="-translate-y-4 opacity-0"
             class="pointer-events-auto relative flex w-full max-w-96 items-center gap-x-3 overflow-hidden rounded-lg border border-gray-200 bg-white px-3.5 py-3 shadow-lg sm:min-h-[72px] dark:border-gray-700 dark:bg-gray-900 dark:shadow-2xl dark:shadow-black/20">
            {{-- Icon by type --}}
            <template x-if="toast.type === 'success'">
                <svg class="h-5 w-5 shrink-0 text-emerald-500 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </template>
            <template x-if="toast.type === 'error'">
                <svg class="h-5 w-5 shrink-0 text-red-500 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </template>
            <template x-if="toast.type === 'warning'">
                <svg class="h-5 w-5 shrink-0 text-amber-500 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
            </template>
            <template x-if="!['success', 'error', 'warning'].includes(toast.type)">
                <svg class="h-5 w-5 shrink-0 text-blue-500 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" /></svg>
            </template>
            <div class="min-w-0 flex-1">
                <p class="text-[13px] font-medium text-gray-900 dark:text-gray-100" x-text="toast.title"></p>
                <p x-show="toast.description" class="mt-0.5 text-[12px] text-gray-500 dark:text-gray-400" x-text="toast.description"></p>

                <template x-if="toast.action">
                    <button @click="run(toast)"
                            class="-mx-1.5 mt-1.5 rounded px-1.5 py-1 text-[12px] font-medium text-gray-900 underline underline-offset-2 transition-colors hover:bg-gray-50 dark:text-gray-100 dark:hover:bg-gray-800"
                            x-text="toast.action.label"></button>
                </template>
            </div>
            <button @click="remove(toast.id)" class="shrink-0 self-start rounded-lg p-0.5 text-gray-400 transition-colors hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>

            {{-- Le temps qui reste, dit plutôt que subi. Sa durée vient de
                 `toast.life`, la même que celle du minuteur ci-dessus. --}}
            <div class="absolute inset-x-0 bottom-0 h-[3px]" aria-hidden="true">
                <div class="h-full w-full origin-left"
                     :class="{
                        'bg-emerald-500 dark:bg-emerald-400': toast.type === 'success',
                        'bg-red-500 dark:bg-red-400': toast.type === 'error',
                        'bg-amber-500 dark:bg-amber-400': toast.type === 'warning',
                        'bg-blue-500 dark:bg-blue-400': !['success', 'error', 'warning'].includes(toast.type),
                     }"
                     :style="`animation: fb-toast-countdown ${toast.life}ms linear forwards`"></div>
            </div>
        </div>
    </template>
</div>
