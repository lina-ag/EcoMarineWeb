<?php

namespace App\Twig;

use App\Entity\Dechet;
use App\Service\WasteOriginAnalyzerService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class WasteOriginExtension extends AbstractExtension
{
    public function __construct(
        private WasteOriginAnalyzerService $originAnalyzer
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('waste_origin_ai', [$this, 'analyze']),
        ];
    }

    public function analyze(Dechet $dechet): array
    {
        return $this->originAnalyzer->analyze($dechet);
    }
}