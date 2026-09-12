<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

function page_run(string $controllerClass, string $action, array $paramKeys = []): void
{
    $args = [];
    foreach ($paramKeys as $paramKey) {
        $args[] = (string) ($_GET[$paramKey] ?? '');
    }

    run_controller_action($controllerClass, $action, $args);
}
