<?php
require 'config.php';

$permission = true;
if (!isset($_SESSION['username'])) {
    // echo json_encode(['success' => false, 'message' => 'Utente non loggato']);
    $permissions = false;
} else {
    $username = $_SESSION['username'];
    $userId = $_SESSION['idUtente'];
}

// Recupera i dati inviati tramite AJAX
$type = isset($_POST['type']) ? $_POST['type'] : '';
if ($type == 'user') {
    $full_name = isset($_POST['full_name']) ? $_POST['full_name'] : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $nationality = isset($_POST['nationality']) ? $_POST['nationality'] : '';
    $email = isset($_POST['email']) ? $_POST['email'] : '';

    if (!empty($full_name) && !empty($password) && !empty($nationality) && !empty($email)) {
        // Connessione al database
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        // Verifica la connessione al database
        if ($conn->connect_error) {
            die('Connection failed: ' . $conn->connect_error);
        }

        // Verifica se l'utente esiste già
        $stmt = $conn->prepare('SELECT id FROM utente WHERE nome_completo = ?');
        $stmt->bind_param('s', $full_name);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            echo 'Username già in uso. Scegli un altro username.';
        } else {
            // Hash della password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            // Inserimento dei dati nel database
            $stmt = $conn->prepare('INSERT INTO utente (nome_completo, email, password, nazionalità)
                                    VALUES (?, ?, ? ,?)');
            // $stmt = $conn->prepare('INSERT INTO user (username, password, nazionalita) VALUES (?, ?, ?)');
            $stmt->bind_param('ssss', $full_name, $email, $password_hash, $nationality);

            if ($stmt->execute()) {
                $user_id = $conn->insert_id;
                $_SESSION['username'] = $full_name;
                $_SESSION['idUtente'] = $user_id;
                echo 'Success';
            } else {
                echo $stmt->error . 'Errore durante la registrazione. Riprova.';
            }
        }

        // Chiude lo statement e la connessione
        $stmt->close();
        $conn->close();
    } else {
        echo 'Si prega di compilare tutti i campi.';
    }
} else if ($type == 'musician' && $permission) {
    $tipo_artista = isset($_POST['tipo_artista']) ? $_POST['tipo_artista'] : '';
    $nome_arte = isset($_POST['nome_arte']) ? $_POST['nome_arte'] : '';
    $genere_musicale = isset($_POST['genere_musicale']) ? $_POST['genere_musicale'] : '';
    $valDecode = json_decode($_POST['membri_band'], true);
    $membri_band = isset($valDecode) ? $valDecode : '';

    if (!empty($tipo_artista) && !empty($nome_arte) && !empty($genere_musicale)) {
        // Connessione al database
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        // Verifica la connessione al database
        if ($conn->connect_error) {
            die('Connection failed: ' . $conn->connect_error);
        }

        // Verifica se l'utente è loggato
        if (!$_SESSION['username']) {
            echo 'Necessario effettuare il login prima della registrazione.';
        } else {
            $full_name = $_SESSION['username'];
            $id = $_SESSION['idUtente'];
        }

        // Inserimento dei dati nel database
        $stmt = $conn->prepare('INSERT INTO Profili_Artista (id_utente, tipo_artista, nome_arte, genere_musicale)
                                    VALUES (?, ?, ?, ?)');
        // $stmt = $conn->prepare('INSERT INTO user (username, password, nazionalita) VALUES (?, ?, ?)');
        $stmt->bind_param('ssss', $id, $tipo_artista, $nome_arte, $genere_musicale);
        if (!$stmt->execute()) {
            echo 'Errore durante la registrazione. Riprova.';
            exit();
        }
        $valuedId = $stmt->insert_id;
        foreach ($membri_band as &$membro) {
            // Inserimento dei dati nel database
            $stmt = $conn->prepare('INSERT INTO Profili_Artista (tipo_artista, nome_arte, genere_musicale)
                                    VALUES (?, ?, ?)');
            $stmt->bind_param('sss', $tipo_artista, $membro["nome"], $genere_musicale);
            if (!$stmt->execute()) {
                echo 'Errore durante la registrazione. Riprova.';
                exit();
            }
            $idArtista = $stmt->insert_id;
            $stmt = $conn->prepare('INSERT INTO Membri_Band (id_band, id_artista, ruolo)
                                    VALUES (?, ?, ?)');
            $stmt->bind_param('sss', $valuedId, $idArtista, $membro["ruolo"]);
            if (!$stmt->execute()) {
                echo 'Errore durante la registrazione. Riprova.';
                exit();
            }
        }
        echo "success";
        // Chiude lo statement e la connessione
        $stmt->close();
        $conn->close();
    } else {
        echo 'Si prega di compilare tutti i campi.';
    }
} else if ($type == 'venue' && $permission) {
    $nome_locale = isset($_POST['nome_locale']) ? $_POST['nome_locale'] : '';
    $indirizzo = isset($_POST['indirizzo']) ? $_POST['indirizzo'] : '';

    if (!empty($nome_locale) && !empty($indirizzo)) {
        // Connessione al database
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        // Verifica la connessione al database
        if ($conn->connect_error) {
            die('Connection failed: ' . $conn->connect_error);
        }

        // Verifica se l'utente è loggato
        if (!$_SESSION['username']) {
            echo 'Necessario effettuare il login prima della registrazione.';
        } else {
            $full_name = $_SESSION['username'];
            $id = $_SESSION['idUtente'];
        }

        // Inserimento dei dati nel database
        $stmt = $conn->prepare('INSERT INTO locale (id_utente, nome_locale, indirizzo)
                                    VALUES (?, ?, ?)');
        // $stmt = $conn->prepare('INSERT INTO user (username, password, nazionalita) VALUES (?, ?, ?)');
        $stmt->bind_param('sss', $id, $nome_locale, $indirizzo);

        if ($stmt->execute()) {
            echo 'success';
        } else {
            echo 'Errore durante la registrazione. Riprova.';
        }

        // Chiude lo statement e la connessione
        $stmt->close();
        $conn->close();
    } else {
        echo 'Si prega di compilare tutti i campi.';
    }
} else {
    echo 'Errore.';
}
