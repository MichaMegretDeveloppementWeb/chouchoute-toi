<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Integration
    |--------------------------------------------------------------------------
    |
    | Decided once, by the developer installing the package. Everything here
    | describes how the package mounts inside its host, never how the business
    | behaves. Business settings live in the database and are edited from the
    | interface: see the "defaults" section at the bottom for their seed values.
    |
    */

    'timezone' => env('BOOKING_TIMEZONE', 'Europe/Paris'),

    /*
    | Where the package writes its errors.
    |
    | Null by default: the package writes where the application already writes.
    | A host that centralises to an external service is not fragmented without
    | having asked for it.
    |
    | The package also registers a channel named "booking", to
    | storage/logs/booking.log, which it is enough to name here to use. It never
    | overwrites a channel of the same name already defined by the host.
    */
    'logging' => [
        'channel' => env('BOOKING_LOG_CHANNEL'),

        // The level of the shipped channel, when that is the one named above.
        // "notice" and not "error": it is the level of the functional audit,
        // and raising it would make the traceability disappear.
        'level' => env('BOOKING_LOG_LEVEL', 'notice'),

        // How many days the shipped channel keeps its files.
        'days' => (int) env('BOOKING_LOG_DAYS', 90),
    ],

    /*
    | A client's attachments.
    |
    | The disk is private and stays private: these are photographs of people,
    | and a public path is a link that outlives any check on rights. The
    | download goes through a route, behind the same guard as the screens.
    */
    'attachments' => [
        'disk' => env('BOOKING_ATTACHMENTS_DISK', 'local'),

        // What a phone photograph weighs today, with room to spare.
        'max_size_kb' => 8192,

        // Per client, the folder being theirs and spanning as many visits as
        // they make. High enough never to be met in normal use, low enough to
        // stop a folder growing without anyone deciding it should.
        'max_per_client' => 100,

        // What can be picked in the file browser.
        'extensions' => ['jpg', 'jpeg', 'png', 'webp', 'heic', 'pdf'],

        // And what the file must actually be. An extension is an entry:
        // renaming a script to .png satisfies it. The type is guessed from the
        // content, and it is the type that decides, the extension only filtering
        // the picker window.
        'mimes' => [
            'image/jpeg',
            'image/png',
            'image/webp',
            'image/heic',
            'image/heif',
            'application/pdf',
        ],
    ],

    'admin' => [
        'route_prefix' => 'admin/agenda',
        'route_name' => 'booking.admin.',

        // Routes are registered outside the host's own groups, so this stack
        // must include the session middleware. Whatever is listed here is also
        // registered as Livewire persistent middleware, otherwise it would not
        // apply to component updates.
        'middleware' => ['web', 'auth:admin'],

        // The guard the screens run behind, used to name the actor of a change
        // in the audit journal. Null falls back to the application's default
        // guard, which is rarely the right one for a back office.
        'guard' => 'admin',

        // A view of the host the package's screens extend. Null makes the
        // package render the full page itself.
        'layout' => 'layouts.booking-admin',

        // The section that layout yields. Hosts name it differently, and a
        // screen rendered into the wrong section shows up blank rather than
        // failing, so it is configurable rather than assumed.
        'layout_section' => 'content',
    ],

    /*
    | L'hebergeur, pour les mentions legales.
    |
    | En configuration et non en reglage · il decrit le serveur, pas
    | l'etablissement. La cliente ne sait pas qui heberge son site, et un
    | changement d'hebergeur se regle ici.
    |
    | A remplir dans le `.env` · `booking:check` le reclame tant qu'il manque,
    | la mention etant obligatoire des qu'une page est publique.
    */
    'legal' => [
        'host' => [
            'name' => env('BOOKING_LEGAL_HOST_NAME'),
            'address' => env('BOOKING_LEGAL_HOST_ADDRESS'),
            'phone' => env('BOOKING_LEGAL_HOST_PHONE'),
        ],
    ],

    'public' => [
        'route_prefix' => 'reservation',
        'route_name' => 'booking.public.',
        'middleware' => ['web'],

        // Null : le paquet rend la page entière lui-même. Nommer un gabarit du
        // site l'y ferait entrer — décision à prendre le jour où la page
        // publique doit ressembler au reste de chouchoute-toi.fr.
        'layout' => null,
        'layout_section' => 'content',

        // Le paquet enregistre sa route publique. À faux, ce serait à nous de
        // la monter.
        'routes' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Assets
    |--------------------------------------------------------------------------
    |
    | The host compiles; the package only ships sources. Its admin screens are
    | styled by falcon/ui-kit, so they need no entrypoint of their own: giving
    | them one would load Tailwind and the kit preset twice on the same page.
    | What they need is for the host's back-office entrypoint to scan the
    | package's views, which booking:install declares once.
    |
    */

    'assets' => [
        // The file booking:install declares the package's views to, so the
        // host's Tailwind build generates the classes they use.
        'admin_css_entrypoint' => 'resources/css/ui-kit.css',

        // What the fallback shell loads when no host layout is configured.
        // A host that names its own layout brings its own assets and ignores
        // this entirely, and must load the admin script from that layout.
        //
        // The package's own script is NOT listed here by default: naming it
        // before it has been added to the Vite input would make @vite raise on
        // a manifest that cannot contain it yet. Add it once both are done,
        // otherwise the fallback shell renders an agenda with no calendar:
        //
        //     'vendor/falcon/booking/resources/js/booking-admin.js',
        //
        // booking:install prints the line with the path already resolved, and
        // booking:check reports it missing from the build.
        'admin_entrypoints' => [
            'resources/css/ui-kit.css',
            'resources/js/ui-kit.js',
        ],

        // La feuille de la page publique, que le paquet possède de bout en
        // bout. Nommée ici **après** avoir été ajoutée à l'input de Vite, sans
        // quoi @vite lèverait sur un manifest qui ne peut pas la contenir.
        'public_entrypoints' => [
            'packages/falcon-booking/resources/css/booking-public.css',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Capabilities
    |--------------------------------------------------------------------------
    */

    // `multi_practitioner` used to live here. Nothing read it: the agenda always
    // picked the first active practitioner, so turning it on promised a
    // behaviour that did not exist. The schema keeps `practitioner_id`, which is
    // what makes the capability possible later; the switch comes back the day it
    // is built. `booking:check` reports a second active practitioner in the
    // meantime, since one of the two would be invisible.

    'campaigns' => [
        'enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Providers
    |--------------------------------------------------------------------------
    |
    | Only one SMS driver is active at a time, chosen by the developer. Several
    | payment drivers may be active at once, and the client picks at checkout.
    |
    | Secrets are not read from here: they live encrypted in the settings table
    | so they can be rotated from the interface. The env values below are only
    | picked up once, at install time, when the settings table is still empty.
    |
    */

    'sms' => [
        'driver' => env('BOOKING_SMS_DRIVER', 'log'),

        'drivers' => [
            'log' => ['channel' => env('BOOKING_SMS_LOG_CHANNEL')],
            'null' => [],
            'envoyer_sms_pro' => [
                'seed_api_key' => env('BOOKING_SMS_API_KEY'),
                'seed_sender_name' => env('BOOKING_SMS_SENDER'),
            ],
        ],
    ],

    'payment' => [
        'enabled_drivers' => [],
        'webhook_prefix' => 'booking/webhooks',
        'currency' => 'eur',
    ],

    /*
    |--------------------------------------------------------------------------
    | Telephone
    |--------------------------------------------------------------------------
    |
    | Numbers are stored in E.164. What a screen needs on top of that is a way to
    | pick a dialling code without typing it, and a length to check against.
    |
    | A list rather than a library: validating a number properly is a job for
    | metadata nobody maintains by hand, and this package does not pretend to do
    | it. What it does is refuse a number that cannot be one, and leave the rest
    | to whoever dials it.
    |
    | `digits` is the national number, dialling code excluded, and a national
    | trunk zero is dropped before counting. Add a country by adding a line.
    |
    */

    'phone' => [
        'default_country' => env('BOOKING_PHONE_COUNTRY', 'FR'),

        'countries' => [
            'FR' => ['name' => 'France', 'flag' => '🇫🇷', 'code' => '+33', 'digits' => 9],
            'BE' => ['name' => 'Belgique', 'flag' => '🇧🇪', 'code' => '+32', 'digits' => 9],
            'CH' => ['name' => 'Suisse', 'flag' => '🇨🇭', 'code' => '+41', 'digits' => 9],
            'LU' => ['name' => 'Luxembourg', 'flag' => '🇱🇺', 'code' => '+352', 'digits' => 9],
            'MC' => ['name' => 'Monaco', 'flag' => '🇲🇨', 'code' => '+377', 'digits' => 8],
            'DE' => ['name' => 'Allemagne', 'flag' => '🇩🇪', 'code' => '+49', 'digits' => 10],
            'IT' => ['name' => 'Italie', 'flag' => '🇮🇹', 'code' => '+39', 'digits' => 10],
            'ES' => ['name' => 'Espagne', 'flag' => '🇪🇸', 'code' => '+34', 'digits' => 9],
            'PT' => ['name' => 'Portugal', 'flag' => '🇵🇹', 'code' => '+351', 'digits' => 9],
            'GB' => ['name' => 'Royaume-Uni', 'flag' => '🇬🇧', 'code' => '+44', 'digits' => 10],
            'NL' => ['name' => 'Pays-Bas', 'flag' => '🇳🇱', 'code' => '+31', 'digits' => 9],
            'AT' => ['name' => 'Autriche', 'flag' => '🇦🇹', 'code' => '+43', 'digits' => 10],
            'IE' => ['name' => 'Irlande', 'flag' => '🇮🇪', 'code' => '+353', 'digits' => 9],
            'PL' => ['name' => 'Pologne', 'flag' => '🇵🇱', 'code' => '+48', 'digits' => 9],
            'RO' => ['name' => 'Roumanie', 'flag' => '🇷🇴', 'code' => '+40', 'digits' => 9],
            'MA' => ['name' => 'Maroc', 'flag' => '🇲🇦', 'code' => '+212', 'digits' => 9],
            'DZ' => ['name' => 'Algérie', 'flag' => '🇩🇿', 'code' => '+213', 'digits' => 9],
            'TN' => ['name' => 'Tunisie', 'flag' => '🇹🇳', 'code' => '+216', 'digits' => 8],
            'US' => ['name' => 'États-Unis', 'flag' => '🇺🇸', 'code' => '+1', 'digits' => 10],
            'CA' => ['name' => 'Canada', 'flag' => '🇨🇦', 'code' => '+1', 'digits' => 10],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Search engine optimisation
    |--------------------------------------------------------------------------
    */

    'seo' => [
        // When the host already describes its business in JSON-LD, put its @id
        // here: the package references that entity instead of declaring a
        // competing one on the same page.
        'business_entity_id' => null,

        'title_pattern' => ':page · :business',
    ],

    /*
    |--------------------------------------------------------------------------
    | Business defaults
    |--------------------------------------------------------------------------
    |
    | Seed values, and nothing more. No business code reads this section: it
    | reads the settings accessor, which falls back here only while a setting
    | has never been given a value. Changing a value here after install has no
    | effect on a running instance, which is exactly the intent.
    |
    */

    'defaults' => [

        // Identity of the establishment. Null on purpose: there is no sensible
        // default for an address, and the settings accessor refuses any key it
        // does not find here, so declaring them is what makes them settable.
        'business' => [
            'name' => null,
            'address' => null,
            'postal_code' => null,
            'city' => null,
            'phone' => null,
            'email' => null,

            // Where the establishment is, which decides the hour an instant is
            // shown at. A setting and not a configuration key: an establishment
            // that moves, or a host installing this package for a business in
            // another region, does not have to redeploy to say so. The seed
            // value is the `timezone` key at the head of this file, repeated
            // here because a PHP array cannot quote itself.
            'timezone' => env('BOOKING_TIMEZONE', 'Europe/Paris'),
        ],

        // Ou l'etablissement travaille est une table depuis P44, plus quatre
        // cles : un lieu porte un nom, une adresse, une visibilite, et deux
        // « sur place » differents sont le cas ordinaire. Rien ne lit plus ce
        // bloc, qui est donc parti.

        'messages' => [
            'blocked_client' => null,
            'home_request' => null,
        ],

        'reminders' => [
            // Should stay below the minimum booking notice, otherwise a share
            // of appointments can never be reminded: the reminder time has
            // already passed when they are booked. Warned about, not forbidden.
            'lead_hours' => 24,
        ],

        'marketing' => [
            // Whether the funnel asks for consent to promotional messages.
            // Turning it off removes a way to satisfy the rule, never the rule:
            // no promotional message goes out without dated consent, whatever
            // the channel it was collected through.
            'ask_consent_in_funnel' => true,
        ],

        'sms_budget' => [
            // A ceiling per campaign does not bound a month: ten campaigns
            // under their own cap still add up. Null means no ceiling.
            'monthly_cents' => null,
        ],

        'slot' => [
            'granularity_minutes' => 15,

            // Slots land on round quarters rather than trailing whatever
            // precedes them, which keeps the day readable for both sides.
            'align_to_grid' => true,
        ],

        'buffer' => [
            'default_minutes' => 15,

            // An appointment may end at closing time with its buffer spilling
            // past it. Worth roughly one sellable slot a day.
            'overflows_closing' => true,
        ],

        'booking_window' => [
            'minimum_notice_hours' => 24,
            'horizon_days' => 90,
        ],

        'client_rules' => [
            'cancellation_notice_hours' => 24,

            // Never longer than the cancellation notice: a client who cannot
            // move an appointment cancels it instead, and the slot is lost.
            'reschedule_notice_hours' => 24,
            'max_reschedules' => 1,
        ],

        'payment' => [
            // How long a slot stays held while the deposit is being paid. Too
            // short and clients fail to pay in time, too long and the agenda
            // freezes for nothing.
            'hold_minutes' => 15,
        ],

        'lifecycle' => [
            // A scheduled appointment left untouched this long after it ended
            // is considered honoured, so the takings are right without anyone
            // having to click through the past week.
            'auto_complete_after_hours' => 12,
        ],
    ],
];
