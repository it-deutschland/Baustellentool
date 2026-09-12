<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$action = strtolower((string) ($_REQUEST['action'] ?? ''));
$id = (string) ($_REQUEST['id'] ?? '');

if ($action === 'save' && $id !== '') {
    run_controller_action(\App\Controllers\PlanController::class, 'save', [$id]);
    return;
}

if ($action === 'export' && $id !== '') {
    run_controller_action(\App\Controllers\PlanController::class, 'export', [$id]);
    return;
}

if ($id !== '') {
    run_controller_action(\App\Controllers\Api\PlanApiController::class, 'show', [$id]);
    return;
}

run_controller_action(\App\Controllers\Api\PlanApiController::class, 'index');
