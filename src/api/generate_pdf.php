<?php
/**
 * API - PDF Dokumentgenerierung
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/Project.php';
require_once __DIR__ . '/../classes/PDFGenerator.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use RSA21\Classes\Project;
use RSA21\Classes\PDFGenerator;

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Nicht authentifiziert']);
    exit;
}

$projectId = $_GET['project_id'] ?? null;
$docType = $_GET['type'] ?? null;

if (!$projectId || !$docType) {
    http_response_code(400);
    echo json_encode(['error' => 'Projekt-ID und Dokumenttyp erforderlich']);
    exit;
}

$projectClass = new Project();
$project = $projectClass->getProject($projectId, $_SESSION['user_id']);

if (!$project) {
    http_response_code(404);
    echo json_encode(['error' => 'Projekt nicht gefunden']);
    exit;
}

try {
    $pdfGenerator = new PDFGenerator();
    $filepath = null;

    switch ($docType) {
        case 'traffic_plan':
            $filepath = $pdfGenerator->generateTrafficSignPlan($projectId, $_SESSION['user_id']);
            break;
        case 'diversion_plan':
            $filepath = $pdfGenerator->generateDiversionPlan($projectId, $_SESSION['user_id']);
            break;
        case 'traffic_order':
            $filepath = $pdfGenerator->generateTrafficOrderRequest($projectId, $_SESSION['user_id']);
            break;
        case 'control_protocol':
            $filepath = $pdfGenerator->generateControlProtocol($projectId, $_SESSION['user_id']);
            break;
        case 'site_documentation':
            $filepath = $pdfGenerator->generateSiteDocumentation($projectId, $_SESSION['user_id']);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Unbekannter Dokumenttyp']);
            exit;
    }

    if ($filepath && file_exists($filepath)) {
        echo json_encode([
            'success' => true,
            'message' => 'PDF erfolgreich generiert',
            'download_url' => '/uploads/' . basename($filepath)
        ]);
    } else {
        throw new Exception('PDF konnte nicht generiert werden');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
