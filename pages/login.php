<?php
declare(strict_types=1);
require_once __DIR__ . '/_run.php';
page_run(\App\Controllers\AuthController::class, 'showLogin');
