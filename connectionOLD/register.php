<?php
require 'config.php'; // File che contiene i dati di connessione al database

// Recupera i dati inviati tramite AJAX
$full_name = isset($_POST['full_name']) ? $_POST['full_name'] : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$nationality = isset($_POST['nationality']) ? $_POST['nationality'] : '';

if (!empty($full_name) && !empty($password) && !empty($nationality)) {
    // Connessione al database
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    // Verifica la connessione al database
    if ($conn->connect_error) {
        die('Connection failed: ' . $conn->connect_error);
    }

    // Verifica se l'utente esiste già
    $stmt = $conn->prepare('SELECT IDUser FROM user WHERE nome_completo = ?');
    $stmt->bind_param('s', $full_name);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo 'Username già in uso. Scegli un altro username.';
    } else {
        // Hash della password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Inserimento dei dati nel database
        $stmt = $conn->prepare('INSERT INTO user (username, email, password, nazionalità) VALUES (?, ?, ?)');
        $stmt->bind_param('sss', $full_name, $password_hash, $nationality);

        if ($stmt->execute()) {
            echo 'success';
        } else {
            echo 'Errore durante la registrazione. Riprova.';
        }
    }

    // Chiude lo statement e la connessione
    $stmt->close();
    $conn->close();
} else {
    echo 'Si prega di compilare tutti i campi.';
}
?>
