<?php

declare(strict_types=1);

namespace Database\Seeders;

use Carbon\CarbonImmutable;
use Falcon\Booking\Actions\Admin\Appointment\BookAppointmentAction;
use Falcon\Booking\Actions\Admin\Appointment\RepeatAppointmentAction;
use Falcon\Booking\Actions\Admin\Appointment\TransitionAppointmentAction;
use Falcon\Booking\Actions\Admin\Schedule\SaveScheduleExceptionAction;
use Falcon\Booking\Actions\Admin\Schedule\UpdateWeeklyScheduleAction;
use Falcon\Booking\Data\Appointment\BookAppointmentData;
use Falcon\Booking\Data\Appointment\BookedTreatmentData;
use Falcon\Booking\Data\Appointment\RecurrenceData;
use Falcon\Booking\Data\Schedule\OpeningHourData;
use Falcon\Booking\Data\Schedule\ScheduleExceptionData;
use Falcon\Booking\Enums\Appointment\AppointmentActor;
use Falcon\Booking\Enums\Appointment\AppointmentLocation;
use Falcon\Booking\Enums\Appointment\AppointmentStatus;
use Falcon\Booking\Enums\Appointment\RecurrenceFrequency;
use Falcon\Booking\Enums\Schedule\ScheduleExceptionType;
use Falcon\Booking\Models\Appointment;
use Falcon\Booking\Models\Client;
use Falcon\Booking\Models\Practitioner;
use Falcon\Booking\Models\ScheduleException;
use Falcon\Booking\Models\Service;
use Illuminate\Database\Seeder;

/**
 * An agenda with enough in it to judge the screens by.
 *
 * Opening hours, some fifty clients and three months of appointments around
 * today, in every shape the package can hold: visits made of several treatments
 * across two agendas, visits at the client's, deliberate overlaps, weekly
 * series, and the states a past appointment ends in.
 *
 * **Written through the Actions, never through the factories.** A visit written
 * the real way carries its treatment lines, and the agenda reads their count to
 * put « +2 » on a card: rows built by hand would draw cards that lie. The
 * factories are also dev-only autoload, and the `Admin\` space refuses nothing —
 * neither closed hours nor an overlap — so nothing can be turned away mid-seed.
 *
 * Anchored on today so the demo never goes stale, and deterministic: everything
 * is derived from a rank, so two runs place the same visits and no two clients
 * fight over a telephone number.
 *
 * Run by hand, like {@see AdminSeeder}, and never from {@see DatabaseSeeder}.
 */
final class DemoAgendaSeeder extends Seeder
{
    /** Six weeks either side of today. */
    private const WEEKS_BACK = 6;

    private const WEEKS_AHEAD = 6;

    /** Tuesday to Saturday, which is how most of them open. */
    private const OPEN_WEEKDAYS = [2, 3, 4, 5, 6];

    /** Two ranges a day: the midday break is a shape the screens have to hold. */
    private const MORNING = ['09:00', '12:30'];

    private const AFTERNOON = ['14:00', '19:00'];

    /** @var list<array{0: string, 1: string}> */
    private const NAMES = [
        ['Camille', 'Berthier'], ['Léa', 'Nguyen'], ['Sofia', 'Marchetti'], ['Inès', 'Fauvel'],
        ['Manon', 'Delaunay'], ['Chloé', 'Ravel'], ['Jade', 'Bonnet'], ['Louise', 'Amrani'],
        ['Emma', 'Vasseur'], ['Alice', 'Kowalski'], ['Sarah', 'Lemoine'], ['Zoé', 'Perrin'],
        ['Nina', 'Da Silva'], ['Clara', 'Béguin'], ['Anna', 'Rousseau'], ['Julie', 'Mercier'],
        ['Marine', 'Ferrand'], ['Élodie', 'Charpentier'], ['Noémie', 'Aubert'], ['Lucie', 'Granger'],
        ['Thomas', 'Meunier'], ['Hugo', 'Lefebvre'], ['Nathan', 'Barbier'], ['Karim', 'Benali'],
        ['Antoine', 'Dupuis'], ['Maxime', 'Roussel'], ['Julien', 'Chevalier'], ['Paul', 'Guerin'],
        ['Yanis', 'Moreau'], ['Adrien', 'Lacroix'],
    ];

    /** @var list<array{0: string, 1: string, 2: string}> */
    private const ADDRESSES = [
        ['12 rue des Tattes', '74500', 'Publier'],
        ['3 chemin du Lac', '74200', 'Thonon-les-Bains'],
        ['48 avenue de Genève', '74100', 'Annemasse'],
        ['7 place du Marché', '74300', 'Cluses'],
    ];

