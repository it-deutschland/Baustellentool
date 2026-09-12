<?php
/**
 * API - Platzierte Zeichen
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';
use RSA21\Config\Database;

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Nicht authentifiziert']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$db = Database::getInstance()->getConnection();
$projectId = $_GET['project_id'] ?? null;

if (!$projectId) {
    http_response_code(400);
    echo json_encode(['error' => 'Projekt-ID erforderlich']);
    exit;
}

switch ($method) {
    case 'GET':
        // Alle platzierten Zeichen eines Projekts abrufen
        $sql = "SELECT ps.*, ts.sign_code, ts.sign_name FROM placed_signs ps
                JOIN traffic_signs ts ON ps.sign_id = ts.id
                WHERE ps.project_id = :project_id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':project_id' => $projectId]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    case 'POST':
        // Neues Zeichen platzieren
        $data = json_decode(file_get_contents('php://input'), true);
        $sql = "INSERT INTO placed_signs (project_id, sign_id, x_position, y_position, rotation, scale, custom_text, is_mandatory)
                VALUES (:project_id, :sign_id, :x_position, :y_position, :rotation, :scale, :custom_text, :is_mandatory)";
        $stmt = $db->prepare($sql);
        
        $result = $stmt->execute([
            ':project_id' => $projectId,
            ':sign_id' => $data['sign_id'],
            ':x_position' => $data['x_position'] ?? 0,
            ':y_position' => $data['y_position'] ?? 0,
            ':rotation' => $data['rotation'] ?? 0,
            ':scale' => $data['scale'] ?? 1.0,
            ':custom_text' => $data['custom_text'] ?? null,
            ':is_mandatory' => $data['is_mandatory'] ?? false
        ]);
        
        if ($result) {
            echo json_encode(['success' => true, 'placed_sign_id' => $db->lastInsertId()]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Zeichen konnte nicht platziert werden']);
        }
        break;

    case 'PUT':
        // Zeichen aktualisieren
        $data = json_decode(file_get_contents('php://input'), true);
        $signId = $_GET['sign_id'] ?? null;
        
        if (!$signId) {
            http_response_code(400);
            echo json_encode(['error' => 'Zeichen-ID erforderlich']);
            break;
        }
        
        $sql = "UPDATE placed_signs SET x_position = :x, y_position = :y, rotation = :rotation, scale = :scale, custom_text = :text
                WHERE id = :id AND project_id = :project_id";
        $stmt = $db->prepare($sql);
        
        if ($stmt->execute([
            ':x' => $data['x_position'] ?? 0,
            ':y' => $data['y_position'] ?? 0,
            ':rotation' => $data['rotation'] ?? 0,
            ':scale' => $data['scale'] ?? 1.0,
            ':text' => $data['custom_text'] ?? null,
            ':id' => $signId,
            ':project_id' => $projectId
        ])) {
            echo json_encode(['success' => true, 'message' => 'Zeichen aktualisiert']);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Zeichen konnte nicht aktualisiert werden']);
        }
        break;

    case 'DELETE':
        // Zeichen löschen
        $signId = $_GET['sign_id'] ?? null;
        
        if (!$signId) {
            http_response_code(400);
            echo json_encode(['error' => 'Zeichen-ID erforderlich']);
            break;
        }
        
        $sql = "DELETE FROM placed_signs WHERE id = :id AND project_id = :project_id";
        $stmt = $db->prepare($sql);
        
        if ($stmt->execute([':id' => $signId, ':project_id' => $projectId])) {
            echo json_encode(['success' => true, 'message' => 'Zeichen gelöscht']);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Zeichen konnte nicht gelöscht werden']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Methode nicht erlaubt']);
}
