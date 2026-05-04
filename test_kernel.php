<?php
require 'vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Kernel;

$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REQUEST_URI'] = '/activite/ecologique/generate-description';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_PORT'] = '8000';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/index.php';
$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
$_SERVER['REQUEST_TIME'] = time();

// Create kernel
$kernel = new Kernel($_ENV['APP_ENV'] ?? 'dev', $_ENV['APP_DEBUG'] ?? true);

// Create request with JSON body
$data = json_encode(['nom' => 'kayak', 'capacite' => 10, 'date' => '']);
$request = Request::create(
    '/activite/ecologique/generate-description',
    'POST',
    [],
    [],
    [],
    [],
    $data
);
$request->headers->set('Content-Type', 'application/json');

try {
    $response = $kernel->handle($request);
    echo "Status: " . $response->getStatusCode() . "\n";
    echo "Response:\n";
    echo $response->getContent();
} catch (\Throwable $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo "Trace:\n";
    echo $e->getTraceAsString();
}
