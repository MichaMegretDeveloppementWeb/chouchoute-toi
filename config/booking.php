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
    | interface: see the "settings" section at the bottom for their seed values.
    |
    */

    // Seed value of the establishment's timezone. Once installed it is a
    // setting, answered from the details screen, and this key is never read
    // again.
    'timezone' => env('BOOKING_TIMEZONE', 'Europe/Paris'),

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

        // The section that layout yields. A screen rendered into the wrong
        // section shows up blank rather than failing, so it is configurable
        // rather than assumed.
        'layout_section' => 'content',
    ],

    /*
    | The public page, the one clients see. The package routes it itself, since
    | an establishment with no website has only this page, and everything below
    | lets a host take over one level without touching another.
    */
    'public' => [
        // Where the page answers, and how its routes are named. Changed once,
        // at installation: an address is what one changes least willingly,
        // every shared link and bookmark dying at the first rename.
        'route_prefix' => 'reservation',
        'route_name' => 'booking.public.',

        // No `auth` here, unlike the back office: this page is the one place
        // the package speaks to someone who has no account.
        'middleware' => ['web'],

        // A view of the host the page extends, and the section it yields. Null
        // makes the package render the full page through a shell of its own,
        // nothing guaranteeing a host has a layout at all.
        'layout' => null,
        'layout_section' => 'content',

        // False stops the package registering its public routes at all, for a
        // host that would rather mount the views under routes of its own. The
        // page then answers nowhere until it does.
        'routes' => true,

        // How a client is recognised on their own space. A configuration key
        // and not a setting: recognising someone is a design decision, not a
        // button in an administration screen. `magic_link` sends an address by
        // e-mail that exchanges for a session, so there is nothing to create
        // and no password to store. Another strategy joins as a second
        // implementation of `Contracts\Public\IdentifiesTheClient`, and only
        // the chosen one is mounted.
        'identification' => 'magic_link',

        // How many days a link stays open. It opens a whole history and not
        // one visit, so it expires; every message stamps a fresh one, and a
        // client whose link died goes through "retrouver mes rendez-vous".
        'link_lifetime_days' => 30,
    ],

    /*
    | Your entry points, where `booking:install` wrote its imports.
    |
    | The package compiles no CSS. Its back office styles are declared in
    | `resources/css/booking-admin.css`, which the host imports into its own
    | entry and compiles with it, FullCalendar's sheet included. The JavaScript
    | ships compiled and is imported the same way.
    |
    | These files are yours, and your `npm run build` reads them.
    | `booking:check` opens them to verify the imports are still there. Change a
    | path here when you move or rename one of them; paths are relative to the
    | project root.
    |
    | `web_css` carries two roles: the shopfront import is written there, and
    | the public layout loads that sheet through `@vite`.
    */
    'assets' => [
        'admin_css' => 'resources/css/admin.css',
        'admin_js' => 'resources/js/admin.js',
        'web_css' => 'resources/css/web.css',
    ],

    'seo' => [
        // When the host already describes its business in JSON-LD, put its @id
        // here: the package then emits no business node of its own, and its
        // `Service` nodes point at that entity instead. Two competing
        // establishments on one page make the result worse, not richer. Only
        // useful when the funnel renders into the host's own layout.
        'business_entity_id' => null,

        // The page title, for every public page but the entry. The entry
        // carries no page name: it is the establishment, and the pattern is
        // skipped there rather than trimmed.
        'title_pattern' => ':page · :business',
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage
    |--------------------------------------------------------------------------
    |
    | Two disks, and they are not interchangeable. One is public because a logo
    | only means something displayed; the other is private because it holds
    | photographs of people.
    |
    */

    /*
    | The establishment's own media: logo, cover, gallery.
    |
    | The package declares this disk itself, from its ServiceProvider, so the
    | host never has to touch `config/filesystems.php`. Its only chore is one
    | `php artisan storage:link`. Naming another disk here sends everything
    | elsewhere, S3 for instance; the package declares its own only when the
    | name is free, never over an existing one.
    */
    'media' => [
        'disk' => env('BOOKING_MEDIA_DISK', 'booking_media'),

        // A logo fits well within this. What does not is a photograph that
        // should have been resized before being sent.
        'max_size_kb' => 2048,

        // How many photographs the gallery holds at most. High enough never to
        // be met in normal use, low enough that a public page does not become
        // an endless album.
        'max_photos' => 20,

        // What the file picker filters.
        'extensions' => ['png', 'jpg', 'jpeg', 'webp'],

        // And what the file must actually be, guessed from the content and
        // never read in the name. No SVG: it is a document that can carry
        // script, served from the host's own domain, and sanitising it would
        // mean a fifth dependency.
        'mimes' => [
            'image/png',
            'image/jpeg',
            'image/webp',
        ],

        /*
        | What the browser does to an image before sending it: resized within
        | these bounds, encoded as WebP, and brought under the target weight by
        | lowering quality first and dimensions if it must. The bounds are the
        | real display size, not round numbers.
        |
        | `ratio` is read only where the page imposes a frame, and that is where
        | the cropper opens. It must be the ratio the CSS applies, otherwise the
        | page would crop over what was just framed. Null keeps the proportions.
        |
        | `target_kb` is a goal, not a server ceiling: the server receives a
        | file without knowing where it came from and holds `max_size_kb`.
        */
        'images' => [
            // The band spans the screen, and `.fb-public-cover` carries the
            // same 3:1.
            'cover' => [
                'max_width' => 1920,
                'max_height' => 640,
                'ratio' => 3,
                'target_kb' => 200,
            ],

            // Displayed 72px tall, but it also serves e-mails and documents.
            'logo' => [
                'max_width' => 512,
                'max_height' => 512,
                'ratio' => 1,
                'target_kb' => 200,
            ],

            // Thumbnails of 130 to 200px, doubled for dense screens.
            'gallery' => [
                'max_width' => 800,
                'max_height' => 800,
                'ratio' => null,
                'target_kb' => 200,
            ],
        ],
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

        // And what the file must actually be, guessed from the content.
        'mimes' => [
            'image/jpeg',
            'image/png',
            'image/webp',
            'image/heic',
            'image/heif',
            'application/pdf',
        ],
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
    | picked up once, at install time, when that table is still empty.
    |
    */

    /*
    | Telephone numbers, stored in E.164.
    |
    | A list rather than a library: validating a number properly is a job for
    | metadata nobody maintains by hand, and this package does not pretend to
    | do it. What it does is refuse a number that cannot be one.
    |
    | `digits` is the national number, dialling code excluded, and a national
    | trunk zero is dropped before counting. Add a country by adding a line.
    | The names are shown in the field, which is why they are in French.
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
    | Operations
    |--------------------------------------------------------------------------
    |
    | What belongs to the deployment rather than to the establishment: where
    | errors go, whether the scheduled tasks are hooked, and who hosts the site.
    |
    */

    /*
    | Where the package writes its errors.
    |
    | Null by default: the package writes where the application already writes,
    | so a host that centralises to an external service is not fragmented
    | without having asked for it.
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
    | The package hooks its own tasks onto Laravel's scheduler, so a host has
    | one cron line to write and nothing else. `schedule:list` then shows what
    | runs and when.
    |
    |     * * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
    |
    | False unhooks them, for a host that would rather call `booking:remind` and
    | `booking:complete-past` on its own terms. The commands stay registered
    | either way.
    */
    'scheduler' => [
        'enabled' => true,
    ],

    /*
    | The legal notice, the part that belongs to the deployment. Everything else
    | on that page is a setting; the web host is not, being a property of the
    | server, and moving from one host to another is settled in the repository.
    |
    | The mention is mandatory as soon as a site is open to the public, and
    | `booking:check` claims it when it is missing. The name alone makes the
    | block appear; address and phone complete what the law asks.
    */
    'legal' => [
        'host' => [
            'name' => env('BOOKING_LEGAL_HOST_NAME'),
            'address' => env('BOOKING_LEGAL_HOST_ADDRESS'),
            'phone' => env('BOOKING_LEGAL_HOST_PHONE'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Business settings
    |--------------------------------------------------------------------------
    |
    | Seed values, and nothing more. No business code reads this section: it
    | reads the settings accessor, which falls back here only while a setting
    | has never been given a value. Changing a value here after install has no
    | effect on a running instance, which is exactly the intent.
    |
    | It doubles as the list of settable keys: the accessor refuses any key it
    | does not find here, which is what stops a typo creating a phantom setting.
    |
    */

    'settings' => [

        'business' => [
            'name' => null,

            /*
            | The registered office, and not a place where clients are received.
            | The address of an appointment always belongs to its location, so
            | these three keys serve the legal notice; a location form offers to
            | copy them over, a copy and never a link.
            |
            | For a sole trader this is often their home or a mail forwarding
            | address, which is why the public page does not show it.
            */
            'address' => null,
            'postal_code' => null,
            'city' => null,

            'phone' => null,
            'email' => null,

            // What the establishment is, to a search engine. `LocalBusiness`
            // is the general answer and is never wrong; the details screen
            // offers the others, taken from `Enums\Seo\BusinessType`. A beauty
            // salon, a dental practice and a garage all take appointments, and
            // the package cannot guess which one it serves.
            //
            // Chouchoute toi is a beauty salon, and says so from the start: a
            // rebuilt installation seeds this rather than the generic answer.
            'seo_type' => 'BeautySalon',

            // What the establishment says about itself, at the top of its page.
            // Plain text: line breaks become paragraphs and nothing else is
            // interpreted. No extra dependency, no injection surface, and a
            // page that cannot be broken.
            'description' => null,

            /*
            | What clients must know before coming, which is not what the
            | description says: an obligation, a house rule, a warning about
            | prices. It is contractual, hence its places, the funnel's modal,
            | the confirmation and the reminder. A thousand characters at most,
            | because it appears at the moment someone wants to finish.
            |
            | The switch commands all of it, page, funnel and e-mails, so a
            | seasonal notice can stay written while it stops being shown.
            */
            'visit_notice' => null,
            'visit_notice_shown' => false,

            // Where the establishment is, which decides the hour an instant is
            // shown at. A setting and not a configuration key: an establishment
            // that moves, or a host installing this package for a business in
            // another region, does not have to redeploy to say so. The seed
            // value is the `timezone` key at the head of this file, repeated
            // here because a PHP array cannot quote itself.
            'timezone' => env('BOOKING_TIMEZONE', 'Europe/Paris'),

            // The face of the establishment on its public page. Null is a
            // normal case: the page then carries its own neutral drawing rather
            // than a demonstration logo. The path is relative to the
            // `media.disk` disk and never a URL, so the disk can change without
            // the stored value moving.
            'logo_path' => null,

            // The banner image. A setting like the logo because there is only
            // one; the gallery has a table of its own, since it carries an
            // order.
            'cover_path' => null,

            /*
            | The legal notice. All null, and null is a normal case: a missing
            | line does not appear on the page, rather than a "SIRET" label
            | above nothing, which would say something false.
            |
            | `legal_name` and `legal_publisher` fall back on the name when
            | empty. That fallback lives in `LegalNoticeData` and not here: a
            | default frozen at install would not follow a change of name.
            |
            | The registered office is `business.address` above, and the web
            | host is under `legal.host`. Neither belongs here.
            */
            'legal_name' => null,
            'legal_form' => null,
            'legal_siret' => null,
            'legal_registry' => null,
            'legal_capital' => null,
            'legal_vat' => null,
            'legal_publisher' => null,
            'legal_insurer' => null,
            'legal_insurance_area' => null,
            'legal_mediator' => null,
            'legal_mediator_url' => null,
        ],

        'messages' => [
            'blocked_client' => null,
            'home_request' => null,
        ],

        /*
        | Who the establishment warns, and about what. The message to the client
        | is not here: it always goes out, because it carries the link to move
        | or cancel, and cutting it would leave the client no way to act.
        |
        | `recipients` is a list of addresses and not of accounts, which is what
        | makes it writable before accounts exist: the day practitioners and
        | administrators have access, the list derives from their roles and the
        | switches do not move.
        */
        'notifications' => [
            // Whether an appointment entered by hand sends a confirmation. It
            // commands only the birth of a counter booking; what happens to it
            // afterwards follows a rule and not a setting, since the counter
            // only speaks of a visit the client has already heard about, which
            // `appointments.client_told_at` records.
            'confirm_counter_bookings' => true,

            'recipients' => [],

            // The practitioner taking the appointment, at the address on their
            // record. Without an address they are simply not warned.
            'notify_practitioner' => true,

            'on_booked' => true,
            'on_moved' => true,
            'on_cancelled' => true,
        ],

        'reminders' => [
            // Whether a reminder goes out at all: the switch the establishment
            // holds, and what an appointment's own `reminder_enabled` follows
            // when it is null, which is its default. An appointment may
            // contradict it either way.
            'enabled' => true,

            // Should stay below the minimum booking notice, otherwise a share
            // of appointments can never be reminded: the reminder time has
            // already passed when they are booked. Warned about, not forbidden.
            'lead_hours' => 24,
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

            // When a scheduled task of the package last finished. Operational
            // and not a business setting, the only key here that is not one:
            // it is declared so the repository accepts it, and left out of
            // `BookingSettingsSeeder::MANDATORY` because seeding it would claim
            // the scheduler had already run. It exists because `booking:check`
            // has to answer "is the cron running".
            'scheduler_seen_at' => null,
        ],
    ],
];
