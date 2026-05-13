<?php
require 'vendor/autoload.php';

use App\Service\DescriptionAIService;

$service = new DescriptionAIService();
$result = $service->generateDescription('kayak', 10, null);

echo "=== Result ===\n";
var_dump($result);

echo "\n=== Expected success: " . ($result['success'] ? 'YES' : 'NO') . " ===\n";

if (!$result['success']) {
    echo "ERROR MESSAGE: " . $result['message'] . "\n";
} else {
    echo "DESCRIPTION: " . substr($result['description'], 0, 100) . "...\n";
}
