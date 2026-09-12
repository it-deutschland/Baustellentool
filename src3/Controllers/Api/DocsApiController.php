<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Response;

/**
 * API documentation endpoints.
 */
class DocsApiController extends Controller
{
    public function index(): void
    {
        $html = '<!doctype html><html><head><meta charset="utf-8"><title>Sonka Bau & Sonnenimmobilien - Multi Administration API</title>'
            . '<style>body{font-family:Arial,sans-serif;max-width:980px;margin:40px auto;color:#222}code{background:#f5f5f5;padding:2px 6px;border-radius:4px}</style>'
            . '</head><body><h1>Sonka Bau & Sonnenimmobilien - Multi Administration API</h1><p>Base URL: <code>/api/v1</code></p>'
            . '<ul><li>POST <code>/auth/login</code></li><li>POST <code>/auth/refresh</code></li><li>GET <code>/projects</code></li><li>GET <code>/projects/{id}</code></li><li>GET <code>/plans</code></li><li>GET <code>/plans/{id}</code></li><li>GET <code>/symbols</code></li><li>GET <code>/symbols/{id}</code></li></ul>'
            . '<p>OpenAPI spec: <a href="/api/v1/openapi.json">/api/v1/openapi.json</a></p></body></html>';
        Response::setHeader('Content-Type', 'text/html; charset=UTF-8');
        echo $html;
        exit;
    }

    public function openapi(): void
    {
        $spec = [
            'openapi' => '3.0.3',
            'info' => [
                'title' => 'Sonka Bau & Sonnenimmobilien - Multi Administration API',
                'version' => defined('APP_VERSION') ? (string) APP_VERSION : '1.0.0',
            ],
            'servers' => [
                ['url' => rtrim((string) APP_URL, '/') . '/api/v1'],
            ],
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'JWT',
                    ],
                ],
            ],
            'paths' => [
                '/auth/login' => [
                    'post' => [
                        'summary' => 'Authenticate and receive a JWT token.',
                        'responses' => ['200' => ['description' => 'JWT token response']],
                    ],
                ],
                '/auth/refresh' => [
                    'post' => [
                        'summary' => 'Refresh the current JWT token.',
                        'security' => [['bearerAuth' => []]],
                        'responses' => ['200' => ['description' => 'JWT token response']],
                    ],
                ],
                '/projects' => [
                    'get' => [
                        'summary' => 'List projects.',
                        'security' => [['bearerAuth' => []]],
                        'responses' => ['200' => ['description' => 'Project list']],
                    ],
                    'post' => [
                        'summary' => 'Create a project.',
                        'security' => [['bearerAuth' => []]],
                        'responses' => ['201' => ['description' => 'Project created']],
                    ],
                ],
                '/projects/{id}' => [
                    'get' => [
                        'summary' => 'Show project details.',
                        'security' => [['bearerAuth' => []]],
                        'responses' => ['200' => ['description' => 'Project details']],
                    ],
                    'put' => [
                        'summary' => 'Update a project.',
                        'security' => [['bearerAuth' => []]],
                        'responses' => ['200' => ['description' => 'Project updated']],
                    ],
                    'delete' => [
                        'summary' => 'Delete a project.',
                        'security' => [['bearerAuth' => []]],
                        'responses' => ['200' => ['description' => 'Project deleted']],
                    ],
                ],
                '/plans' => [
                    'get' => [
                        'summary' => 'List plans.',
                        'security' => [['bearerAuth' => []]],
                        'responses' => ['200' => ['description' => 'Plan list']],
                    ],
                ],
                '/plans/{id}' => [
                    'get' => [
                        'summary' => 'Show plan details.',
                        'security' => [['bearerAuth' => []]],
                        'responses' => ['200' => ['description' => 'Plan details']],
                    ],
                ],
                '/symbols' => [
                    'get' => [
                        'summary' => 'List symbols.',
                        'responses' => ['200' => ['description' => 'Symbol list']],
                    ],
                ],
                '/symbols/{id}' => [
                    'get' => [
                        'summary' => 'Show a symbol.',
                        'responses' => ['200' => ['description' => 'Symbol details']],
                    ],
                ],
            ],
        ];

        $this->json($spec);
    }
}