    public function run(): void
    {
        $practitioners = Practitioner::query()->orderBy('id')->get();
        $services = Service::query()->whereNull('archived_at')->orderBy('id')->get();

        if ($practitioners->isEmpty() || $services->isEmpty()) {
            $this->command?->warn(
                'Ni praticien ni prestation en base : lancez db:seed puis DemoCatalogueSeeder avant celui-ci.'
            );

            return;
        }

        $this->openingHours($practitioners->all());
        $clients = $this->clients();
        $this->unavailabilities($practitioners->first());

        if (Appointment::query()->exists()) {
            $this->command?->info('Des rendez-vous existent déjà : l’agenda est laissé tel quel.');

            return;
        }

        $this->appointments($practitioners->all(), $clients, $services->all());
        $this->series($practitioners->first(), $clients[0], $services->first());
    }

    /**
     * The week every practitioner works, replaced whole.
     *
     * `UpdateWeeklyScheduleAction` swaps the week rather than pairing rows, so
     * running this twice leaves exactly one week behind.
     *
     * @param  list<Practitioner>  $practitioners
     */
    private function openingHours(array $practitioners): void
    {
        $week = [];

        foreach (self::OPEN_WEEKDAYS as $weekday) {
            $week[] = new OpeningHourData($weekday, self::MORNING[0], self::MORNING[1]);
            $week[] = new OpeningHourData($weekday, self::AFTERNOON[0], self::AFTERNOON[1]);
        }

        foreach ($practitioners as $practitioner) {
            app(UpdateWeeklyScheduleAction::class)->execute($practitioner, $week);
        }

        $this->command?->info(count($practitioners).' semaine(s) d’ouverture posée(s), deux plages par jour.');
    }

    /**
     * Some fifty client records, keyed on a telephone built from their rank.
     *
     * Built and not drawn: the column is unique, and two random numbers that
     * collide would take the whole seed down halfway through.
     *
     * @return list<Client>
     */
    private function clients(): array
    {
        $clients = [];
        $created = 0;

        foreach (self::NAMES as $rank => [$first, $last]) {
            // A second pass over the same names, so fifty records out of thirty
            // pairs without inventing a family that shares a number.
            foreach ([0, 1] as $round) {
                $index = $rank * 2 + $round;

                if ($index >= 50) {
                    break 2;
                }

                // One in four carries an address, without which a visit at the
                // client's has nowhere to go and the seed would place almost
                // none.
                $address = $index % 4 === 0 ? self::ADDRESSES[$index % count(self::ADDRESSES)] : null;

                $client = Client::query()->firstOrCreate(
                    ['phone' => sprintf('+3360%07d', 1_000_000 + $index)],
                    [
                        'first_name' => $first,
                        'last_name' => $round === 0 ? $last : $last.'-'.chr(65 + $round),
                        'email' => sprintf('%s.%s%d@exemple.test', mb_strtolower($first), mb_strtolower(preg_replace('/[^a-z]/i', '', $last) ?? 'x'), $index),
                        'address' => $address[0] ?? null,
                        'postal_code' => $address[1] ?? null,
                        'city' => $address[2] ?? null,

                        // One in twenty-five, so the screens that must warn have
                        // something to warn about.
                        'is_blocked' => $index % 25 === 24,
                        'blocked_at' => $index % 25 === 24 ? CarbonImmutable::now() : null,
                        'source' => 'admin',
                    ],
                );

                $created += $client->wasRecentlyCreated ? 1 : 0;
                $clients[] = $client;
            }
        }

        $this->command?->info("{$created} client(s) créé(s), le reste était déjà en base.");

        return $clients;
    }

    /**
     * A closed day, a closed afternoon, and a day opening later than usual.
     *
     * Keyed on the day and the type: replayed, it recognises its own.
     */
    private function unavailabilities(Practitioner $practitioner): void
    {
        $monday = CarbonImmutable::now()->startOfWeek()->addWeek();

        $periods = [
            [ScheduleExceptionType::Closure, $monday->addDays(2)->setTime(0, 0), $monday->addDays(3)->setTime(0, 0), 'Congé'],
            [ScheduleExceptionType::Closure, $monday->addDays(9)->setTime(14, 0), $monday->addDays(9)->setTime(19, 0), 'Formation'],
            [ScheduleExceptionType::SpecialHours, $monday->addDays(16)->setTime(11, 0), $monday->addDays(16)->setTime(19, 0), 'Ouverture tardive'],
        ];

        $created = 0;

        foreach ($periods as [$type, $startsAt, $endsAt, $reason]) {
            $exists = ScheduleException::query()
                ->where('practitioner_id', $practitioner->id)
                ->where('starts_at', $startsAt->utc())
                ->exists();

            if ($exists) {
                continue;
            }

            app(SaveScheduleExceptionAction::class)->execute(
                $practitioner,
                new ScheduleExceptionData($type, $startsAt, $endsAt, $reason),
            );

            $created++;
        }

        $this->command?->info("{$created} indisponibilité(s) créée(s).");
    }

