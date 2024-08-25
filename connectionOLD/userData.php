<?php
session_start();
if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Utente non loggato']);
    exit();
}

require 'config.php'; // File che contiene i dati di connessione al database

$username = $_SESSION['username'];

// Connessione al database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Verifica la connessione al database
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Connection failed: ' . $conn->connect_error]);
    exit();
}

// Recupera i dati dell'utente
$stmt = $conn->prepare('SELECT * FROM user WHERE username = ?');
$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user_data = $result->fetch_assoc();
    echo json_encode(['success' => true, 'data' => $user_data]);
} else {
    echo json_encode(['success' => false, 'message' => 'Utente non trovato']);
}

// Chiude lo statement e la connessione
$stmt->close();
$conn->close();
?>
