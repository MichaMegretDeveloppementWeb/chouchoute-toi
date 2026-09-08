<?php

declare(strict_types=1);

namespace Database\Seeders;

use Falcon\Booking\Enums\Appointment\AppointmentLocation;
use Falcon\Booking\Enums\Catalogue\Visibility;
use Falcon\Booking\Models\Location;
use Falcon\Booking\Repositories\Settings\SettingsRepository;
use Falcon\Booking\Support\Palette;
use Illuminate\Database\Seeder;

/**
 * An establishment with a registered office and three places where clients are
 * received. `booking:install` lays a single one, which says nothing about the
 * screens: a list of one cannot be dragged, cannot be reordered, and its last
 * active place cannot be archived.
 *
 * The office matters as much as the places: it drives the address carry-over
 * and the follow-up when it moves, and neither has anything to show without it.
 *
 * Deliberately absent from {@see DatabaseSeeder}: what it lays is invented, and
 * it runs by hand on a local database, like {@see DemoCatalogueSeeder}.
 *
 * Idempotent on the place's name, and additive: it never touches what is
 * already in the database, settings included.
 */
final class DemoEstablishmentSeeder extends Seeder
{
    /**
     * The registered office, and the main place's address with it: the same
     * spot for both, as with most sole traders. That is the state where the
     * « Reprendre l'adresse du siège » button withdraws by itself, and moving
     * it makes the follow-up appear.
     *
     * @var array<string, string>
     */
    private const SEAT = [
        'business.address' => '14 rue de la Capelle',
        'business.postal_code' => '12100',
        'business.city' => 'Millau',
        'business.phone' => '05 65 60 12 34',
        'business.email' => 'contact@chouchoute-toi.test',
    ];

    /**
     * The two places added to the one the installer lays. Elsewhere than the
     * office, and genuinely so: two distinct addresses, without which the list
     * would refuse the second.
     *
     * @var list<array<string, string>>
     */
    private const PLACES = [
        [
            'name' => 'Cabine du Larzac',
            'address' => '3 place du Mandarous',
            'postal_code' => '12100',
            'city' => 'Millau',
            'visibility' => Visibility::Bookable->value,
            'practical_info' => "Deuxième étage, sonnez à l’interphone « Institut ».\nParking gratuit place du Mandarous.",
            'color' => Palette::FAMILIES['sage'][2],
        ],
        [
            'name' => 'Annexe de Creissels',
            'address' => '27 avenue de Saint-Martin',
            'postal_code' => '12100',
            'city' => 'Creissels',
            'visibility' => Visibility::Shown->value,
            'practical_info' => 'Entrée par la cour, à gauche du portail vert.',
            'color' => Palette::FAMILIES['amber'][1],
        ],
    ];

    public function run(): void
    {
        $this->seat();
        $this->places();
        $this->homeVisits();
    }

    /**
     * The demonstration's establishment travels, and says so. The installer
     * archives « À domicile » while no visit has been held there, which is
     * right for a fresh install; the demonstration's agenda lays twenty-five
     * right after, and leaving it archived would show a screen contradicting
     * its own appointments.
     */
    private function homeVisits(): void
    {
        $home = Location::query()->where('kind', AppointmentLocation::Home->value)->first();

        if ($home === null || ! $home->isArchived()) {
            return;
        }

        $home->update([
            'archived_at' => null,
            'visibility' => Visibility::Bookable->value,

            // The starting point, which is not an address where clients are
            // received: theirs is carried by each appointment.
            'address' => self::SEAT['business.address'],
            'postal_code' => self::SEAT['business.postal_code'],
            'city' => self::SEAT['business.city'],
            'radius_km' => 20,
        ]);

        $this->command?->info('Le déplacement chez le client est activé, dans un rayon de 20 km.');
    }

    /**
     * The registered office, laid only where nothing was filled in. Key by key
     * and not in one block: a local database where a phone number was just
     * typed must not lose it because an address was missing.
     */
    private function seat(): void
    {
        $settings = app(SettingsRepository::class);
        $written = 0;

        foreach (self::SEAT as $key => $value) {
            if ($settings->nullableString($key) !== null) {
                continue;
            }

            $settings->set($key, $value);
            $written++;
        }

        $this->command?->info($written === 0
            ? 'Le siège était déjà renseigné, il est laissé tel quel.'
            : "{$written} coordonnée(s) du siège renseignée(s).");
    }

    /**
     * The places, and the main one's address. The installer's is born without
     * one when the office was not yet filled in, which is the order these
     * commands run in; it receives it here, failing which the demonstration
     * would show a place without saying where.
     */
    private function places(): void
    {
        $main = Location::query()->onSite()->orderBy('position')->orderBy('id')->first();

        if ($main !== null && $main->address === null) {
            $main->update([
                'address' => self::SEAT['business.address'],
                'postal_code' => self::SEAT['business.postal_code'],
                'city' => self::SEAT['business.city'],
                'practical_info' => 'Rez-de-chaussée, la porte bleue après la boulangerie.',
            ]);
        }

        $position = (int) Location::query()->max('position');
        $created = 0;

        foreach (self::PLACES as $place) {
            if (Location::query()->where('name', $place['name'])->exists()) {
                continue;
            }

            Location::query()->create($place + [
                'kind' => AppointmentLocation::OnSite->value,
                'position' => ++$position,
            ]);

            $created++;
        }

        $this->command?->info("{$created} lieu(x) supplémentaire(s) créé(s).");
    }
}
