<?php
require 'vendor/autoload.php';

use App\Service\DescriptionAIService;

$service = new DescriptionAIService();
$result = $service->generateDescription('kayak', 10, null);

echo "=== Test Result ===\n";
var_dump($result);
