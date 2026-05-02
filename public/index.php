<?php

use App\Kernel;

// Guard against very low local PHP memory limits that can break Twig compilation.
if (function_exists('ini_get') && function_exists('ini_set')) {
    $currentLimit = ini_get('memory_limit');
    if (is_string($currentLimit) && $currentLimit !== '' && $currentLimit !== '-1') {
        $value = (int) $currentLimit;
        $unit = strtolower(substr($currentLimit, -1));
        if ($unit === 'g') {
            $value *= 1024 * 1024 * 1024;
        } elseif ($unit === 'm') {
            $value *= 1024 * 1024;
        } elseif ($unit === 'k') {
            $value *= 1024;
        }

        if ($value > 0 && $value < 134217728) {
            ini_set('memory_limit', '256M');
        }
    }
}

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
