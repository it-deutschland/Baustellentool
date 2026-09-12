<?php
/**
 * API - Verkehrszeichen Endpunkte
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/TrafficSign.php';

use RSA21\Classes\TrafficSign;

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Nicht authentifiziert']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$signClass = new TrafficSign();

switch ($method) {
    case 'GET':
        if (isset($_GET['category'])) {
            $signs = $signClass->getSignsByCategory($_GET['category']);
            echo json_encode(['success' => true, 'data' => $signs]);
        } else if (isset($_GET['grouped'])) {
            $signs = $signClass->getSignsByCategories();
            echo json_encode(['success' => true, 'data' => $signs]);
        } else {
            $signs = $signClass->getAllSigns();
            echo json_encode(['success' => true, 'data' => $signs]);
        }
        break;

    case 'POST':
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Keine Berechtigung']);
            break;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        if ($signClass->addSign($data)) {
            echo json_encode(['success' => true, 'message' => 'Zeichen hinzugefügt']);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Zeichen konnte nicht hinzugefügt werden']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Methode nicht erlaubt']);
}
