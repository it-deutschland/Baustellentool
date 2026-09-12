<?php
/**
 * API - Validierung Endpunkte
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/Project.php';
require_once __DIR__ . '/../classes/Validator.php';

use RSA21\Classes\Project;
use RSA21\Classes\Validator;

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Nicht authentifiziert']);
    exit;
}

$projectId = $_GET['project_id'] ?? null;
if (!$projectId) {
    http_response_code(400);
    echo json_encode(['error' => 'Projekt-ID erforderlich']);
    exit;
}

$projectClass = new Project();
$project = $projectClass->getProject($projectId, $_SESSION['user_id']);

if (!$project) {
    http_response_code(404);
    echo json_encode(['error' => 'Projekt nicht gefunden']);
    exit;
}

$validator = new Validator();
$validationResult = $validator->validateProject($projectId);

echo json_encode(['success' => true, 'validation' => $validationResult]);
