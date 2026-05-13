#!/usr/bin/env php
<?php
require 'vendor/autoload.php';

use App\Service\DescriptionAIService;

echo "=== Test DescriptionAIService ===\n\n";

$service = new DescriptionAIService();

$tests = [
    ['nom' => 'kayak', 'capacite' => 10],
    ['nom' => 'plongée', 'capacite' => 5],
    ['nom' => 'observation', 'capacite' => 20],
];

foreach ($tests as $test) {
    echo "Test: " . $test['nom'] . " (" . $test['capacite'] . " personnes)\n";
    $result = $service->generateDescription($test['nom'], $test['capacite'], null);
    echo "  Success: " . ($result['success'] ? 'YES' : 'NO') . "\n";
    echo "  Message: " . $result['message'] . "\n";
    if ($result['success']) {
        echo "  Type: " . $result['type_detecte'] . "\n";
        echo "  Description: " . substr($result['description'], 0, 50) . "...\n";
    }
    echo "\n";
}
