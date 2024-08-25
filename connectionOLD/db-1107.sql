CREATE TABLE IF NOT EXISTS Utente (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome_completo VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nazionalità VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS Locale (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_utente INT,
    nome_locale VARCHAR(100) NOT NULL,
    indirizzo VARCHAR(255) NOT NULL,
    FOREIGN KEY (id_utente) REFERENCES Utente(id)
);

CREATE TABLE IF NOT EXISTS Profili_Artista (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_utente INT,
    tipo_artista ENUM('Band', 'Musicista') NOT NULL,
    nome_arte VARCHAR(100) NOT NULL,
    genere_musicale VARCHAR(100),
    FOREIGN KEY (id_utente) REFERENCES Utente(id)
);

CREATE TABLE IF NOT EXISTS Membri_Band (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_band INT,
    id_artista INT,
    ruolo VARCHAR(100), -- es. cantante, chitarrista, batterista, etc.
    FOREIGN KEY (id_band) REFERENCES Profili_Artista(id),
    FOREIGN KEY (id_artista) REFERENCES Profili_Artista(id)
);

CREATE TABLE IF NOT EXISTS Newsletter (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) UNIQUE NOT NULL,
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- CREATE TABLE IF NOT EXISTS Evento (
--     id INT AUTO_INCREMENT PRIMARY KEY,
--     id_locale INT,
--     posizione GEOGRAPHY(POINT, 4326) NOT NULL, 
--     nome_evento VARCHAR(100) NOT NULL,
--     data_evento DATE NOT NULL,
--     ora_inizio TIME NOT NULL,
--     ora_fine TIME NOT NULL,
--     genere_musicale VARCHAR(100),
--     descrizione TEXT,
--     FOREIGN KEY (id_locale) REFERENCES Locale(id)
-- );  

CREATE TABLE IF NOT EXISTS Evento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_locale INT,
    posizione POINT NOT NULL, 
    nome_evento VARCHAR(100) NOT NULL,
    data_evento DATE NOT NULL,
    ora_inizio TIME NOT NULL,
    ora_fine TIME NOT NULL,
    genere_musicale VARCHAR(100),
    descrizione TEXT,
    FOREIGN KEY (id_locale) REFERENCES Locale(id),
    SPATIAL INDEX (posizione)
);

CREATE TABLE IF NOT EXISTS Candidature (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_artista INT,
    id_evento INT,
    stato ENUM('In attesa', 'Accettata', 'Rifiutata') DEFAULT 'In attesa', -- Stato della candidatura
    FOREIGN KEY (id_artista) REFERENCES Profili_Artista(id),
    FOREIGN KEY (id_evento) REFERENCES Evento(id)
);


/*

+-----------------+     +-------------------+     +-------------------+     +-------------------+     +-----------------+
|     Utente      |     |      Locale       |     |   Profili_Artista |     |     Newsletter     |     |     Evento      |
+-----------------+     +-------------------+     +-------------------+     +-------------------+     +-----------------+
| id (PK)         |     | id (PK)           |     | id (PK)           |     | id (PK)           |     | id (PK)         |
| nome_completo   |     | id_utente (FK)    |     | id_utente (FK)    |     | email (UNIQUE)    |     | id_locale (FK)  |
| email (UNIQUE)  |     | nome_locale       |     | tipo_artista      |     | subscribed_at     |     | posizione       |
| password        |     | indirizzo         |     | nome_arte         |     +-------------------+     | nome_evento     |
| nazionalità     |     +-------------------+     | genere_musicale   |                               | data_evento     |
| created_at      |                                +-------------------+                               | ora_inizio      |
+-----------------+                                                                                   | ora_fine        |
                                                                                                      | genere_musicale |
                                                                                                      | descrizione     |
                                                                                                      +-----------------+
+-------------------+          
|   Membri_Band     |         
+-------------------+        
| id (PK)           |       
| id_band (FK)      |
| id_artista (FK)   |          
| ruolo             |          
+-------------------+          

+-------------------+           
|    Candidature     |
+-------------------+
| id (PK)           |
| id_artista (FK)   |
| id_evento (FK)    |
| stato             |   
+-------------------+

*/



