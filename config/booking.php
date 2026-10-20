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

    'admin' => [
        'route_prefix' => 'admin/agenda',

        // Routes are registered outside the host's own groups, so this stack
        // must include the session middleware. Whatever is listed here is also
        // registered as Livewire persistent middleware, otherwise it would not
        // apply to component updates.
        'middleware' => ['web', 'auth:admin'],

        // The guard the screens run behind, used to name the actor of a change
        // in the audit journal. Null falls back to the application's default
        // guard, which is rarely the right one for a back office.
        'guard' => 'admin',

        // Extra middleware for one branch of the ability tree, keyed by ability
        // name (e.g. ['booking.settings' => ['password.confirm']]). Every
        // screen of that branch takes it after the door and its ability, and
        // every click on those screens replays it. Empty, no screen takes more
        // than the door. Who may open or do what is not set here: see
        // docs/autorisation.md.
        'middleware_for' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | The shell around each area
    |--------------------------------------------------------------------------
    |
    | One key per area, named after it. The value is a Blade COMPONENT — a
    | screen opens it as a tag and fills its slot, so name it the way you would
    | write it: 'layout.admin' for <x-layout.admin>. Null uses the package's own
    | shell.
    |
    | The back office's is a stopgap and nothing more: a host that has one names
    | it here. The public page's is not — an establishment with no website has
    | only that page, and the package draws it whole.
    |
    | There is no section to name any more. A component layout has a slot, and a
    | slot cannot be missed the way a section name could.
    |
    */
    'layouts' => [
        // The back office takes the site's own shell, with its sidebar and its
        // account. The public pages take the site's too · its header, its
        // footer, its face and its colours, set in `css/web/booking.css`.
        'admin' => 'layout.admin',
        'public' => 'layout.booking',
    ],

    /*
    | The public page, the one clients see. The package routes it itself, since
    | an establishment with no website has only this page, and everything below
    | lets a host take over one level without touching another.
    */
    'public' => [
        // Where the page answers. Changed once, at installation: an address is
        // what one changes least willingly, every shared link and bookmark
        // dying at the first rename.
        'route_prefix' => 'reservation',

        // No `auth` here, unlike the back office: this page is the one place
        // the package speaks to someone who has no account.
        'middleware' => ['web'],

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
    | There is nothing to set for the assets, and that is the point.
    |
    | The package compiles its sheet and its script, ships them already
    | compiled, and `vendor:publish` lays a copy in your public directory. You
    | import nothing, scan none of the package's views, and install no build
    | dependency.
    |
    | The one thing to do, at every deployment:
    |
    |     php artisan vendor:publish --tag=laravel-assets --force
    |
    | Without it the screens raise, naming the command, rather than silently
    | serving a stale sheet.
    |
    | Where the copy lands is set at the kit, `ui.assets.path`, for the whole
    | suite at once.
    */

    'seo' => [
        // When the host already describes its business in JSON-LD, put its @id
        // here: the package then emits no business node of its own, its
        // `Service` nodes point at that entity instead, and a place at the
        // seat's address is that entity rather than a second one. Two
        // competing establishments on one page make the result worse, not
        // richer. A value leading with `#` is read on the application's root,
        // `url('/')`. Only useful when the funnel renders into the host's own
        // layout, which then carries the host's description.
        'business_entity_id' => '#business',

        // The same for the host's `WebSite`: the package then declares none,
        // and its page is part of the host's site.
        'website_entity_id' => '#website',

        // The page title, for every public page. The entry's page name is
        // « Réserver en ligne ».
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

        // What a phone photograph weighs, the server making its sizes itself.
        'max_size_kb' => 8192,
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
    ],

    /*
    | Telephone numbers, stored in E.164.
    |
    | A list rather than a library: validating a number properly is a job for
    | metadata nobody maintains by hand, and this package does not pretend to
    | do it. What it does is refuse a number that cannot be one.
    |
    | `min` and `max` bound the national number, dialling code excluded, a
    | trunk zero dropped before counting unless `keeps_zero` says it is part
    | of the number. Canada shares +1 and lives under `US`. Add a country by
    | adding a line.
    | The names are shown in the field, which is why they are in French.
    */
    'phone' => [
        'default_country' => env('BOOKING_PHONE_COUNTRY', 'FR'),

        'countries' => [
            'FR' => ['name' => 'France', 'flag' => '🇫🇷', 'code' => '+33', 'min' => 9, 'max' => 9],
            'BE' => ['name' => 'Belgique', 'flag' => '🇧🇪', 'code' => '+32', 'min' => 8, 'max' => 9],
            'CH' => ['name' => 'Suisse', 'flag' => '🇨🇭', 'code' => '+41', 'min' => 9, 'max' => 9],
            'LU' => ['name' => 'Luxembourg', 'flag' => '🇱🇺', 'code' => '+352', 'min' => 6, 'max' => 9],
            'MC' => ['name' => 'Monaco', 'flag' => '🇲🇨', 'code' => '+377', 'min' => 8, 'max' => 9],
            'DE' => ['name' => 'Allemagne', 'flag' => '🇩🇪', 'code' => '+49', 'min' => 6, 'max' => 11],
            'IT' => ['name' => 'Italie', 'flag' => '🇮🇹', 'code' => '+39', 'min' => 6, 'max' => 11, 'keeps_zero' => true],
            'ES' => ['name' => 'Espagne', 'flag' => '🇪🇸', 'code' => '+34', 'min' => 9, 'max' => 9],
            'PT' => ['name' => 'Portugal', 'flag' => '🇵🇹', 'code' => '+351', 'min' => 9, 'max' => 9],
            'GB' => ['name' => 'Royaume-Uni', 'flag' => '🇬🇧', 'code' => '+44', 'min' => 9, 'max' => 10],
            'NL' => ['name' => 'Pays-Bas', 'flag' => '🇳🇱', 'code' => '+31', 'min' => 9, 'max' => 9],
            'AT' => ['name' => 'Autriche', 'flag' => '🇦🇹', 'code' => '+43', 'min' => 7, 'max' => 13],
            'IE' => ['name' => 'Irlande', 'flag' => '🇮🇪', 'code' => '+353', 'min' => 7, 'max' => 9],
            'PL' => ['name' => 'Pologne', 'flag' => '🇵🇱', 'code' => '+48', 'min' => 9, 'max' => 9],
            'RO' => ['name' => 'Roumanie', 'flag' => '🇷🇴', 'code' => '+40', 'min' => 9, 'max' => 9],
            'MA' => ['name' => 'Maroc', 'flag' => '🇲🇦', 'code' => '+212', 'min' => 9, 'max' => 9],
            'DZ' => ['name' => 'Algérie', 'flag' => '🇩🇿', 'code' => '+213', 'min' => 8, 'max' => 9],
            'TN' => ['name' => 'Tunisie', 'flag' => '🇹🇳', 'code' => '+216', 'min' => 8, 'max' => 8],
            'US' => ['name' => 'États-Unis, Canada', 'flag' => '🇺🇸', 'code' => '+1', 'min' => 10, 'max' => 10],
        ],
    ],

    /*
    | Whether the radius of the home visits is checked, as the crow flies.
    |
    | True: the starting point is located when the home visits screen is saved,
    | and each address is located against it · out of the radius, a booking
    | online is refused and the counter is warned. The address goes to the
    | public geocoding service of the IGN, which the privacy page says, and the
    | server needs an outgoing HTTPS connection to data.geopf.fr.
    |
    | False: the radius is only said, as before, and no address leaves.
    */
    'home_visits' => [
        'check_radius' => env('BOOKING_CHECK_TRAVEL_RADIUS', true),
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
    | `booking:send-messages` on its own terms. The commands stay registered
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
        // The site is hosted by Hostinger, the same for every environment.
        // The phone is the one Hostinger declares to the RIPE registry: its
        // own legal pages publish none.
        'host' => [
            'name' => 'Hostinger International Ltd',
            'address' => '61 Lordou Vironos Street, 6023 Larnaca, Chypre',
            'phone' => '+370 645 03378',
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

        /*
        | Who the establishment warns, and about what. The message to the client
        | is not here, but for the one switch below: once a visit exists, what
        | happens to it is always told, because the client knows every visit of
        | theirs and the message carries the link to move or cancel.
        |
        | `recipients` is a list of addresses and not of accounts, which is what
        | makes it writable before accounts exist: the day practitioners and
        | administrators have access, the list derives from their roles and the
        | switches do not move.
        */
        'notifications' => [
            // Whether the form of an appointment entered by hand opens with its
            // confirmation ticked. The box decides, visit by visit, when it is
            // saved; it commands only the birth of a counter booking. What happens
            // to it afterwards follows a rule and not a setting: a move, a
            // cancellation or a change of what the client knows is always told.
            // False here: the establishment confirms by phone, and ticks the box
            // when the client wants the message.
            'confirm_counter_bookings' => false,

            'recipients' => [],

            // The practitioner taking the appointment, at the address on their
            // record. Without an address they are simply not warned.
            'notify_practitioner' => true,

            'on_booked' => true,
            'on_moved' => true,
            'on_cancelled' => true,
        ],

        'reminders' => [
            // Whether an appointment gets a reminder, read when it is
            // registered: the choice its form opens on, and the value of one
            // booked online. Each appointment then keeps its own
            // `reminder_enabled`, whatever this becomes afterwards.
            'enabled' => true,

            // Should stay below the minimum booking notice, otherwise a share
            // of appointments can never be reminded: the reminder time has
            // already passed when they are booked. Warned about, not forbidden.
            'lead_hours' => 24,
        ],

        'slot' => [
            'granularity_minutes' => 15,

            // Slots land on the step, counted from the opening of their
            // window, rather than trailing whatever precedes them.
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

        'lifecycle' => [

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
