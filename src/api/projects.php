<?php
/**
 * API - Projekte Endpunkte
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/Project.php';
require_once __DIR__ . '/../classes/User.php';

use RSA21\Classes\Project;
use RSA21\Classes\User;

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Nicht authentifiziert']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$projectClass = new Project();
$userId = $_SESSION['user_id'];

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            $project = $projectClass->getProject($_GET['id'], $userId);
            if ($project) {
                echo json_encode(['success' => true, 'data' => $project]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Projekt nicht gefunden']);
            }
        } else {
            $page = $_GET['page'] ?? 1;
            $limit = 50;
            $offset = ($page - 1) * $limit;
            $projects = $projectClass->getUserProjects($userId, $limit, $offset);
            echo json_encode(['success' => true, 'data' => $projects]);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $projectId = $projectClass->createProject($userId, $data);
        if ($projectId) {
            echo json_encode(['success' => true, 'project_id' => $projectId]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Projekt konnte nicht erstellt werden']);
        }
        break;

    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        $projectId = $_GET['id'] ?? null;
        if (!$projectId) {
            http_response_code(400);
            echo json_encode(['error' => 'Projekt-ID erforderlich']);
            break;
        }
        
        if ($projectClass->updateProject($projectId, $userId, $data)) {
            echo json_encode(['success' => true, 'message' => 'Projekt aktualisiert']);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Projekt konnte nicht aktualisiert werden']);
        }
        break;

    case 'DELETE':
        $projectId = $_GET['id'] ?? null;
        if (!$projectId) {
            http_response_code(400);
            echo json_encode(['error' => 'Projekt-ID erforderlich']);
            break;
        }
        
        if ($projectClass->deleteProject($projectId, $userId)) {
            echo json_encode(['success' => true, 'message' => 'Projekt gelöscht']);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Projekt konnte nicht gelöscht werden']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Methode nicht erlaubt']);
}
