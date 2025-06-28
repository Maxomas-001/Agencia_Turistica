<?php
SessionManager::startSession();
header('Content-Type: application/json');

$response = ['redirect' => false];

if (!isset($_SESSION['user_id'])) {
    $response['redirect'] = 'login.php';
} elseif ($_SESSION['user_type'] === 'admin' && !str_contains($_SERVER['SCRIPT_NAME'], 'admin')) {
    $response['redirect'] = 'admin.php';
} elseif ($_SESSION['user_type'] !== 'admin' && str_contains($_SERVER['SCRIPT_NAME'], 'admin')) {
    $response['redirect'] = 'home.php';
}

echo json_encode($response);
?>