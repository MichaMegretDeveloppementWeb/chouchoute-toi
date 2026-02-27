<footer class="bg-black text-white">
    <div class="mx-auto max-w-[1336px] px-5 py-16 lg:py-20">
        <div class="grid grid-cols-1 gap-10 sm:grid-cols-2 lg:grid-cols-4 lg:gap-8">
            {{-- Col 1 — Marque --}}
            <div>
                <a href="{{ route('home') }}" class="text-lg font-bold tracking-widest uppercase">CHOUCHOUTE-TOI</a>
                <p class="mt-3 text-sm leading-relaxed text-white/60">
                    Extensions de cils à domicile sur le bassin lémanique.
                </p>
                <p class="mt-6 text-xs text-white/40">
                    &copy; {{ date('Y') }} Chouchoute-toi by Amande. Tous droits réservés.
                </p>
            </div>

            {{-- Col 2 — Contact --}}
            <div>
                <h3 class="mb-4 text-xs font-medium uppercase tracking-wider text-white/40">Contact</h3>
                <ul class="space-y-3 text-sm">
                    <li>
                        <a href="tel:+33671637666" class="text-white/70 transition-colors hover:text-white">
                            06 71 63 76 66
                        </a>
                    </li>
                    <li>
                        <a href="mailto:dc.amandine@gmail.com" class="text-white/70 transition-colors hover:text-white">
                            dc.amandine@gmail.com
                        </a>
                    </li>
                    <li class="flex items-center gap-3">
                        <a href="https://www.instagram.com/chouchoutetoibyamande/" target="_blank" rel="noopener noreferrer" class="text-white/70 transition-colors hover:text-white" aria-label="Instagram">
                            <x-icon.instagram class="h-5 w-5" />
                        </a>
                        <a href="https://www.facebook.com/p/Chouchoute-Toi-Ongles-Cils-by-Amande-61551795336766/" target="_blank" rel="noopener noreferrer" class="text-white/70 transition-colors hover:text-white" aria-label="Facebook">
                            <x-icon.facebook class="h-5 w-5" />
                        </a>
                    </li>
                </ul>
            </div>

            {{-- Col 3 — Navigation --}}
            <div>
                <h3 class="mb-4 text-xs font-medium uppercase tracking-wider text-white/40">Navigation</h3>
                <ul class="space-y-3 text-sm">
                    <li>
                        <a href="{{ route('prestations') }}" class="flex w-full items-center justify-between border-b border-white/10 pb-3 text-white/70 transition-colors hover:text-white">
                            Prestations <x-icon.arrow-top-right width="12" height="12" class="opacity-40" />
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('about') }}" class="flex w-full items-center justify-between border-b border-white/10 pb-3 text-white/70 transition-colors hover:text-white">
                            À propos <x-icon.arrow-top-right width="12" height="12" class="opacity-40" />
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('reviews') }}" class="flex w-full items-center justify-between border-b border-white/10 pb-3 text-white/70 transition-colors hover:text-white">
                            Avis <x-icon.arrow-top-right width="12" height="12" class="opacity-40" />
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('contact') }}" class="flex w-full items-center justify-between border-b border-white/10 pb-3 text-white/70 transition-colors hover:text-white">
                            Contact <x-icon.arrow-top-right width="12" height="12" class="opacity-40" />
                        </a>
                    </li>
                </ul>
            </div>

            {{-- Col 4 — Informations --}}
            <div>
                <h3 class="mb-4 text-xs font-medium uppercase tracking-wider text-white/40">Informations</h3>
                <ul class="space-y-3 text-sm">
                    <li>
                        <a href="{{ route('legal') }}" class="flex w-full items-center justify-between border-b border-white/10 pb-3 text-white/70 transition-colors hover:text-white">
                            Mentions légales <x-icon.arrow-top-right width="12" height="12" class="opacity-40" />
                        </a>
                    </li>
                    <li class="pt-2 text-xs leading-relaxed text-white/30">
                        Lundi – Vendredi : 9h – 17h
                    </li>
                </ul>
            </div>
        </div>
    </div>
</footer>
