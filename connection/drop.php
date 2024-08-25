<?php
require 'config.php';

// Connessione al database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Disabilita i controlli delle chiavi esterne
$conn->query("SET FOREIGN_KEY_CHECKS = 0");

// Elimina tutti i dati dalle tabelle
$tables = ['Membri_Band', 'Candidature', 'Evento', 'Newsletter', 'Profili_Artista', 'Locale', 'Utente'];
foreach ($tables as $table) {
    $conn->query("DELETE FROM $table");
}

// Riabilita i controlli delle chiavi esterne
$conn->query("SET FOREIGN_KEY_CHECKS = 1");

echo "Tutte le tabelle sono state resettate.";

$conn->close();
header('Location: ../index.html');
?>
