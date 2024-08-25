<?php
// check_auth.php
session_start();

$response = ['authenticated' => false];

if (isset($_SESSION['username']) && isset($_SESSION['idUtente'])) {
    $response['authenticated'] = true;
    $response['username'] = $_SESSION['username'];
    $response['id'] = $_SESSION['idUtente'];
}

echo json_encode($response);
?>