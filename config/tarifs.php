<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Grille tarifaire · Chouchoute toi
    |--------------------------------------------------------------------------
    |
    | Accessible via config('tarifs.categories'), config('tarifs.depose'), etc.
    | Chaque categorie contient ses prestations (pose + remplissages).
    |
    */

    'categories' => [

        'naturelle' => [
            'nom' => 'Naturelle',
            'slug' => 'naturelle',
            'description' => 'Un regard sublimé en toute discrétion. La pose naturelle dépose une extension sur chaque cil pour un effet « j\'ai de beaux cils naturellement ».',
            'pose' => [
                'nom' => 'Pose complète "Naturelle"',
                'duree' => '2h',
                'prix' => 65,
            ],
            'remplissages' => [
                [
                    'nom' => 'Remplissage 3 semaines',
                    'description' => 'Entretien de votre pose initiale à maximum 21 jours',
                    'duree' => '2h',
                    'prix' => 40,
                ],
                [
                    'nom' => 'Remplissage 4 semaines',
                    'description' => 'Entretien de la pose initiale à maximum 28 jours',
                    'duree' => '2h',
                    'prix' => 50,
                ],
            ],
        ],

        'volume-leger' => [
            'nom' => 'Volume Léger',
            'slug' => 'volume-leger',
            'description' => 'Un volume doux et aérien. Des bouquets légers de 2 à 3 extensions par cil naturel pour un regard ouvert et lumineux.',
            'pose' => [
                'nom' => 'Pose complète "Volume Léger"',
                'duree' => '2h',
                'prix' => 75,
            ],
            'remplissages' => [
                [
                    'nom' => 'Remplissage 3 semaines',
                    'description' => 'Entretien de la pose initiale à maximum 21 jours',
                    'duree' => '2h',
                    'prix' => 45,
                ],
                [
                    'nom' => 'Remplissage 4 semaines',
                    'description' => 'Entretien de la pose initiale à maximum 28 jours',
                    'duree' => '2h',
                    'prix' => 55,
                ],
            ],
        ],

        'volume-mixte' => [
            'nom' => 'Volume Mixte',
            'slug' => 'volume-mixte',
            'description' => 'Le meilleur des deux mondes. Un mélange de cil à cil et de volume pour un regard à la fois naturel et étoffé.',
            'pose' => [
                'nom' => 'Pose complète "Volume Mixte"',
                'duree' => '2h 15min',
                'prix' => 85,
            ],
            'remplissages' => [
                [
                    'nom' => 'Remplissage 3 semaines',
                    'description' => 'Entretien de la pose initiale à maximum 21 jours',
                    'duree' => '2h 15min',
                    'prix' => 50,
                ],
                [
                    'nom' => 'Remplissage 4 semaines',
                    'description' => 'Entretien de la pose initiale à maximum 28 jours',
                    'duree' => '2h 15min',
                    'prix' => 60,
                ],
            ],
        ],

        'volume-intense' => [
            'nom' => 'Volume Intense',
            'slug' => 'volume-intense',
            'description' => 'Pour un regard spectaculaire. Des éventails denses de 4 à 6 extensions par cil naturel pour un effet glamour et intense.',
            'pose' => [
                'nom' => 'Pose complète "Volume Intense"',
                'duree' => '2h 30min',
                'prix' => 95,
            ],
            'remplissages' => [
                [
                    'nom' => 'Remplissage 2 semaines',
                    'description' => 'Entretien de la pose initiale à maximum 15 jours',
                    'duree' => '2h',
                    'prix' => 45,
                ],
                [
                    'nom' => 'Remplissage 3 semaines',
                    'description' => 'Entretien de la pose initiale à maximum 21 jours',
                    'duree' => '2h 15min',
                    'prix' => 55,
                ],
                [
                    'nom' => 'Remplissage 4 semaines',
                    'description' => 'Entretien de la pose initiale à maximum 28 jours',
                    'duree' => '2h 15min',
                    'prix' => 65,
                ],
            ],
        ],

    ],

    'depose' => [
        'nom' => 'Dépose des extensions de cils',
        'description' => 'Retrait des extensions avec produit dissolvant et shampooing pour cils',
        'duree' => '1h',
        'prix' => 20,
    ],

    'mention' => 'Les tarifs sont indicatifs et peuvent varier selon le résultat souhaité. Un devis personnalisé vous sera proposé lors de la prise de contact.',

];
