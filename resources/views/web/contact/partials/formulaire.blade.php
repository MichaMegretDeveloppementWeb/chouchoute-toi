<section class="mb-[150px] max-md:mb-20 pt-12">
    <div class="mx-auto max-w-[1336px] px-5">
        <p class="mb-3 flex items-center gap-2 text-sm font-normal uppercase tracking-[2px] text-wine">
            <x-icon.section-marker />
            Contact
        </p>

        <h1 class="mb-16 text-[38px] font-light leading-[1.5] text-wine max-md:mb-10 max-md:text-[28px]">
            Prenez rendez-vous
        </h1>

        <div class="grid grid-cols-2 gap-16 max-md:grid-cols-1 max-md:gap-10" data-animate>
            {{-- Informations --}}
            <div>
                <h2 class="mb-2 text-xl font-semibold text-dark">Chouchoute-toi by Amande</h2>
                <p class="mb-8 text-base text-charcoal">Extensions de cils à domicile</p>

                <div class="space-y-5">
                    <div class="flex items-start gap-4">
                        <x-icon.phone class="mt-0.5 text-wine" />
                        <div>
                            <p class="mb-1 text-xs font-medium uppercase tracking-[1.5px] text-charcoal/60">Téléphone</p>
                            <a href="tel:+33671637666" class="text-base text-dark transition-colors hover:text-wine">06 71 63 76 66</a>
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <x-icon.email class="mt-0.5 text-wine" />
                        <div>
                            <p class="mb-1 text-xs font-medium uppercase tracking-[1.5px] text-charcoal/60">Email</p>
                            <a href="mailto:dc.amandine@gmail.com" class="text-base text-dark transition-colors hover:text-wine">dc.amandine@gmail.com</a>
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <x-icon.instagram class="mt-0.5 text-wine" />
                        <div>
                            <p class="mb-1 text-xs font-medium uppercase tracking-[1.5px] text-charcoal/60">Réseaux sociaux</p>
                            <div class="flex items-center gap-3">
                                <a href="https://www.instagram.com/chouchoutetoibyamande/" target="_blank" rel="noopener noreferrer" class="text-base text-dark transition-colors hover:text-wine">Instagram</a>
                                <span class="text-charcoal/30">·</span>
                                <a href="https://www.facebook.com/p/Chouchoute-Toi-Ongles-Cils-by-Amande-61551795336766/" target="_blank" rel="noopener noreferrer" class="text-base text-dark transition-colors hover:text-wine">Facebook</a>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <x-icon.clock class="mt-0.5 text-wine" />
                        <div>
                            <p class="mb-1 text-xs font-medium uppercase tracking-[1.5px] text-charcoal/60">Horaires</p>
                            <p class="text-base text-dark">Lundi – Vendredi : 9h – 17h</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Formulaire --}}
            <div>
                <form action="#" method="POST" class="space-y-5">
                    @csrf

                    <div>
                        <label for="name" class="mb-1.5 block text-xs font-medium uppercase tracking-[1.5px] text-charcoal/60">Prénom et nom *</label>
                        <input type="text" id="name" name="name" required class="w-full rounded-[10px] border border-black/10 bg-white px-4 py-3 text-sm text-dark outline-none transition-all focus:border-wine/30 focus:ring-1 focus:ring-wine/10">
                    </div>

                    <div>
                        <label for="email" class="mb-1.5 block text-xs font-medium uppercase tracking-[1.5px] text-charcoal/60">Email *</label>
                        <input type="email" id="email" name="email" required class="w-full rounded-[10px] border border-black/10 bg-white px-4 py-3 text-sm text-dark outline-none transition-all focus:border-wine/30 focus:ring-1 focus:ring-wine/10">
                    </div>

                    <div>
                        <label for="phone" class="mb-1.5 block text-xs font-medium uppercase tracking-[1.5px] text-charcoal/60">Téléphone *</label>
                        <input type="tel" id="phone" name="phone" required class="w-full rounded-[10px] border border-black/10 bg-white px-4 py-3 text-sm text-dark outline-none transition-all focus:border-wine/30 focus:ring-1 focus:ring-wine/10">
                    </div>

                    <div>
                        <label for="commune" class="mb-1.5 block text-xs font-medium uppercase tracking-[1.5px] text-charcoal/60">Commune</label>
                        <select id="commune" name="commune" class="w-full rounded-[10px] border border-black/10 bg-white px-4 py-3 text-sm text-dark outline-none transition-all focus:border-wine/30 focus:ring-1 focus:ring-wine/10">
                            <option value="">Sélectionnez...</option>
                            <option value="evian">Évian-les-Bains</option>
                            <option value="thonon">Thonon-les-Bains</option>
                            <option value="publier">Publier</option>
                            <option value="amphion">Amphion</option>
                            <option value="maxilly">Maxilly</option>
                            <option value="neuvecelle">Neuvecelle</option>
                            <option value="autre">Autre</option>
                        </select>
                    </div>

                    @php
                        $categories = config('tarifs.categories');
                        $depose = config('tarifs.depose');
                        $tarifsJson = collect($categories)->map(fn ($cat) => [
                            'nom' => $cat['nom'],
                            'prestations' => array_merge(
                                [['value' => 'pose-complete', 'label' => 'Pose complète']],
                                array_map(fn ($r) => [
                                    'value' => \Illuminate\Support\Str::slug($r['nom']),
                                    'label' => $r['nom'],
                                ], $cat['remplissages'])
                            ),
                        ])->toJson();
                    @endphp

                    <div class="grid grid-cols-2 gap-5 max-sm:grid-cols-1" data-prestation-selects data-tarifs="{{ $tarifsJson }}">
                        <div>
                            <label for="volume" class="mb-1.5 block text-xs font-medium uppercase tracking-[1.5px] text-charcoal/60">Type de volume</label>
                            <select id="volume" name="volume" class="w-full rounded-[10px] border border-black/10 bg-white px-4 py-3 text-sm text-dark outline-none transition-all focus:border-wine/30 focus:ring-1 focus:ring-wine/10" data-volume-select>
                                <option value="">Sélectionnez...</option>
                                @foreach ($categories as $slug => $categorie)
                                    <option value="{{ $slug }}">{{ $categorie['nom'] }}</option>
                                @endforeach
                                <option value="depose">Dépose</option>
                                <option value="indecise">Je ne sais pas encore</option>
                            </select>
                        </div>

                        <div>
                            <label for="prestation" class="mb-1.5 block text-xs font-medium uppercase tracking-[1.5px] text-charcoal/60">Type de prestation</label>
                            <select id="prestation" name="prestation" class="w-full rounded-[10px] border border-black/10 bg-white px-4 py-3 text-sm text-dark outline-none transition-all focus:border-wine/30 focus:ring-1 focus:ring-wine/10 disabled:opacity-50" data-prestation-select disabled>
                                <option value="">Choisissez d'abord un volume</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="message" class="mb-1.5 block text-xs font-medium uppercase tracking-[1.5px] text-charcoal/60">Message (optionnel)</label>
                        <textarea id="message" name="message" rows="4" class="w-full resize-none rounded-[10px] border border-black/10 bg-white px-4 py-3 text-sm text-dark outline-none transition-all focus:border-wine/30 focus:ring-1 focus:ring-wine/10"></textarea>
                    </div>

                    <button type="submit" class="inline-flex items-center gap-2 rounded-[10px] bg-black px-[22px] py-3 text-sm text-white transition-colors duration-300 hover:bg-dark">
                        Envoyer ma demande
                        <x-icon.arrow-top-right />
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>
