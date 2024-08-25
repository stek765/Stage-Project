CREATE TABLE PERMESSI (
    IDPermesso INT AUTO_INCREMENT PRIMARY KEY,
    Nome VARCHAR(50) NOT NULL
);

CREATE TABLE RUOLI (
    IDRuolo INT AUTO_INCREMENT PRIMARY KEY,
    Nome VARCHAR(50) NOT NULL
);

CREATE TABLE USER (
    IDUser INT AUTO_INCREMENT PRIMARY KEY,
    Username VARCHAR(50),
    Password VARCHAR(255),
    Nome VARCHAR(50),
    Cognome VARCHAR(50),
    Nazionalita VARCHAR(50),
    Localita VARCHAR(50),
    ExpireDate DATE
);

CREATE TABLE ARTISTA (
    IDArtista INT AUTO_INCREMENT PRIMARY KEY,
    Nome VARCHAR(50),
    Cognome VARCHAR(50),
    Email VARCHAR(100)
);

CREATE TABLE GENERE (
    IDGenere INT AUTO_INCREMENT PRIMARY KEY,
    Nome VARCHAR(50)
);

CREATE TABLE MUSICISTA (
    IDMusicista INT AUTO_INCREMENT PRIMARY KEY,
    Nome VARCHAR(50),
    Cognome VARCHAR(50),
    Strumento VARCHAR(50)
);

CREATE TABLE BAND (
    IDBand INT AUTO_INCREMENT PRIMARY KEY,
    NomeBand VARCHAR(50)
);

CREATE TABLE LOCALE (
    IDLocale INT AUTO_INCREMENT PRIMARY KEY,
    NomeLocale VARCHAR(50),
    Indirizzo VARCHAR(100),
    Email VARCHAR(100)
);

CREATE TABLE EVENTO (
    IDEvento INT AUTO_INCREMENT PRIMARY KEY,
    TipoEvento VARCHAR(50),
    ST_POINT POINT,
    DataOra TIMESTAMP,
    IDLocale INT,
    FOREIGN KEY (IDLocale) REFERENCES LOCALE(IDLocale)
);

CREATE TABLE TURISTA (
    IDTurista INT AUTO_INCREMENT PRIMARY KEY,
    Nome VARCHAR(50),
    Cognome VARCHAR(50),
    Email VARCHAR(100)
);

CREATE TABLE User_Ruoli (
    IDUser INT,
    IDRuolo INT,
    PRIMARY KEY (IDUser, IDRuolo),
    FOREIGN KEY (IDUser) REFERENCES USER(IDUser),
    FOREIGN KEY (IDRuolo) REFERENCES RUOLI(IDRuolo)
);

CREATE TABLE Ruolo_Permessi (
    IDRuolo INT,
    IDPermesso INT,
    PRIMARY KEY (IDRuolo, IDPermesso),
    FOREIGN KEY (IDRuolo) REFERENCES RUOLI(IDRuolo),
    FOREIGN KEY (IDPermesso) REFERENCES PERMESSI(IDPermesso)
);

CREATE TABLE Artista_Genere (
    IDArtista INT,
    IDGenere INT,
    PRIMARY KEY (IDArtista, IDGenere),
    FOREIGN KEY (IDArtista) REFERENCES ARTISTA(IDArtista),
    FOREIGN KEY (IDGenere) REFERENCES GENERE(IDGenere)
);

CREATE TABLE Evento_Artista (
    IDEvento INT,
    IDArtista INT,
    PRIMARY KEY (IDEvento, IDArtista),
    FOREIGN KEY (IDEvento) REFERENCES EVENTO(IDEvento),
    FOREIGN KEY (IDArtista) REFERENCES ARTISTA(IDArtista)
);

CREATE TABLE Band_Musicista (
    IDBand INT,
    IDMusicista INT,
    PRIMARY KEY (IDBand, IDMusicista),
    FOREIGN KEY (IDBand) REFERENCES BAND(IDBand),
    FOREIGN KEY (IDMusicista) REFERENCES MUSICISTA(IDMusicista)
);

CREATE TABLE Prenotazione (
    IDTurista INT,
    IDEvento INT,
    PRIMARY KEY (IDTurista, IDEvento),
    FOREIGN KEY (IDTurista) REFERENCES TURISTA(IDTurista),
    FOREIGN KEY (IDEvento) REFERENCES EVENTO(IDEvento)
);
