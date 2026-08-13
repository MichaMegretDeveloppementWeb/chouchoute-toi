<?php

declare(strict_types=1);

use Falcon\Analytics\Funnels\Funnel;
use Falcon\Analytics\Funnels\FunnelBranch;

/*
|--------------------------------------------------------------------------
| Funnels
|--------------------------------------------------------------------------
|
| Progression is sequential: a visitor only reaches step N after step N-1.
| Alternatives that sit at the same depth are declared with anyOf, otherwise
| they would read as "went through one THEN the other" and report zeros.
|
| Phone and email are deliberately absent from the funnels: they short-circuit
| the path (one can call from the header without ever opening the contact
| page), so counting them as a funnel step would report false losses. They
| remain conversions in their own right on the events screen.
|
*/

/*
 | The path the site actually controls, end to end. Answers: of the visitors
 | who look at the offer, how many open the form, and how many send it?
 */
Funnel::define('demande_rdv', 'Demande de rendez-vous')
    ->step('Offre consultée', value: 2, anyOf: [
        FunnelBranch::route('Accueil', 'home'),
        FunnelBranch::route('Prestations', 'prestations'),
    ])
    ->step('Formulaire ouvert', value: 8, route: 'contact')
    ->step('Demande envoyée', value: 80, event: 'contact.request.submitted');

/*
 | Does the price list convert, or does it scare people off? Isolated on
 | purpose: pricing is the page most likely to lose a visitor, and mixing it
 | into the funnel above would hide that.
 */
Funnel::define('parcours_tarifs', 'Parcours tarifaire')
    ->step('Tarifs consultés', value: 1, route: 'prestations')
    ->step('Réservation cliquée', value: 5, event: 'pricing.cta.click')
    ->step('Demande envoyée', value: 80, event: 'contact.request.submitted');
