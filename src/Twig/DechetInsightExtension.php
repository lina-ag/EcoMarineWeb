<?php

namespace App\Twig;

use App\Entity\Dechet;
use App\Service\DechetCrossModuleInsightService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class DechetInsightExtension extends AbstractExtension
{
    public function __construct(
        private DechetCrossModuleInsightService $insightService
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('dechet_insight', [$this, 'getInsight']),
        ];
    }

    public function getInsight(Dechet $dechet): array
    {
        return $this->insightService->buildInsight($dechet);
    }
}