    /**
     * Three months of visits, four to six a day on the days that open.
     *
     * @param  list<Practitioner>  $practitioners
     * @param  list<Client>  $clients
     * @param  list<Service>  $services
     */
    private function appointments(array $practitioners, array $clients, array $services): void
    {
        $today = CarbonImmutable::now()->startOfDay();
        $day = $today->subWeeks(self::WEEKS_BACK)->startOfWeek();
        $last = $today->addWeeks(self::WEEKS_AHEAD)->endOfWeek();

        $booking = app(BookAppointmentAction::class);
        $transition = app(TransitionAppointmentAction::class);

        $rank = 0;
        $written = 0;

        while ($day->lessThanOrEqualTo($last)) {
            if (! in_array($day->dayOfWeekIso, self::OPEN_WEEKDAYS, true)) {
                $day = $day->addDay();

                continue;
            }

            $perDay = 4 + $rank % 3;

            for ($slot = 0; $slot < $perDay; $slot++) {
                $appointment = $booking->execute($this->requestFor($day, $slot, $rank, $practitioners, $clients, $services));

                $this->settle($transition, $appointment, $day, $today, $rank);

                $rank++;
                $written++;
            }

            if ($written % 50 === 0) {
                $this->command?->info("  {$written} rendez-vous posés…");
            }

            $day = $day->addDay();
        }

        $this->command?->info("{$written} rendez-vous posés.");
    }

    /**
     * One visit: who, with whom, at what hour, and made of what.
     *
     * @param  list<Practitioner>  $practitioners
     * @param  list<Client>  $clients
     * @param  list<Service>  $services
     */
    private function requestFor(
        CarbonImmutable $day,
        int $slot,
        int $rank,
        array $practitioners,
        array $clients,
        array $services,
    ): BookAppointmentData {
        // Two visits on the same hour every eleventh: an establishment doubles
        // its agenda on purpose, and the screens have to draw it.
        $hour = $rank % 11 === 10 && $slot > 0 ? 9 + $slot - 1 : 9 + $slot;
        $startsAt = $day->setTime(min($hour, 17), $rank % 2 === 0 ? 0 : 30);

        $lead = $practitioners[$rank % count($practitioners)];
        $client = $clients[$rank % count($clients)];

        $treatments = [BookedTreatmentData::of($services[$rank % count($services)], $lead)];

        // Roughly one in seven is made of several treatments, and half of those
        // are shared with a second agenda.
        if ($rank % 7 === 3) {
            $second = $rank % 14 === 3 ? $practitioners[($rank + 1) % count($practitioners)] : $lead;
            $treatments[] = BookedTreatmentData::of($services[($rank + 5) % count($services)], $second);
        }

        $atHome = $client->address !== null && $rank % 3 === 1;

        return new BookAppointmentData(
            client: $client,
            treatments: $treatments,
            startsAt: $startsAt,
            location: $atHome ? AppointmentLocation::Home : AppointmentLocation::OnSite,
            address: $atHome ? $client->address : null,
            postalCode: $atHome ? $client->postal_code : null,
            city: $atHome ? $client->city : null,
            actorReference: 'seeder',
            internalNote: $rank % 17 === 5 ? 'Prévoir un peu plus de temps.' : null,
        );
    }

    /**
     * What a visit ended up being: honoured, missed, or called off.
     *
     * Every one of these is the establishment's own gesture, so **none of them
     * reaches the audit journal** — it records what happens out of sight, and
     * the counter acts under the eyes of whoever is looking at the screen.
     *
     * That the journal comes out empty is right, and deliberate: a database that
     * has just been rebuilt has no history. Seeding entries into it would invent
     * a past that never happened, which is the one thing an audit trail must
     * never contain.
     */
    private function settle(
        TransitionAppointmentAction $transition,
        Appointment $appointment,
        CarbonImmutable $day,
        CarbonImmutable $today,
        int $rank,
    ): void {
        $past = $day->lessThan($today);

        $outcome = match (true) {
            $past && $rank % 13 === 4 => AppointmentStatus::NoShow,
            $past && $rank % 7 === 2 => AppointmentStatus::Cancelled,
            $past => AppointmentStatus::Completed,

            // A handful of visits called off before the day comes.
            $rank % 19 === 5 => AppointmentStatus::Cancelled,
            default => null,
        };

        if ($outcome === null) {
            return;
        }

        $transition->execute(
            $appointment,
            $outcome,
            AppointmentActor::Administrator,
            actorId: 'seeder',
        );
    }

    /**
     * A weekly series, written in one go.
     *
     * The three scopes have no meaning without one, and it is the shape the
     * appointment panel is hardest on.
     */
    private function series(Practitioner $practitioner, Client $client, Service $service): void
    {
        $first = CarbonImmutable::now()->startOfWeek()->addWeeks(2)->addDays(1)->setTime(15, 0);

        app(RepeatAppointmentAction::class)->execute(
            new BookAppointmentData(
                client: $client,
                treatments: [BookedTreatmentData::of($service, $practitioner)],
                startsAt: $first,
                actorReference: 'seeder',
            ),
            new RecurrenceData(RecurrenceFrequency::Weeks, 1, repeatCycles: 5),
        );

        $this->command?->info('Une série hebdomadaire de six occurrences posée.');
    }
}
