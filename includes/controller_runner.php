<?php

declare(strict_types=1);

function run_controller_action(string $controllerClass, string $action, array $args = []): void
{
    if (!class_exists($controllerClass)) {
        http_response_code(500);
        echo 'Controller nicht gefunden: ' . e($controllerClass);
        exit;
    }

    $controller = new $controllerClass();

    if (!method_exists($controller, $action)) {
        http_response_code(500);
        echo 'Action nicht gefunden: ' . e($action);
        exit;
    }

    $controller->{$action}(...$args);
}
