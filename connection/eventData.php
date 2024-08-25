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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_event' && $permission) {
    $nome_locale = isset($_POST['nome_locale']) ? $_POST['nome_locale'] : '';
    $indirizzo = isset($_POST['indirizzo']) ? $_POST['indirizzo'] : '';
    $nome_evento = isset($_POST['nome_evento']) ? $_POST['nome_evento'] : '';
    $ora_inizio = isset($_POST['orario_inizio']) ? $_POST['orario_inizio'] : '';
    $ora_fine = isset($_POST['orario_fine']) ? $_POST['orario_fine'] : '';
    $data_evento = isset($_POST['data_evento']) ? $_POST['data_evento'] : '';
    $genere = isset($_POST['genere']) ? $_POST['genere'] : '';
    $descrizione = isset($_POST['descrizione']) ? $_POST['descrizione'] : '';
    $lat = isset($_POST['lat']) ? (float) $_POST['lat'] : null; // Converti a float
    $lon = isset($_POST['lon']) ? (float) $_POST['lon'] : null; // Converti a float

    if (!empty($nome_locale) && !empty($indirizzo) && !empty($nome_evento) && !empty($ora_inizio) && !empty($ora_fine) && !empty($data_evento) && !empty($genere) && !empty($descrizione) && !empty($lat) && !empty($lon)) {
        //verifico se l'utente il locale inserito nel form
        $stmt = $conn->prepare('SELECT `id` FROM `locale` WHERE id_utente = ? && nome_locale = ? && indirizzo = ?');
        $stmt->bind_param('iss', $userId, $nome_locale, $indirizzo);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows > 0) {
            // Inserimento dei dati nel database
            $result = $result->fetch_assoc();
            $idLocale = $result['id'];

            $stmt = $conn->prepare('INSERT INTO evento (id_locale, posizione, nome_evento, data_evento, ora_inizio, ora_fine, genere_musicale, descrizione)
                                    VALUES (?, POINT(?, ?), ?, ?, ?, ?, ?, ?)');

            $stmt->bind_param('iddssssss', $idLocale, $lat, $lon, $nome_evento, $data_evento, $ora_inizio, $ora_fine, $genere, $descrizione);

            if (!$stmt->execute()) {
                echo json_encode(['success' => false, 'message' => 'Errore durante la registrazione, riprovare più tardi.']);
            }
            echo json_encode(['success' => true, 'message' => 'Inserimento completato con successo.']);
            $stmt->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'Errore durante la creazione dell\'evento, l\'utente non ha registrato il locale "' . $nome_locale . '" in via ' . $indirizzo . '.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => ' - ' . $nome_locale . ' - ' . $indirizzo . ' - ' . $nome_evento . ' - ' . $data_evento . ' - ' . $ora_inizio . ' - ' . $ora_fine . ' - ']);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_userEvent' && $permission) {
    // Array per memorizzare i dati da inviare al client
    $dati = [];
    $datiArtisti = [];
    $datilocali = [];

    // 1. Estrazione dei locali dell'utente
    $stmt = $conn->prepare('SELECT * FROM locale WHERE id_utente = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $locali_result = $stmt->get_result();

    if ($locali_result->num_rows > 0) {
        while ($locale = $locali_result->fetch_assoc()) {
            // Per ogni locale, estrai gli eventi associati
            $localeId = $locale['id'];
            $stmt2 = $conn->prepare('SELECT id, id_locale, ST_X(posizione) AS lat, ST_Y(posizione) AS lon, nome_evento, data_evento, ora_inizio, ora_fine, genere_musicale, descrizione 
                                    FROM evento WHERE id_locale = ?');
            $stmt2->bind_param('i', $localeId);
            $stmt2->execute();
            $eventi_result = $stmt2->get_result();

            $eventi = [];
            if ($eventi_result->num_rows > 0) {
                while ($evento = $eventi_result->fetch_assoc()) {
                    $eventi[] = $evento;
                }
            }

            // Aggiungi gli eventi al locale corrente
            $locale['eventi'] = $eventi;

            // Aggiungi il locale con i suoi eventi all'array principale
            $datiLocali[] = $locale;

            $stmt2->close();
        }
    }
    $stmt->close();
    //echo json_encode(['success' => true, 'data' => $dati]);

    // 2. Estrazione degli artisti dell'utente
    $stmt = $conn->prepare('SELECT * FROM profili_artista WHERE id_utente = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $artisti_result = $stmt->get_result();

    if ($artisti_result->num_rows > 0) {
        while ($artista = $artisti_result->fetch_assoc()) {
            // Per ogni artista, estrai gli eventi associati
            $artistaId = $artista['id'];
            $stmt2 = $conn->prepare('SELECT c.id AS id, 
                                            e.id_locale AS id_locale, 
                                            ST_X(e.posizione) AS lat, 
                                            ST_Y(e.posizione) AS lon, 
                                            e.nome_evento AS nome_evento, 
                                            e.data_evento AS data_evento, 
                                            e.ora_inizio AS ora_inizio, 
                                            e.ora_fine AS ora_fine, 
                                            e.genere_musicale AS genere_musicale, 
                                            e.descrizione AS descrizione, 
                                            p.nome_arte AS nome_artista, 
                                            l.nome_locale AS nome_locale,
                                            c.stato AS stato
                                    FROM `candidature` c 
                                    JOIN profili_artista p ON c.id_artista=p.id 
                                    JOIN evento e ON c.id_evento=e.id 
                                    JOIN locale l ON e.id_locale=l.id 
                                    WHERE c.id_artista= ?;
                                    ');
            $stmt2->bind_param('i', $artistaId);
            $stmt2->execute();
            $eventi_result = $stmt2->get_result();

            $eventi = [];
            if ($eventi_result->num_rows > 0) {
                while ($evento = $eventi_result->fetch_assoc()) {
                    $eventi[] = $evento;
                }
            }

            // Aggiungi gli eventi al locale corrente
            $artista['eventi'] = $eventi;

            // Aggiungi il locale con i suoi eventi all'array principale
            $datiAertisti[] = $artista;

            $stmt2->close();
        }
    }
    $stmt->close();

    $dati = [
        "locali" => $datiLocali,
        "artisti" => $datiAertisti
    ];

    echo json_encode(['success' => true, 'data' => $dati]);
} else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_event' && $permission) {
    $idEvento = isset($_POST['idEvento']) ? $_POST['idEvento'] : '';

    if (!empty($idEvento)) {
        // 1. Elimina le candidature collegate all'evento
        $stmt = $conn->prepare('DELETE FROM Candidature WHERE id_evento = ?');
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Errore durante la preparazione della query: ' . $conn->error]);
            exit();
        }
        $stmt->bind_param('i', $idEvento);
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Errore durante l\'eliminazione dell\'account e delle relative dipendenze.']);
            $stmt->close();
            exit();
        }
        $stmt->close();

        // 2. Elimina l'evento stesso
        $stmt = $conn->prepare('DELETE FROM Evento WHERE id = ?');
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Errore durante la preparazione della query: ' . $conn->error]);
            exit();
        }
        $stmt->bind_param('i', $idEvento);
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
} else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_event' && $permission) {
    $nome_evento = isset($_POST['nome_evento']) ? $_POST['nome_evento'] : '';
    $ora_inizio = isset($_POST['ora_inizio']) ? $_POST['ora_inizio'] : '';
    $ora_fine = isset($_POST['ora_fine']) ? $_POST['ora_fine'] : '';
    $data = isset($_POST['data']) ? $_POST['data'] : '';
    $genere = isset($_POST['genere']) ? $_POST['genere'] : '';
    $descrizione = isset($_POST['descrizione']) ? $_POST['descrizione'] : '';

    $idEvento = isset($_POST['idEvento']) ? $_POST['idEvento'] : '';

    if (!empty($nome_evento) && !empty($ora_inizio) && !empty($ora_fine) && !empty($data) && !empty($genere) && !empty($descrizione)) {
        $stmt = $conn->prepare('UPDATE `evento` SET `nome_evento` = ?, `ora_inizio` = ?, `ora_fine` = ? , `data_evento` = ?, `genere_musicale` = ?, `descrizione` = ?
                                WHERE `id` = ?');
        $stmt->bind_param('ssssssi', $nome_evento, $ora_inizio, $ora_fine, $data, $genere, $descrizione, $idEvento);
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
        echo json_encode(['success' => false, 'message' => 'Errore']);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_allEvent') {
    $stmt2 = $conn->prepare('SELECT e.id, id_locale, ST_X(posizione) AS lat, ST_Y(posizione) AS lon, nome_evento, data_evento, ora_inizio, ora_fine, genere_musicale, descrizione, nome_locale 
                                    FROM evento e JOIN locale l on e.id_locale=l.id');
    // $stmt2->bind_param('i', $localeId);
    if (!$stmt2->execute()) {
        echo json_encode(['success' => false, 'message' => 'Errore.']);
        exit();
    }
    $eventi_result = $stmt2->get_result();

    $eventi = [];
    if ($eventi_result->num_rows > 0) {
        while ($evento = $eventi_result->fetch_assoc()) {
            $eventi[] = $evento;
        }
    }

    $stmt2->close();

    echo json_encode(['success' => true, 'data' => $eventi]);
} else if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_artist' && $permission) {
    $stmt2 = $conn->prepare('SELECT * FROM profili_artista WHERE id_utente = ?');
    $stmt2->bind_param('i', $userId);
    if (!$stmt2->execute()) {
        echo json_encode(['success' => false, 'message' => 'Errore.']);
        exit();
    }
    $result = $stmt2->get_result();

    $artisti = [];
    if ($result->num_rows > 0) {
        while ($artista = $result->fetch_assoc()) {
            $artisti[] = $artista;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Errore.']);
        exit();
    }

    $stmt2->close();

    echo json_encode(['success' => true, 'data' => $artisti]);
} else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_candidature' && $permission) {
    $idArtista = isset($_POST['idArtista']) ? $_POST['idArtista'] : '';
    $idEvento = isset($_POST['idEvento']) ? $_POST['idEvento'] : '';
    $stato = "In attesa";

    if (!empty($idArtista) && !empty($idEvento)) {
        $stmt = $conn->prepare('INSERT INTO candidature (id_artista, id_evento, stato)
                                    VALUES (?, ?, ?)');
        $stmt->bind_param('iis', $idArtista, $idEvento, $stato);

        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Errore durante la candidatura dell\'artista.']);
            exit();
        }

        echo json_encode(['success' => true, 'message' => 'candidatura completato con successo.']);
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Errore campi vuoti']);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_cand' && $permission) {
    $idCand = isset($_POST['idCand']) ? $_POST['idCand'] : '';

    if (!empty($idCand)) {
        // 1. Elimina le candidature collegate all'evento
        $stmt = $conn->prepare('DELETE FROM Candidature WHERE id = ?');
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Errore durante la preparazione della query: ' . $conn->error]);
            exit();
        }
        $stmt->bind_param('i', $idCand);
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Errore durante l\'eliminazione della candidatura e delle relative dipendenze.']);
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