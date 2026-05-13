<?php

namespace App\Service;

class DescriptionAIService
{
    public function generateDescription(
        string $nomActivite,
        int $capacite,
        ?\DateTimeInterface $dateActivite = null
    ): array {
        try {
            // Try multiple Python paths for Windows/Linux compatibility
            $pythonPaths = [
                'python',
                'python3',
                'C:\\Users\\WSI\\AppData\\Local\\Programs\\Python\\Python310\\python.exe',
                'C:\\Python310\\python.exe',
                '/usr/bin/python3',
                '/usr/bin/python',
            ];
            
            $pythonPath = null;
            foreach ($pythonPaths as $path) {
                $testCmd = ($path === 'python' || $path === 'python3') 
                    ? "{$path} --version" 
                    : "\"{$path}\" --version";
                $testOutput = [];
                exec($testCmd . ' 2>&1', $testOutput, $testReturn);
                if ($testReturn === 0) {
                    $pythonPath = $path;
                    break;
                }
            }
            
            if ($pythonPath === null) {
                return [
                    'success'     => false,
                    'description' => '',
                    'message'     => 'Python non trouvé.',
                ];
            }
            
            $scriptPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'generate_description.py';
            if (!file_exists($scriptPath)) {
                return [
                    'success'     => false,
                    'description' => '',
                    'message'     => 'Script Python introuvable.',
                ];
            }
            
            $mois = $dateActivite ? $dateActivite->format('n') : '';

            // Build command with proper quoting
            $pythonArg = (strpos($pythonPath, ' ') !== false) ? "\"{$pythonPath}\"" : $pythonPath;
            $scriptArg = (strpos($scriptPath, ' ') !== false) ? "\"{$scriptPath}\"" : $scriptPath;
            
            $command = sprintf(
                '%s %s %s %s %s',
                $pythonArg,
                $scriptArg,
                escapeshellarg($nomActivite),
                escapeshellarg((string) $capacite),
                escapeshellarg($mois)
            );

            // Execute and capture output
            $output = shell_exec($command . ' 2>&1');
            
            if ($output === null || trim($output) === '') {
                return [
                    'success'     => false,
                    'description' => '',
                    'message'     => 'Génération indisponible.',
                ];
            }

            // Fix encoding issues - convert from Windows locale to UTF-8
            if (function_exists('mb_detect_encoding')) {
                $detected = mb_detect_encoding($output, ['UTF-8', 'Windows-1252', 'ISO-8859-1'], true);
                if ($detected && $detected !== 'UTF-8') {
                    $output = mb_convert_encoding($output, 'UTF-8', $detected);
                }
            } elseif (function_exists('iconv') && strpos(php_uname(), 'Windows') !== false) {
                $output = iconv('Windows-1252', 'UTF-8//IGNORE', $output);
            }

            // Find JSON in output
            $lines = explode("\n", $output);
            $jsonLine = '';
            
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if (str_starts_with($trimmed, '{')) {
                    $jsonLine = $trimmed;
                    break;
                }
            }

            if ($jsonLine === '') {
                return [
                    'success'     => false,
                    'description' => '',
                    'message'     => 'Génération indisponible.',
                ];
            }

            // Decode JSON
            $result = json_decode($jsonLine, true);

            if ($result === null) {
                // Try to clean and retry
                $jsonLine = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $jsonLine);
                $result = json_decode($jsonLine, true);
                
                if ($result === null) {
                    return [
                        'success'     => false,
                        'description' => '',
                        'message'     => 'Erreur de décodage.',
                    ];
                }
            }

            if (!is_array($result)) {
                return [
                    'success'     => false,
                    'description' => '',
                    'message'     => 'Format invalide.',
                ];
            }

            if (isset($result['error'])) {
                return [
                    'success'     => false,
                    'description' => '',
                    'message'     => $result['error'],
                ];
            }

            return [
                'success'      => true,
                'description'  => $result['description'] ?? '',
                'type_detecte' => $result['type_detecte'] ?? '',
                'score'        => $result['score'] ?? 0,
                'message'      => 'OK',
            ];
        } catch (\Throwable $e) {
            return [
                'success'     => false,
                'description' => '',
                'message'     => 'Erreur: ' . $e->getMessage(),
            ];
        }
    }
}