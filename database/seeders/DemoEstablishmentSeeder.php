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
 * Un établissement qui a un siège, et trois endroits où l'on reçoit.
 *
 * `booking:install` en pose un seul, ce qui est vrai d'une installation neuve et
 * ne dit rien des écrans : une liste d'un élément ne se glisse pas, ne se
 * réordonne pas, et son dernier lieu actif ne s'archive pas. Trois lignes
 * suffisent à voir tout cela.
 *
 * **Le siège compte autant que les lieux.** Il commande la reprise d'adresse et
 * le suivi quand il déménage, et ces deux gestes n'ont rien à montrer sans lui.
 *
 * Volontairement absent de {@see DatabaseSeeder} : ce qu'il pose est inventé, et
 * il se lance à la main sur une base locale, comme {@see DemoCatalogueSeeder}.
 *
 * Idempotent sur le nom du lieu, et additif : il ne touche jamais à ce qui est
 * déjà en base, réglage compris.
 */
final class DemoEstablishmentSeeder extends Seeder
{
    /**
     * Le siège, et l'adresse du lieu principal avec lui.
     *
     * Le même endroit pour les deux, comme chez la plupart des indépendants ·
     * c'est l'état où le bouton « Reprendre l'adresse du siège » se retire de
     * lui-même, et le déménager fait paraître le suivi.
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
     * Les deux lieux qui s'ajoutent à celui de l'installation.
     *
     * Ailleurs qu'au siège, et pour de bon · deux adresses distinctes, sans quoi
     * la liste refuserait la seconde, et c'est ce refus qui les rend utiles.
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
     * L'établissement de la démonstration se déplace, et le dit.
     *
     * L'installation archive « À domicile » tant qu'aucune visite ne s'y est
     * tenue, ce qui est juste pour une installation neuve. L'agenda de la
     * démonstration en pose vingt-cinq juste après : le laisser archivé
     * montrerait un écran qui contredit ses propres rendez-vous.
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

            // Le point de départ, qui n'est pas une adresse où l'on reçoit ·
            // celle du client est portée par chaque rendez-vous.
            'address' => self::SEAT['business.address'],
            'postal_code' => self::SEAT['business.postal_code'],
            'city' => self::SEAT['business.city'],
            'radius_km' => 20,
        ]);

        $this->command?->info('Le déplacement chez le client est activé, dans un rayon de 20 km.');
    }

    /**
     * Le siège, posé seulement s'il n'a pas déjà été renseigné.
     *
     * Clé par clé, et non en bloc · une base locale où l'on vient de saisir un
     * numéro de téléphone n'a pas à le perdre parce qu'il manquait une adresse.
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
     * Les lieux, et l'adresse du principal.
     *
     * Celui de l'installation naît sans adresse quand le siège n'était pas
     * encore renseigné, ce qui est l'ordre où ces commandes se lancent. Il la
     * reçoit ici, faute de quoi la démonstration montrerait un endroit où l'on
     * reçoit sans dire où.
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
