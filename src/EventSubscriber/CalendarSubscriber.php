<?php

namespace App\EventSubscriber;

use App\Entity\ActiviteEcologique;
use App\Repository\ActiviteEcologiqueRepository;
use CalendarBundle\CalendarEvents;
use CalendarBundle\Entity\Event;
use CalendarBundle\Event\CalendarEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CalendarSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ActiviteEcologiqueRepository $activiteRepository,
        private UrlGeneratorInterface $urlGenerator
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            CalendarEvents::SET_DATA => 'onCalendarSetData',
        ];
    }

    public function onCalendarSetData(CalendarEvent $calendar): void
    {
        $start = \DateTimeImmutable::createFromInterface($calendar->getStart())->setTime(0, 0, 0);
        $end = \DateTimeImmutable::createFromInterface($calendar->getEnd())->setTime(23, 59, 59);
        $filters = $calendar->getFilters();

        $activityFilter = trim((string) ($filters['activity'] ?? ''));
        $statusFilter = trim((string) ($filters['status'] ?? 'all'));
        $searchFilter = mb_strtolower(trim((string) ($filters['q'] ?? '')));

        $qb = $this->activiteRepository
            ->createQueryBuilder('a')
            ->leftJoin('a.reservations', 'r')
            ->addSelect('r')
            ->andWhere('a.date_activite BETWEEN :start AND :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end);

        if ($activityFilter !== '') {
            $qb
                ->andWhere('LOWER(a.nom_activite) LIKE :activity')
                ->setParameter('activity', '%' . mb_strtolower($activityFilter) . '%');
        }

        $activities = $qb->getQuery()->getResult();

        $today = new \DateTimeImmutable('today');

        foreach ($activities as $activity) {
            if (!$activity instanceof ActiviteEcologique || !$activity->getDateActivite()) {
                continue;
            }

            $activityDate = \DateTimeImmutable::createFromInterface($activity->getDateActivite())->setTime(0, 0, 0);
            $status = $activityDate < $today ? 'past' : ($activityDate > $today ? 'upcoming' : 'today');

            if ($statusFilter !== 'all' && $status !== $statusFilter) {
                continue;
            }

            $activityName = $activity->getNomActivite() ?? 'Activite non definie';
            $description = (string) ($activity->getDescription() ?? '');
            $capacity = (int) ($activity->getCapacite() ?? 0);
            $reservedPeople = 0;

            foreach ($activity->getReservations() as $reservation) {
                $reservedPeople += (int) ($reservation->getNombrePersonnes() ?? 0);
            }

            $remaining = max(0, $capacity - $reservedPeople);
            $fillRate = $capacity > 0 ? (int) round(($reservedPeople / $capacity) * 100) : 0;
            $title = sprintf('%s (%d/%d)', $activityName, $reservedPeople, $capacity);

            if ($searchFilter !== '') {
                $searchHaystack = mb_strtolower(implode(' ', [
                    $activityName,
                    $description,
                    (string) $capacity,
                    (string) $reservedPeople,
                ]));

                if (!str_contains($searchHaystack, $searchFilter)) {
                    continue;
                }
            }

            [$backgroundColor, $borderColor] = match ($status) {
                'past' => ['#94a3b8', '#64748b'],
                'today' => ['#f59e0b', '#d97706'],
                default => ['#0ea5a5', '#0f766e'],
            };

            $activityUrl = $this->urlGenerator->generate('app_activite_ecologique_show', [
                'id_activite' => $activity->getIdActivite(),
            ]);

            $calendar->addEvent(new Event(
                $title,
                $activity->getDateActivite(),
                null,
                null,
                [
                    'url' => $activityUrl,
                    'backgroundColor' => $backgroundColor,
                    'borderColor' => $borderColor,
                    'textColor' => '#ffffff',
                    'activity' => $activityName,
                    'description' => $description,
                    'capacity' => $capacity,
                    'reservedPeople' => $reservedPeople,
                    'remaining' => $remaining,
                    'fillRate' => $fillRate,
                    'status' => $status,
                ]
            ));
        }
    }
}
