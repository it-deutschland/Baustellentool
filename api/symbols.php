<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$id = (string) ($_GET['id'] ?? '');

if ($id !== '') {
    run_controller_action(\App\Controllers\Api\SymbolApiController::class, 'show', [$id]);
    return;
}

run_controller_action(\App\Controllers\Api\SymbolApiController::class, 'index');
