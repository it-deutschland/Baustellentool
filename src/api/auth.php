<?php
/**
 * API - Authentifizierung
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/User.php';

use RSA21\Classes\User;

session_start();

$action = $_GET['action'] ?? null;
$userClass = new User();

switch ($action) {
    case 'login':
        $data = json_decode(file_get_contents('php://input'), true);
        $user = $userClass->authenticate($data['email'], $data['password']);
        
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            
            echo json_encode(['success' => true, 'message' => 'Anmeldung erfolgreich']);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'E-Mail oder Passwort inkorrekt']);
        }
        break;

    case 'register':
        $data = json_decode(file_get_contents('php://input'), true);
        
        if ($userClass->emailExists($data['email'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Diese E-Mail ist bereits registriert']);
            break;
        }
        
        if ($userClass->register($data)) {
            echo json_encode(['success' => true, 'message' => 'Registrierung erfolgreich']);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Registrierung fehlgeschlagen']);
        }
        break;

    case 'logout':
        session_destroy();
        header('Location: /');
        exit;

    case 'profile':
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Nicht authentifiziert']);
            break;
        }
        
        $user = $userClass->getUserById($_SESSION['user_id']);
        echo json_encode(['success' => true, 'user' => $user]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unbekannte Aktion']);
}
