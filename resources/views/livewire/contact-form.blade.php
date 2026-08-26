<section class="mb-[150px] max-md:mb-20 pt-12">
    <div class="mx-auto max-w-[1336px] px-5">
        <p class="mb-3 flex items-center gap-2 text-sm font-normal uppercase tracking-[2px] text-wine">
            <x-icon.section-marker />
            Contact
        </p>

        <h1 class="mb-16 text-[38px] font-light leading-[1.5] text-wine max-md:mb-10 max-md:text-[28px]">
            Prenez rendez-vous
        </h1>

        <div class="grid grid-cols-2 gap-16 max-md:grid-cols-1 max-md:gap-10">
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
                @if ($sent)
                    <div class="flex flex-col items-center justify-center rounded-xl bg-sand/40 px-8 py-16 text-center">
                        <div class="mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-wine/10">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-wine">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                        </div>
                        <h3 class="mb-2 text-xl font-semibold text-dark">Message envoyé</h3>
                        <p class="mb-8 max-w-sm text-sm leading-relaxed text-charcoal">
                            Merci pour votre demande ! Je vous répondrai dans les plus brefs délais.
                        </p>
                        <button wire:click="resetForm" type="button" class="inline-flex items-center gap-2 text-sm font-medium text-wine transition-opacity hover:opacity-70">
                            Envoyer un autre message
                            <x-icon.arrow-top-right />
                        </button>
                    </div>
                @else
                    <form wire:submit="send" class="space-y-5">

                        <div>
                            <label for="name" class="mb-1.5 block text-xs font-medium uppercase tracking-[1.5px] text-charcoal/60">Prénom et nom *</label>
                            <input wire:model="name" type="text" id="name" class="w-full rounded-[10px] border border-black/10 bg-white px-4 py-3 text-sm text-dark outline-none transition-all focus:border-wine/30 focus:ring-1 focus:ring-wine/10">
                            @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="email" class="mb-1.5 block text-xs font-medium uppercase tracking-[1.5px] text-charcoal/60">Email *</label>
                            <input wire:model="email" type="email" id="email" class="w-full rounded-[10px] border border-black/10 bg-white px-4 py-3 text-sm text-dark outline-none transition-all focus:border-wine/30 focus:ring-1 focus:ring-wine/10">
                            @error('email') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="phone" class="mb-1.5 block text-xs font-medium uppercase tracking-[1.5px] text-charcoal/60">Téléphone *</label>
                            <input wire:model="phone" type="tel" id="phone" class="w-full rounded-[10px] border border-black/10 bg-white px-4 py-3 text-sm text-dark outline-none transition-all focus:border-wine/30 focus:ring-1 focus:ring-wine/10">
                            @error('phone') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="commune" class="mb-1.5 block text-xs font-medium uppercase tracking-[1.5px] text-charcoal/60">Commune</label>
                            <select wire:model="commune" id="commune" class="w-full rounded-[10px] border border-black/10 bg-white px-4 py-3 text-sm text-dark outline-none transition-all focus:border-wine/30 focus:ring-1 focus:ring-wine/10">
                                <option value="">Sélectionnez...</option>
                                <option value="Évian-les-Bains">Évian-les-Bains</option>
                                <option value="Thonon-les-Bains">Thonon-les-Bains</option>
                                <option value="Publier">Publier</option>
                                <option value="Amphion">Amphion</option>
                                <option value="Maxilly">Maxilly</option>
                                <option value="Neuvecelle">Neuvecelle</option>
                                <option value="Autre">Autre</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-5 max-sm:grid-cols-1">
                            <div>
                                <label for="volume" class="mb-1.5 block text-xs font-medium uppercase tracking-[1.5px] text-charcoal/60">Type de volume</label>
                                <select wire:model.live="volume" id="volume" class="w-full rounded-[10px] border border-black/10 bg-white px-4 py-3 text-sm text-dark outline-none transition-all focus:border-wine/30 focus:ring-1 focus:ring-wine/10">
                                    <option value="">Sélectionnez...</option>
                                    @foreach (config('tarifs.categories') as $slug => $categorie)
                                        <option value="{{ $slug }}">{{ $categorie['nom'] }}</option>
                                    @endforeach
                                    <option value="depose">Dépose</option>
                                    <option value="indecise">Je ne sais pas encore</option>
                                </select>
                            </div>

                            <div>
                                <label for="prestation" class="mb-1.5 block text-xs font-medium uppercase tracking-[1.5px] text-charcoal/60">Type de prestation</label>
                                <select wire:model="prestation" id="prestation" class="w-full rounded-[10px] border border-black/10 bg-white px-4 py-3 text-sm text-dark outline-none transition-all focus:border-wine/30 focus:ring-1 focus:ring-wine/10 disabled:opacity-50" @if (empty($this->prestationOptions)) disabled @endif>
                                    @if (empty($this->prestationOptions))
                                        <option value="">{{ $volume ? 'Choisissez une prestation' : 'Choisissez d\'abord un volume' }}</option>
                                    @else
                                        <option value="">Sélectionnez...</option>
                                        @foreach ($this->prestationOptions as $option)
                                            <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>

                        <div>
                            <label for="message" class="mb-1.5 block text-xs font-medium uppercase tracking-[1.5px] text-charcoal/60">Message (optionnel)</label>
                            <textarea wire:model="message" id="message" rows="4" class="w-full resize-none rounded-[10px] border border-black/10 bg-white px-4 py-3 text-sm text-dark outline-none transition-all focus:border-wine/30 focus:ring-1 focus:ring-wine/10"></textarea>
                        </div>

                        <button type="submit" class="inline-flex items-center gap-2 rounded-[10px] bg-black px-[22px] py-3 text-sm text-white transition-colors duration-300 hover:bg-dark disabled:opacity-50" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="send">Envoyer ma demande</span>
                            <span wire:loading.flex wire:target="send" class="inline-flex items-center gap-2">
                                <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                Envoi en cours...
                            </span>
                            <x-icon.arrow-top-right wire:loading.remove wire:target="send" />
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</section>
