<?php

namespace App\Service;

use Psr\Log\LoggerInterface;

class PredictionAIService
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
    public function predictRisque(
        int $mois,
        int $jourSemaine,
        int $capacite,
        string $typeActivite
    ): array {
        $pythonPath  = 'python';
        $scriptPath  = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'predict.py';
        $type        = escapeshellarg(strtolower(explode(' ', trim($typeActivite))[0]));

        $command = sprintf(
            '%s %s %d %d %d %s 2>&1',
            $pythonPath,
            escapeshellarg($scriptPath),
            $mois,
            $jourSemaine,
            $capacite,
            $type
        );

        $output = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);

        // Filtrer les warnings Python pour garder uniquement le JSON
        $jsonLine = '';
        foreach ($output as $line) {
            if (str_starts_with(trim($line), '{')) {
                $jsonLine = $line;
                break;
            }
        }

        if ($jsonLine === '') {
            // Log full output for debugging (contains stderr and stdout)
            $this->logger->error('Prediction script did not return JSON', [
                'command' => $command,
                'exit_code' => $exitCode,
                'output' => $output,
            ]);

            // Save a short error summary to var/last_prediction_error.json for UI debugging in dev
            try {
                $projectDir = dirname(__DIR__, 2);
                $errorPath = $projectDir . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'last_prediction_error.json';
                $summary = [
                    'timestamp' => (new \DateTimeImmutable())->format(DATE_ATOM),
                    'command' => $command,
                    'exit_code' => $exitCode,
                    'output' => array_slice($output, -20), // keep last 20 lines
                ];
                @file_put_contents($errorPath, json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            } catch (\Throwable $e) {
                // Nothing to do if writing fails
            }

            return [
                'risque_pct' => 0,
                'niveau'     => 'inconnu',
                'message'    => 'Prédiction indisponible.',
                'couleur'    => 'secondary',
            ];
        }

        $result = json_decode($jsonLine, true);

        if (!is_array($result)) {
            $this->logger->error('Prediction JSON could not be decoded', [
                'command' => $command,
                'json' => $jsonLine,
                'json_error' => function_exists('json_last_error_msg') ? json_last_error_msg() : 'unknown',
            ]);

            // Save a short error summary for UI debugging in dev
            try {
                $projectDir = dirname(__DIR__, 2);
                $errorPath = $projectDir . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'last_prediction_error.json';
                $summary = [
                    'timestamp' => (new \DateTimeImmutable())->format(DATE_ATOM),
                    'command' => $command,
                    'json' => $jsonLine,
                    'json_error' => function_exists('json_last_error_msg') ? json_last_error_msg() : 'unknown',
                ];
                @file_put_contents($errorPath, json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            } catch (\Throwable $e) {
                // Ignore
            }

            return [
                'risque_pct' => 0,
                'niveau'     => 'inconnu',
                'message'    => 'Erreur de prédiction.',
                'couleur'    => 'secondary',
            ];
        }

        return $result;
    }
}