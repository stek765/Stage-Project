<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $password = $_POST['password'];

    // Verifica che la password non sia vuota
    if (!empty($password)) {
        // Cripta la password usando BCRYPT
        $password_hash = password_hash($password, PASSWORD_BCRYPT);

        // Mostra l'hash della password
        echo "Password: " . htmlspecialchars($password) . "<br>";
        echo "Hash: " . $password_hash . "<br>";
    } else {
        echo "Si prega di inserire una password.";
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crittografia Password</title>
</head>
<body>
    <form method="POST">
        <label for="password">Inserisci la password:</label>
        <input type="text" id="password" name="password" required>
        <button type="submit">Cripta</button>
    </form>
</body>
</html>
