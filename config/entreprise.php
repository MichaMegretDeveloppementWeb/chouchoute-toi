<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Business identity
    |--------------------------------------------------------------------------
    |
    | Read by App\Services\SiteGraphService, which turns these values into the
    | site's Schema.org graph.
    |
    | The footer prints the phone number and the email address by hand: changing
    | them here does not change them there.
    |
    */

    'nom' => 'Chouchoute-toi by Amande',

    // The name the site presents itself under, shorter than the legal one.
    'nom_court' => 'Chouchoute-toi',

    'slogan' => 'Sublimez votre regard, cil après cil',

    'description' => 'Extensions de cils à domicile sur Évian-les-Bains, Thonon-les-Bains et le bassin lémanique. Pose cil à cil, volume russe, volume mixte et remplissage par technicienne certifiée.',

    'telephone' => '+33671637666',

    'email' => 'dc.amandine@gmail.com',

    // Relative paths: `asset()` is applied on read, so the domain is frozen
    // nowhere.
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
    | Opening hours, grouped by range: days sharing the same hours make one
    | entry, which is the shape every validator reads.
    |
    | The days are Schema.org identifiers, not displayed text: they stay in
    | English.
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
    | The person behind the business: the business's `founder` and the subject
    | of the « À propos » page. One node, named twice.
    */
    'fondatrice' => [
        'nom' => 'Amandine David-Cruz',
        'fonction' => 'Technicienne certifiée en extensions de cils',
        'description' => 'Passionnée par la beauté du regard, Amandine met son savoir-faire au service de ses clientes avec des extensions de cils naturelles et sur-mesure, directement à domicile.',
        'portrait' => 'images/about/portrait-amandine.webp',
    ],

];
