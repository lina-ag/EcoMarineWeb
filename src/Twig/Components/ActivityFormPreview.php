<?php

namespace App\Twig\Components;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('activity_form_preview')]
final class ActivityFormPreview
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public string $activityName = '';

    #[LiveProp(writable: true)]
    public string $activityDate = '';

    #[LiveProp(writable: true)]
    public int $capacity = 1;

    #[LiveProp(writable: true)]
    public string $description = '';

    #[LiveProp(writable: true)]
    public string $isRecurring = '0';

    #[LiveProp(writable: true)]
    public string $recurrenceEndDate = '';

    #[LiveProp(writable: true)]
    public string $selectedDays = '';

    public function getSummary(): array
    {
        $capacity = max(1, $this->capacity);
        $descriptionLength = mb_strlen(trim($this->description));
        $date = $this->parseDate($this->activityDate);
        $endDate = $this->parseDate($this->recurrenceEndDate);
        $days = $this->normalizeDays($this->selectedDays);
        $isRecurring = $this->isRecurringEnabled();

        $tone = match (true) {
            $capacity >= 100 => 'grand format',
            $capacity >= 30 => 'atelier collectif',
            default => 'groupe intime',
        };

        $season = $date ? $this->detectSeason($date) : 'a definir';
        $occurrenceCount = $isRecurring && $date && $endDate && $days !== []
            ? $this->countOccurrences($date, $endDate, $days)
            : 1;

        return [
            'title' => trim($this->activityName) !== '' ? trim($this->activityName) : 'Nouvelle activite eco',
            'date_label' => $date ? $date->format('d/m/Y') : 'Date a definir',
            'season_label' => $season,
            'capacity' => $capacity,
            'tone' => $tone,
            'description_length' => $descriptionLength,
            'description_quality' => $descriptionLength >= 120 ? 'detaillee' : ($descriptionLength >= 40 ? 'equilibree' : 'courte'),
            'recurring' => $isRecurring,
            'days_label' => $days !== [] ? implode(', ', array_map([$this, 'dayLabel'], $days)) : 'Aucun jour selectionne',
            'end_date_label' => $endDate ? $endDate->format('d/m/Y') : 'Date de fin non definie',
            'occurrence_count' => max(1, $occurrenceCount),
            'status_label' => $this->buildStatusLabel($capacity, $isRecurring, $occurrenceCount),
        ];
    }

    private function isRecurringEnabled(): bool
    {
        return in_array($this->isRecurring, ['1', 'true', 'on'], true);
    }

    private function parseDate(string $value): ?\DateTimeImmutable
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false ? $date : null;
    }

    /**
     * @return int[]
     */
    private function normalizeDays(string $value): array
    {
        $rawParts = array_filter(array_map('trim', explode(',', $value)), static fn (string $day): bool => $day !== '');
        $days = [];

        foreach ($rawParts as $part) {
            $day = (int) $part;
            if ($day >= 1 && $day <= 7) {
                $days[] = $day;
            }
        }

        $days = array_values(array_unique($days));
        sort($days);

        return $days;
    }

    private function detectSeason(\DateTimeImmutable $date): string
    {
        $month = (int) $date->format('n');

        return match (true) {
            in_array($month, [12, 1, 2], true) => 'hiver marin',
            in_array($month, [3, 4, 5], true) => 'printemps doux',
            in_array($month, [6, 7, 8], true) => 'saison estivale',
            default => 'automne lumineux',
        };
    }

    private function countOccurrences(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate, array $days): int
    {
        if ($endDate < $startDate) {
            return 0;
        }

        $count = 0;
        $cursor = $startDate;

        while ($cursor <= $endDate) {
            if (in_array((int) $cursor->format('N'), $days, true)) {
                ++$count;
            }

            $cursor = $cursor->modify('+1 day');
        }

        return $count;
    }

    private function dayLabel(int $day): string
    {
        return match ($day) {
            1 => 'Lun',
            2 => 'Mar',
            3 => 'Mer',
            4 => 'Jeu',
            5 => 'Ven',
            6 => 'Sam',
            7 => 'Dim',
            default => '?',
        };
    }

    private function buildStatusLabel(int $capacity, bool $isRecurring, int $occurrenceCount): string
    {
        if ($isRecurring && $occurrenceCount > 1) {
            return sprintf('Serie de %d sessions', $occurrenceCount);
        }

        if ($capacity >= 100) {
            return 'Grande capacite';
        }

        if ($capacity >= 30) {
            return 'Capacite intermediaire';
        }

        return 'Format cible';
    }
}
