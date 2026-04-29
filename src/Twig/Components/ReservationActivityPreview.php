<?php

namespace App\Twig\Components;

use App\Entity\ActiviteEcologique;
use App\Repository\ActiviteEcologiqueRepository;
use App\Repository\ReservationRepository;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('reservation_activity_preview')]
final class ReservationActivityPreview
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public ?int $activityId = null;

    #[LiveProp(writable: true)]
    public int $requestedPeople = 1;

    public function __construct(
        private readonly ActiviteEcologiqueRepository $activiteEcologiqueRepository,
        private readonly ReservationRepository $reservationRepository,
    ) {
    }

    public function getActivity(): ?ActiviteEcologique
    {
        if (!$this->activityId) {
            return null;
        }

        return $this->activiteEcologiqueRepository->find($this->activityId);
    }

    public function getAllowedDates(): array
    {
        $activity = $this->getActivity();
        if (!$activity) {
            return [];
        }

        $dates = $this->activiteEcologiqueRepository->findDatesForReservationByName((string) $activity->getNomActivite());

        if ([] === $dates && $activity->getDateActivite() instanceof \DateTimeInterface) {
            $dates = [$activity->getDateActivite()->format('Y-m-d')];
        }

        return $dates;
    }

    public function getFormattedDates(): array
    {
        $formattedDates = [];

        foreach ($this->getAllowedDates() as $date) {
            $dateObject = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
            $formattedDates[] = $dateObject ? $dateObject->format('d/m/Y') : $date;
        }

        return $formattedDates;
    }

    public function getCapacity(): array
    {
        $activity = $this->getActivity();
        if (!$activity) {
            return [
                'state' => 'default',
                'state_label' => '--',
                'used' => 0,
                'total' => 0,
                'remaining' => 0,
                'requested' => max(1, $this->requestedPeople),
                'fits_request' => false,
                'request_message' => 'Selectionnez une activite pour voir la disponibilite.',
                'progress_percent' => 0,
            ];
        }

        $totalCapacity = max(0, (int) ($activity->getCapacite() ?? 0));
        $usedCapacity = $this->reservationRepository->countReservedPeopleForActivity($activity);
        $remainingCapacity = max(0, $totalCapacity - $usedCapacity);
        $requestedPeople = max(1, $this->requestedPeople);

        if ($totalCapacity <= 0 || $remainingCapacity <= 0) {
            $state = 'full';
            $stateLabel = 'Complet';
        } elseif ($usedCapacity / $totalCapacity >= 0.5) {
            $state = 'warning';
            $stateLabel = 'Alerte';
        } else {
            $state = 'available';
            $stateLabel = 'Disponible';
        }

        $fitsRequest = $remainingCapacity >= $requestedPeople && $remainingCapacity > 0;
        $requestMessage = $fitsRequest
            ? sprintf('%d place(s) demandee(s) compatibles avec la capacite restante.', $requestedPeople)
            : sprintf('Il reste %d place(s), insuffisant pour %d participant(s).', $remainingCapacity, $requestedPeople);

        if ('full' === $state) {
            $requestMessage = sprintf('Activite complete (%d/%d places occupees).', $usedCapacity, $totalCapacity);
        }

        $progressPercent = $totalCapacity > 0 ? (int) min(100, round(($usedCapacity / $totalCapacity) * 100)) : 0;

        return [
            'state' => $state,
            'state_label' => $stateLabel,
            'used' => $usedCapacity,
            'total' => $totalCapacity,
            'remaining' => $remainingCapacity,
            'requested' => $requestedPeople,
            'fits_request' => $fitsRequest,
            'request_message' => $requestMessage,
            'progress_percent' => $progressPercent,
        ];
    }
}
