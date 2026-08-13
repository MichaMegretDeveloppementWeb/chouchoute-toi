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

    'admin' => [
        'route_prefix' => 'admin/agenda',
        'route_name' => 'booking.admin.',

        // Routes are registered outside the host's own groups, so this stack
        // must include the session middleware. Whatever is listed here is also
        // registered as Livewire persistent middleware, otherwise it would not
        // apply to component updates.
        'middleware' => ['web', 'auth:admin'],

        // A view of the host the package's screens extend. Null makes the
        // package render the full page itself.
        'layout' => 'layouts.booking-admin',

        // The section that layout yields. Hosts name it differently, and a
        // screen rendered into the wrong section shows up blank rather than
        // failing, so it is configurable rather than assumed.
        'layout_section' => 'content',
    ],

    'public' => [
        'route_prefix' => 'reservation',
        'route_name' => 'booking.public.',
        'middleware' => ['web'],
        'layout' => null,
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
        'admin_css_entrypoint' => 'resources/css/ui-kit.css',
    ],

    /*
    |--------------------------------------------------------------------------
    | Capabilities
    |--------------------------------------------------------------------------
    */

    // `multi_practitioner` used to live here and drove nothing: the agenda
    // always picks the first active practitioner. Removed with the package.

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
        ],

        // Where the establishment works. A mode may be offered without being
        // bookable online: the funnel then shows it and hands over to the
        // message below rather than pretending it does not exist.
        'locations' => [
            'salon' => [
                'enabled' => true,
                'bookable_online' => true,
            ],
            'home' => [
                'enabled' => false,
                'bookable_online' => false,
            ],
        ],

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
            // move her appointment cancels it instead, and the slot is lost.
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
            // is considered honoured, so the takings are right without the
            // administrator having to click through her past week.
            'auto_complete_after_hours' => 12,
        ],
    ],
];
