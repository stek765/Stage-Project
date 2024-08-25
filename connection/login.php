<?php
require 'config.php'; // File che contiene i dati di connessione al database

// Recupera i dati inviati tramite AJAX
$username = isset($_POST['username']) ? $_POST['username'] : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

if (!empty($username) && !empty($password)) {
    // Connessione al database
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    // Verifica la connessione al database
    if ($conn->connect_error) {
        die('Connection failed: ' . $conn->connect_error);
    }

    // Prepara e esegue la query
    $stmt = $conn->prepare('SELECT password, id FROM Utente WHERE nome_completo = ?');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $stmt->store_result();

    // Verifica se l'utente esiste
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($password_hash, $id);
        $stmt->fetch();

        // Verifica la password
        if (password_verify($password, $password_hash)) {
            // Imposta la sessione
            $_SESSION['username'] = $username;
            $_SESSION['idUtente'] = $id;
            // $_SESSION['ruolo']
            echo 'success';
        } else {
            echo 'Credenziali non valide. Riprova.';
        }
    } else {
        echo 'Credenziali non valide. Riprova.';
    }

    // Chiude lo statement e la connessione
    $stmt->close();
    $conn->close();
} else {
    echo 'Si prega di compilare tutti i campi.';
}
