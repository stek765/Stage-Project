<?php
require 'config.php'; // File che contiene i dati di connessione al database

$permission = true;
if (!isset($_SESSION['username'])) {
    // echo json_encode(['success' => false, 'message' => 'Utente non loggato']);
    $permissions = false;
} else {
    $username = $_SESSION['username'];
    $userId = $_SESSION['idUtente'];
}

// Connessione al database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Verifica la connessione al database
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Connection failed: ' . $conn->connect_error]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_account' && $permission) {
    // Recupera i dati dell'utente
    // $stmt = $conn->prepare('SELECT * FROM Utente WHERE nome_completo = ?');
    $stmt = $conn->prepare('SELECT * FROM utente WHERE id = ?');
    $stmt->bind_param('s', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    if ($result->num_rows > 0) {
        $user_data = $result->fetch_assoc();
        //scarico dati artista
        $stmt = $conn->prepare('SELECT * FROM profili_artista p WHERE p.id_utente = ?');
        $stmt->bind_param('s', $_SESSION['idUtente']);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        $artista_data = [];
        while ($row = $result->fetch_assoc()) {
            //e artisti della band
            $stmt2 = $conn->prepare('SELECT m.ruolo, p.nome_arte FROM `membri_band` `m` JOIN `profili_artista` `p` on m.id_artista=p.id WHERE m.id_band=?');
            $stmt2->bind_param('s', $row['id']);
            $stmt2->execute();
            $result2 = $stmt2->get_result();
            $stmt2->close();
            $band_data = [];
            while ($row2 = $result2->fetch_assoc()) {
                $band_data[] = $row2;
            }
            $artista_data[] = [$row, $band_data];
        }

        //scarico dati locali
        $stmt = $conn->prepare('SELECT * FROM locale l WHERE l.id_utente = ?');
        $stmt->bind_param('s', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        $locale_data = [];
        while ($row = $result->fetch_assoc()) {
            $locale_data[] = $row;
        }

        $data = [
            'user' => $user_data,
            'artista' => $artista_data,
            'locale' => $locale_data
        ];
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Utente non trovato']);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_account' && $permission) {
    if (!empty($userId)) {
        // 1. Elimina le candidature collegate ai profili artista dell'utente
        $stmt = $conn->prepare('DELETE FROM Candidature
                                WHERE id_artista IN (
                                    SELECT id
                                    FROM Profili_Artista
                                    WHERE id_utente = ?
                                )');
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Errore durante la preparazione della query22: ' . $conn->error]);
            exit();
        }
        $stmt->bind_param('i', $userId);
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Errore durante l\'eliminazione dell\'account e delle relative dipendenze: ' . $e->getMessage()]);
            $stmt->close();
            exit();
        }
        $stmt->close();

        // 2. Elimina i membri della band collegati ai profili artista dell'utente
        $stmt = $conn->prepare('DELETE FROM Membri_Band
                                WHERE id_band IN (
                                    SELECT id
                                    FROM Profili_Artista
                                    WHERE id_utente = ?
                                )');
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Errore durante la preparazione della query: ' . $conn->error]);
            exit();
        }
        $stmt->bind_param('i', $userId);
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Errore durante l\'eliminazione dell\'account e delle relative dipendenze: ' . $e->getMessage()]);
            $stmt->close();
            exit();
        }
        $stmt->close();

        // 3. Elimina gli eventi collegati ai locali dell'utente
        $stmt = $conn->prepare('DELETE FROM Evento
                                WHERE id_locale IN (
                                    SELECT id
                                    FROM Locale
                                    WHERE id_utente = ?
                                )');
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Errore durante la preparazione della query: ' . $conn->error]);
            exit();
        }
        $stmt->bind_param('i', $userId);
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Errore durante l\'eliminazione dell\'account e delle relative dipendenze: ' . $e->getMessage()]);
            $stmt->close();
            exit();
        }
        $stmt->close();

        // 4. Elimina i profili artista dell'utente
        $stmt = $conn->prepare('DELETE FROM Profili_Artista WHERE id_utente = ?');
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Errore durante la preparazione della query: ' . $conn->error]);
            exit();
        }
        $stmt->bind_param('i', $userId);
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Errore durante l\'eliminazione dell\'account e delle relative dipendenze: ' . $e->getMessage()]);
            $stmt->close();
            exit();
        }
        $stmt->close();

        // 5. Elimina i locali dell'utente
        $stmt = $conn->prepare('DELETE FROM Locale WHERE id_utente = ?');
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Errore durante la preparazione della query: ' . $conn->error]);
            exit();
        }
        $stmt->bind_param('i', $userId);
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Errore durante l\'eliminazione dell\'account e delle relative dipendenze: ' . $e->getMessage()]);
            $stmt->close();
            exit();
        }
        $stmt->close();

        // 6. Elimina l'utente stesso
        $stmt = $conn->prepare('DELETE FROM Utente WHERE id = ?');
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Errore durante la preparazione della query: ' . $conn->error]);
            exit();
        }
        $stmt->bind_param('i', $userId);
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Errore durante l\'eliminazione dell\'account e delle relative dipendenze: ' . $e->getMessage()]);
            $stmt->close();
            exit();
        }
        $stmt->close();

        // Se tutto è andato a buon fine, conferma la transazione
        echo json_encode(['success' => true, 'message' => 'Eliminazione completata con successo.']);

        // Distruggi la sessione dell'utente dopo l'eliminazione dell'account
        session_destroy();
    } else {
        echo json_encode(['success' => false, 'message' => 'Errore durante la modifica dell\'account, non possono esservi valori nulli.']);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_user' && $permission) {
    $nome_completo = isset($_POST['nome_completo']) ? $_POST['nome_completo'] : '';
    $email = isset($_POST['email']) ? $_POST['email'] : '';
    // $password = isset($_POST['password']) ? $_POST['password'] : '';
    $nazionalita = isset($_POST['nazionalita']) ? $_POST['nazionalita'] : '';

    if (!empty($nome_completo) && !empty($email) && !empty($nazionalita)) {
        $_SESSION['username'] = $nome_completo;

        // Inserimento dei dati nel database
        // $stmt = $conn->prepare('UPDATE `utente` SET `nome_completo` = ?, `email` = ?, `password` = ?, `nazionalità` = ? 
        //                             WHERE `id` = ?');
        // $stmt->bind_param('ssssi', $nome_completo, $email, $password, $nazionalita, $userId);
        $stmt = $conn->prepare('UPDATE `utente` SET `nome_completo` = ?, `email` = ?, `nazionalità` = ? 
                                WHERE `id` = ?');
        $stmt->bind_param('sssi', $nome_completo, $email, $nazionalita, $userId);
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Errore durante la modifica dell\'account.']);
            exit();
        }
        // $valuedId = $stmt->insert_id;
        // foreach ($membri_band as &$membro) {
        //     // Inserimento dei dati nel database
        //     $stmt = $conn->prepare('INSERT INTO Profili_Artista (tipo_artista, nome_arte, genere_musicale)
        //                             VALUES (?, ?, ?)');
        //     $stmt->bind_param('sss', $tipo_artista, $membro["nome"], $genere_musicale);
        //     if (!$stmt->execute()) {
        //         echo 'Errore durante la registrazione. Riprova.';
        //         exit();
        //     }
        //     $idArtista = $stmt->insert_id;
        //     $stmt = $conn->prepare('INSERT INTO Membri_Band (id_band, id_artista, ruolo)
        //                             VALUES (?, ?, ?)');
        //     $stmt->bind_param('sss', $valuedId, $idArtista, $membro["ruolo"]);
        //     if (!$stmt->execute()) {
        //         echo 'Errore durante la registrazione. Riprova.';
        //         exit();
        //     }
        // }
        echo json_encode(['success' => true, 'message' => 'Aggiornamento completato con successo.']);
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Errore durante la modifica dell\'account, non possono esservi valori nulli.']);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_locale' && $permission) {
    $nome_locale = isset($_POST['nome_locale']) ? $_POST['nome_locale'] : '';
    $indirizzo = isset($_POST['indirizzo']) ? $_POST['indirizzo'] : '';
    $idLocale = isset($_POST['idLocale']) ? $_POST['idLocale'] : '';

    if (!empty($nome_locale) && !empty($indirizzo) && !empty($idLocale)) {
        $stmt = $conn->prepare('UPDATE `locale` SET `nome_locale`=?,`indirizzo`=?
                                WHERE `id_utente` = ? && `id`=?');
        $stmt->bind_param('ssii', $nome_locale, $indirizzo, $userId, $idLocale);
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Errore durante la modifica dell\'account.']);
            $stmt->close();
            exit();
        }

        echo json_encode(['success' => true, 'message' => 'Aggiornamento completato con successo.']);
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => empty($nome_locale) . '-' . empty($indirizzo) . "-" . empty($idLocale) . "-" . 'Errore durante la modifica dell\'account, non possono esservi valori nulli.']);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_artista' && $permission) {
    $nome_artista = isset($_POST['nome_artista']) ? $_POST['nome_artista'] : '';
    $tipo = isset($_POST['tipo']) ? $_POST['tipo'] : '';
    $genere = isset($_POST['genere']) ? $_POST['genere'] : '';
    $idArtista = isset($_POST['idArtista']) ? $_POST['idArtista'] : '';

    if (!empty($nome_artista) && !empty($tipo) && !empty($genere) && !empty($idArtista)) {
        $stmt = $conn->prepare('UPDATE `profili_artista` SET `nome_arte`=?,`tipo_artista`=?, `genere_musicale`=?
                                WHERE `id_utente` = ? && `id`=?');
        $stmt->bind_param('sssii', $nome_artista, $tipo, $genere, $userId, $idArtista);
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Errore durante la modifica dell\'account.']);
            $stmt->close();
            exit();
        }

        echo json_encode(['success' => true, 'message' => 'Aggiornamento completato con successo.']);
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Errore durante la modifica dell\'account, non possono esservi valori nulli.']);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_locale' && $permission) {
    $idLocale = isset($_POST['idLocale']) ? $_POST['idLocale'] : '';

    if (!empty($idLocale)) {
        // 1. Elimina le candidature collegate agli eventi del locale
        $stmt = $conn->prepare('DELETE Candidature
                                FROM Candidature
                                JOIN Evento ON Candidature.id_evento = Evento.id
                                WHERE Evento.id_locale = ?');
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Errore durante la preparazione della query: ' . $conn->error]);
            exit();
        }
        $stmt->bind_param('i', $idLocale);
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Errore durante l\'eliminazione dell\'account e delle relative dipendenze: ' . $e->getMessage()]);
            $stmt->close();
            exit();
        }
        $stmt->close();

        // 2. Elimina gli eventi collegati al locale
        $stmt = $conn->prepare('DELETE FROM Evento WHERE id_locale = ?');
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Errore durante la preparazione della query: ' . $conn->error]);
            exit();
        }
        $stmt->bind_param('i', $idLocale);
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Errore durante l\'eliminazione dell\'account e delle relative dipendenze: ' . $e->getMessage()]);
            $stmt->close();
            exit();
        }
        $stmt->close();

        // 3. Elimina il locale
        $stmt = $conn->prepare('DELETE FROM Locale WHERE id = ?');
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Errore durante la preparazione della query: ' . $conn->error]);
            exit();
        }
        $stmt->bind_param('i', $idLocale);
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Errore durante l\'eliminazione dell\'account e delle relative dipendenze: ' . $e->getMessage()]);
            $stmt->close();
            exit();
        }
        $stmt->close();

        // Se tutto è andato a buon fine, conferma la transazione
        echo json_encode(['success' => true, 'message' => 'Eliminazione completata con successo.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Errore durante la modifica dell\'account, non possono esservi valori nulli.']);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_artista' && $permission) {
    $idArtista = isset($_POST['idArtista']) ? $_POST['idArtista'] : '';

    if (!empty($idArtista)) {
        // 1. Elimina le candidature legate all'artista
        $stmt = $conn->prepare('DELETE FROM Candidature WHERE id_artista = ?');
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Errore durante la preparazione della query: ' . $conn->error]);
            exit();
        }
        $stmt->bind_param('i', $idArtista);
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Errore durante l\'eliminazione dell\'account e delle relative dipendenze.']);
            $stmt->close();
            exit();
        }
        $stmt->close();

        //scarico gli id degli artisti della band
        $stmt2 = $conn->prepare('SELECT id_artista 
                                    FROM Membri_Band
                                    WHERE id_band = ?');
        $stmt2->bind_param('i', $idArtista);
        $stmt2->execute();
        $result = $stmt2->get_result();

        // 2. Elimina i membri della band associati all'artista
        $stmt = $conn->prepare('DELETE FROM Membri_Band WHERE id_band = ?');
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Errore durante la preparazione della query: ' . $conn->error]);
            exit();
        }
        $stmt->bind_param('i', $idArtista);
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Errore durante l\'eliminazione dell\'account e delle relative dipendenze.']);
            $stmt->close();
            exit();
        }
        $stmt->close();

        // 3. Se l'artista è una band, elimina anche i profili degli altri membri della band
        while ($row = $result->fetch_assoc()) {
            $stmt = $conn->prepare('DELETE FROM Profili_Artista
                                    WHERE id = ?');
            if (!$stmt) {
                echo json_encode(['success' => false, 'message' => 'Errore durante la preparazione della query: ' . $conn->error]);
                exit();
            }
            $stmt->bind_param('i', $row['id_artista']);
            if (!$stmt->execute()) {
                echo json_encode(['success' => false, 'message' => 'Errore durante l\'eliminazione dell\'artista e delle relative dipendenze.']);
                $stmt->close();
                exit();
            }
            $stmt->close();
        }
        $stmt2->close();
        /*$stmt = $conn->prepare('DELETE FROM Profili_Artista
                                WHERE id IN (
                                    SELECT id_artista 
                                    FROM Membri_Band
                                    WHERE id_band = ?
                                )');
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Errore durante la preparazione della query: ' . $conn->error]);
            exit();
        }
        $stmt->bind_param('i', $idArtista);
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Errore durante l\'eliminazione dell\'account e delle relative dipendenze2.']);
            $stmt->close();
            exit();
        }
        $stmt->close();*/

        // 4. Elimina i profili artista dell'artista
        $stmt = $conn->prepare('DELETE FROM Profili_Artista WHERE id = ?');
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Errore durante la preparazione della query: ' . $conn->error]);
            exit();
        }
        $stmt->bind_param('i', $idArtista);
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Errore durante l\'eliminazione dell\'account e delle relative dipendenze.']);
            $stmt->close();
            exit();
        }
        $stmt->close();

        // Se tutto è andato a buon fine, conferma la transazione
        echo json_encode(['success' => true, 'message' => 'Eliminazione completata con successo.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Errore durante la modifica dell\'account, non possono esservi valori nulli.']);
    }
} else
    echo json_encode(['success' => false, 'message' => 'Errore globale.']);
$conn->close();
?>