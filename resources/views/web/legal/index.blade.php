@extends('layouts.web')

@section('title', 'Mentions légales')
@section('meta_description', 'Mentions légales, conditions générales de vente et politique de confidentialité du site Chouchoute-toi by Amande — Extensions de cils à domicile.')

@section('assets')
    @vite([
        'resources/css/web/legal/index.css',
        'resources/js/web/legal/index.js',
    ])
@endsection

@section('content')
    <section class="mx-auto max-w-[1336px] px-5 pb-20 pt-12">
        <p class="mb-3 flex items-center gap-2 text-sm font-normal uppercase tracking-[2px] text-wine">
            <x-icon.section-marker />
            Informations légales
        </p>

        <h1 class="mb-16 text-[38px] font-light leading-[1.5] text-wine max-md:mb-10 max-md:text-[28px]">
            Mentions légales
        </h1>

        <div class="max-w-3xl space-y-12">
            {{-- Éditeur --}}
            <div>
                <h2 class="mb-4 text-xl font-semibold text-dark">Éditeur du site</h2>
                <div class="space-y-1 text-base leading-[1.7] text-charcoal">
                    <p><span class="font-medium text-dark">Nom :</span> Amandine David-Cruz</p>
                    <p><span class="font-medium text-dark">Nom commercial :</span> Chouchoute-toi by Amande</p>
                    <p><span class="font-medium text-dark">Activité :</span> Soins de beauté — Extensions de cils à domicile</p>
                    <p><span class="font-medium text-dark">Statut :</span> Auto-entrepreneur (micro-entreprise)</p>
                    <p><span class="font-medium text-dark">SIRET :</span> 497 879 700 00035</p>
                    <p><span class="font-medium text-dark">Code APE :</span> 9602B — Soins de beauté</p>
                    <p><span class="font-medium text-dark">Adresse :</span> 261 rue des Tattes, 74500 Publier</p>
                    <p><span class="font-medium text-dark">Téléphone :</span> <a data-track-event="contact.phone.click" data-track-section="mentions-legales" href="tel:+33671637666" class="transition-colors hover:text-wine">06 71 63 76 66</a></p>
                    <p><span class="font-medium text-dark">Email :</span> <a href="mailto:dc.amandine@gmail.com" class="transition-colors hover:text-wine">dc.amandine@gmail.com</a></p>
                    <p><span class="font-medium text-dark">TVA :</span> Non assujettie à la TVA (article 293 B du CGI)</p>
                </div>
            </div>

            {{-- Hébergeur --}}
            <div>
                <h2 class="mb-4 text-xl font-semibold text-dark">Hébergement</h2>
                <div class="space-y-1 text-base leading-[1.7] text-charcoal">
                    <p><span class="font-medium text-dark">Hébergeur :</span> Hostinger International Ltd</p>
                    <p><span class="font-medium text-dark">Adresse :</span> 61 Lordou Vironos Street, 6023 Larnaca, Chypre</p>
                    <p><span class="font-medium text-dark">Site web :</span> <a href="https://www.hostinger.fr" target="_blank" rel="noopener noreferrer" class="transition-colors hover:text-wine">www.hostinger.fr</a></p>
                </div>
            </div>

            {{-- Conception --}}
            <div>
                <h2 class="mb-4 text-xl font-semibold text-dark">Conception et réalisation</h2>
                <div class="space-y-1 text-base leading-[1.7] text-charcoal">
                    <p><span class="font-medium text-dark">Développeur :</span> Micha Megret — Développement Web</p>
                </div>
            </div>

            {{-- Propriété intellectuelle --}}
            <div>
                <h2 class="mb-4 text-xl font-semibold text-dark">Propriété intellectuelle</h2>
                <p class="text-base leading-[1.7] text-charcoal">
                    L'ensemble du contenu de ce site (textes, images, logos, éléments graphiques) est protégé par le droit
                    d'auteur. Toute reproduction, représentation ou diffusion, en tout ou partie, du contenu de ce site sur
                    quelque support ou par tout procédé que ce soit est interdite sans l'autorisation écrite préalable de
                    Chouchoute-toi by Amande.
                </p>
            </div>

            {{-- Données personnelles --}}
            <div>
                <h2 class="mb-4 text-xl font-semibold text-dark">Données personnelles & RGPD</h2>
                <div class="space-y-4 text-base leading-[1.7] text-charcoal">
                    <p>
                        Le responsable du traitement des données personnelles est Amandine David-Cruz,
                        joignable à l'adresse <a href="mailto:dc.amandine@gmail.com" class="font-medium text-dark transition-colors hover:text-wine">dc.amandine@gmail.com</a>.
                    </p>
                    <p>
                        Les informations recueillies via le formulaire de contact (nom, email, téléphone, commune, message)
                        sont collectées dans le seul but de répondre à votre demande de rendez-vous ou d'information.
                        Elles ne sont ni cédées ni vendues à des tiers.
                    </p>
                    <p>
                        Conformément au Règlement Général sur la Protection des Données (RGPD) et à la loi Informatique
                        et Libertés, vous disposez d'un droit d'accès, de rectification, de suppression et de portabilité
                        de vos données. Pour exercer ces droits, adressez votre demande par email à
                        <a href="mailto:dc.amandine@gmail.com" class="font-medium text-dark transition-colors hover:text-wine">dc.amandine@gmail.com</a>.
                    </p>
                </div>
            </div>

            {{-- Cookies --}}
            <div>
                <h2 class="mb-4 text-xl font-semibold text-dark">Cookies</h2>
                <p class="text-base leading-[1.7] text-charcoal">
                    Ce site utilise uniquement des cookies techniques nécessaires à son bon fonctionnement
                    (session, sécurité CSRF). Aucun cookie publicitaire, de suivi ou d'analyse n'est déposé.
                    Aucune donnée personnelle n'est collectée à des fins commerciales.
                </p>
            </div>

            {{-- Responsabilité --}}
            <div>
                <h2 class="mb-4 text-xl font-semibold text-dark">Limitation de responsabilité</h2>
                <p class="text-base leading-[1.7] text-charcoal">
                    Chouchoute-toi by Amande s'efforce d'assurer l'exactitude des informations diffusées sur ce site,
                    mais ne saurait être tenue responsable d'éventuelles erreurs ou omissions. Les photographies
                    et illustrations présentées sont non contractuelles.
                </p>
            </div>
        </div>

        {{-- CGV --}}
        <div class="mt-24 max-w-3xl">
            <p class="mb-3 flex items-center gap-2 text-sm font-normal uppercase tracking-[2px] text-wine">
                <x-icon.section-marker />
                Conditions générales
            </p>

            <h2 class="mb-16 text-[38px] font-light leading-[1.5] text-wine max-md:mb-10 max-md:text-[28px]">
                Conditions générales de vente
            </h2>

            <div class="space-y-12">
                {{-- Article 1 --}}
                <div>
                    <h3 class="mb-3 text-lg font-semibold text-dark">Article 1 — Objet</h3>
                    <p class="text-base leading-[1.7] text-charcoal">
                        Les présentes conditions générales de vente (CGV) régissent les prestations de services
                        proposées par Amandine David-Cruz, exerçant sous le nom commercial Chouchoute-toi by Amande,
                        spécialisée dans les extensions de cils à domicile. Toute prise de rendez-vous implique
                        l'acceptation pleine et entière des présentes CGV.
                    </p>
                </div>

                {{-- Article 2 --}}
                <div>
                    <h3 class="mb-3 text-lg font-semibold text-dark">Article 2 — Prestations</h3>
                    <div class="space-y-3 text-base leading-[1.7] text-charcoal">
                        <p>
                            Les prestations proposées comprennent la pose complète d'extensions de cils (pose par pose,
                            volume russe, volume mixte), les remplissages et la dépose. Le détail des prestations et
                            tarifs en vigueur est consultable sur la page Prestations du site.
                        </p>
                        <p>
                            Les prestations sont réalisées au domicile de la cliente, dans la zone d'intervention
                            couvrant Thonon-les-Bains, Évian-les-Bains, Publier, Amphion, Maxilly, Neuvecelle
                            et alentours.
                        </p>
                    </div>
                </div>

                {{-- Article 3 --}}
                <div>
                    <h3 class="mb-3 text-lg font-semibold text-dark">Article 3 — Tarifs et paiement</h3>
                    <div class="space-y-3 text-base leading-[1.7] text-charcoal">
                        <p>
                            Les prix indiqués sur le site sont en euros TTC (TVA non applicable, article 293 B du CGI).
                            Ils sont susceptibles d'être modifiés à tout moment. Le tarif applicable est celui en
                            vigueur au moment de la prise de rendez-vous.
                        </p>
                        <p>
                            Le règlement s'effectue à la fin de la prestation par les moyens de paiement acceptés
                            (espèces, virement, paiement mobile).
                        </p>
                    </div>
                </div>

                {{-- Article 4 --}}
                <div>
                    <h3 class="mb-3 text-lg font-semibold text-dark">Article 4 — Prise de rendez-vous</h3>
                    <p class="text-base leading-[1.7] text-charcoal">
                        Les rendez-vous sont pris par téléphone, via le formulaire de contact du site ou par
                        messagerie sur les réseaux sociaux. Un rendez-vous est considéré comme confirmé après
                        accord mutuel sur la date, l'heure et la prestation souhaitée.
                    </p>
                </div>

                {{-- Article 5 --}}
                <div>
                    <h3 class="mb-3 text-lg font-semibold text-dark">Article 5 — Déroulement de la prestation</h3>
                    <div class="space-y-3 text-base leading-[1.7] text-charcoal">
                        <p>
                            La cliente s'engage à fournir un espace de travail adapté (position allongée, éclairage
                            suffisant, environnement calme). Tout le matériel professionnel nécessaire est apporté
                            par la technicienne.
                        </p>
                        <p>
                            La durée de la prestation varie selon le type de pose (généralement 1h30 à 2h30 pour
                            une pose complète, 1h à 1h30 pour un remplissage). Les yeux doivent rester fermés
                            pendant toute la durée de la pose.
                        </p>
                    </div>
                </div>

                {{-- Article 6 --}}
                <div>
                    <h3 class="mb-3 text-lg font-semibold text-dark">Article 6 — Contre-indications</h3>
                    <p class="text-base leading-[1.7] text-charcoal">
                        La prestation est déconseillée en cas d'allergie connue à la colle pour extensions de cils,
                        de conjonctivite, d'infection oculaire en cours, ou de traitement médical affectant les yeux.
                        En cas de doute, la cliente est invitée à consulter un médecin au préalable. La technicienne
                        se réserve le droit de refuser une prestation pour des raisons de santé ou de sécurité.
                    </p>
                </div>

                {{-- Article 7 --}}
                <div>
                    <h3 class="mb-3 text-lg font-semibold text-dark">Article 7 — Responsabilité</h3>
                    <p class="text-base leading-[1.7] text-charcoal">
                        Chouchoute-toi by Amande met en œuvre tout son savoir-faire pour garantir un résultat
                        de qualité. Toutefois, la tenue des extensions dépend également du respect des conseils
                        d'entretien communiqués après la pose. Aucun remboursement ne pourra être exigé en cas
                        de non-respect de ces recommandations.
                    </p>
                </div>

                {{-- Article 8 --}}
                <div>
                    <h3 class="mb-3 text-lg font-semibold text-dark">Article 8 — Droit applicable</h3>
                    <p class="text-base leading-[1.7] text-charcoal">
                        Les présentes CGV sont soumises au droit français. En cas de litige, une solution amiable
                        sera recherchée avant toute action judiciaire. À défaut, les tribunaux compétents seront
                        ceux du ressort du domicile de la prestataire.
                    </p>
                </div>

                {{-- Article 9 --}}
                <div>
                    <h3 class="mb-3 text-lg font-semibold text-dark">Article 9 — Médiation</h3>
                    <p class="text-base leading-[1.7] text-charcoal">
                        Conformément aux articles L.616-1 et R.616-1 du Code de la consommation, en cas de litige
                        non résolu, la cliente peut recourir gratuitement au service de médiation de la consommation.
                        Le médiateur compétent peut être saisi via la plateforme européenne de règlement en ligne
                        des litiges :
                        <a href="https://ec.europa.eu/consumers/odr" target="_blank" rel="noopener noreferrer" class="font-medium text-dark transition-colors hover:text-wine">ec.europa.eu/consumers/odr</a>.
                    </p>
                </div>
            </div>
        </div>
    </section>
@endsection
