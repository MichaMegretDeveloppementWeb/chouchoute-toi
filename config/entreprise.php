<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Identite de l'entreprise · Chouchoute-toi
    |--------------------------------------------------------------------------
    |
    | Accessible via config('entreprise.nom'), config('entreprise.adresse'), etc.
    |
    | Ces valeurs etaient ecrites a la main dans chaque vue qui en avait besoin :
    | le telephone paraissait a neuf endroits, l'adresse postale a six, et les
    | six villes desservies a trois. Une correction en oubliait toujours un.
    |
    | Lu par `App\Support\Seo\SiteGraph`, qui en fait le graphe Schema.org du
    | site. Les vues qui affichent ces memes valeurs a l'ecran (l'en-tete, le
    | pied de page, les mentions legales) ne sont pas encore branchees ici.
    |
    */

    'nom' => 'Chouchoute-toi by Amande',

    // Le nom sous lequel le site se presente, plus court que la raison sociale.
    'nom_court' => 'Chouchoute-toi',

    'slogan' => 'Sublimez votre regard, cil après cil',

    'description' => 'Extensions de cils à domicile sur Évian-les-Bains, Thonon-les-Bains et le bassin lémanique. Pose cil à cil, volume russe, volume mixte et remplissage par technicienne certifiée.',

    'telephone' => '+33671637666',

    'email' => 'dc.amandine@gmail.com',

    // Chemins relatifs · `asset()` leur est applique a la lecture, pour que le
    // domaine ne soit fige nulle part.
    'image' => 'images/og-image.jpg',

    'adresse' => [
        'rue' => '261 rue des Tattes',
        'code_postal' => '74500',
        'ville' => 'Publier',
        'region' => 'Haute-Savoie',
        'pays' => 'FR',
    ],

    'coordonnees' => [
        'latitude' => 46.3925,
        'longitude' => 6.5456,
    ],

    /*
    | Les horaires, groupes par plage · six jours aux memes heures font une
    | ligne et non six, ce qui est la forme que tout validateur affiche.
    |
    | Les jours suivent le vocabulaire de Schema.org, en anglais · ce sont des
    | identifiants, pas du texte affiche.
    */
    'horaires' => [
        [
            'jours' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
            'ouvre' => '09:00',
            'ferme' => '17:00',
        ],
    ],

    'villes_desservies' => [
        'Évian-les-Bains',
        'Thonon-les-Bains',
        'Publier',
        'Amphion',
        'Maxilly',
        'Neuvecelle',
    ],

    'reseaux' => [
        'https://www.instagram.com/chouchoutetoibyamande/',
        'https://www.facebook.com/p/Chouchoute-Toi-Ongles-Cils-by-Amande-61551795336766/',
    ],

    'gamme_de_prix' => '€€',

    'paiements' => 'Espèces, Virement, Paiement mobile',

    'devise' => 'EUR',

    'savoir_faire' => [
        'Extensions de cils',
        'Pose cil à cil',
        'Volume russe',
        'Volume mixte',
        'Remplissage extensions de cils',
        'Dépose extensions de cils',
    ],

    /*
    | La personne derriere l'entreprise · elle est le `founder` de l'entreprise
    | et le sujet de la page « A propos ». Un seul nœud, designe deux fois.
    */
    'fondatrice' => [
        'nom' => 'Amandine David-Cruz',
        'fonction' => 'Technicienne certifiée en extensions de cils',
        'description' => 'Passionnée par la beauté du regard, Amandine met son savoir-faire au service de ses clientes avec des extensions de cils naturelles et sur-mesure, directement à domicile.',
        'portrait' => 'images/about/portrait-amandine.webp',
    ],

];
