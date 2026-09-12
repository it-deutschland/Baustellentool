<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/_run.php';
page_run(\App\Controllers\MaterialController::class, 'create');
