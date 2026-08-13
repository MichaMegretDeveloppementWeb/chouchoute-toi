<?php

declare(strict_types=1);

use Falcon\Analytics\Events\TrackedEvent;

/*
|--------------------------------------------------------------------------
| Tracked events
|--------------------------------------------------------------------------
|
| Single source of truth for the named events of the site. Keep in sync with
| the code using `php artisan analytics:events:scan`.
|
| Value scale: euros of *potential* revenue, based on the real price list
| (config/tarifs.php: a first appointment runs 65 to 95 EUR, average 80).
| A conversion is valued as if it became an appointment, which is optimistic
| in absolute terms but keeps every channel on the same scale, so comparing
| sources and campaigns stays correct. Revisit the figures once Amandine can
| say what share of requests actually turns into an appointment.
|
*/

// -- Conversions ------------------------------------------------------------

// The only conversion the site fully controls: recorded server-side, once the
// mail has actually left, so a failed send is never counted as a request.
TrackedEvent::define(
    'contact.request.submitted',
    'Demande de rendez-vous envoyée',
    value: 80.0,
    conversion: true,
);

// Intent, not a completed call: the click is certain, the call is not.
// Discounted accordingly, but still a conversion.
TrackedEvent::define(
    'contact.phone.click',
    'Numéro de téléphone cliqué',
    value: 60.0,
    conversion: true,
);

TrackedEvent::define(
    'contact.email.click',
    'Adresse email cliquée',
    value: 40.0,
    conversion: true,
);

// -- Intent, on the way to a conversion -------------------------------------

// Carries the chosen volume as a prop: tells which offer actually attracts.
TrackedEvent::define(
    'pricing.cta.click',
    'Réservation depuis un tarif',
    value: 5.0,
    conversion: false,
);

TrackedEvent::define(
    'contact.cta.click',
    'Clic sur un bouton de prise de contact',
    value: 3.0,
    conversion: false,
);

// -- Acquisition channels ---------------------------------------------------

TrackedEvent::define(
    'social.instagram.click',
    'Vers Instagram',
    value: 2.0,
    conversion: false,
);

TrackedEvent::define(
    'social.facebook.click',
    'Vers Facebook',
    value: 1.0,
    conversion: false,
);
