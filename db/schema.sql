-- Eenvoudige database structuur voor CampusLife

-- Gebruikers tabel
CREATE TABLE gebruikers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    gebruikersnaam VARCHAR(50) UNIQUE NOT NULL,
    wachtwoord VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    voornaam VARCHAR(50) NOT NULL,
    achternaam VARCHAR(50) NOT NULL,
    rol ENUM('student', 'docent', 'coach', 'organisator', 'beheerder') NOT NULL,
    laatste_login DATETIME,
    aangemaakt_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Vakken
CREATE TABLE vakken (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL,
    naam VARCHAR(100) NOT NULL,
    beschrijving TEXT,
    docent_id INT,
    FOREIGN KEY (docent_id) REFERENCES gebruikers(id)
);

-- Rooster
CREATE TABLE rooster (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vak_id INT NOT NULL,
    dag_van_de_week TINYINT NOT NULL, -- 1=maandag, 7=zondag
    start_tijd TIME NOT NULL,
    eind_tijd TIME NOT NULL,
    lokaal VARCHAR(20),
    is_verplaatst BOOLEAN DEFAULT FALSE,
    nieuwe_tijd DATETIME,
    FOREIGN KEY (vak_id) REFERENCES vakken(id)
);

-- Opdrachten
CREATE TABLE opdrachten (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vak_id INT NOT NULL,
    titel VARCHAR(255) NOT NULL,
    beschrijving TEXT,
    deadline DATETIME NOT NULL,
    max_cijfer DECIMAL(5,2),
    aangemaakt_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vak_id) REFERENCES vakken(id)
);

-- Inleveringen
CREATE TABLE inleveringen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    opdracht_id INT NOT NULL,
    student_id INT NOT NULL,
    bestandspad VARCHAR(255) NOT NULL,
    ingeleverd_op DATETIME NOT NULL,
    cijfer DECIMAL(5,2),
    feedback TEXT,
    nagekeken_door INT,
    nagekeken_op DATETIME,
    FOREIGN KEY (opdracht_id) REFERENCES opdrachten(id),
    FOREIGN KEY (student_id) REFERENCES gebruikers(id),
    FOREIGN KEY (nagekeken_door) REFERENCES gebruikers(id)
);

-- Cijfers
CREATE TABLE cijfers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    vak_id INT NOT NULL,
    opdracht_id INT,
    cijfer DECIMAL(5,2) NOT NULL,
    opmerking TEXT,
    toegekend_door INT NOT NULL,
    toegekend_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES gebruikers(id),
    FOREIGN KEY (vak_id) REFERENCES vakken(id),
    FOREIGN KEY (opdracht_id) REFERENCES opdrachten(id) ON DELETE SET NULL,
    FOREIGN KEY (toegekend_door) REFERENCES gebruikers(id)
);

-- Evenementen
CREATE TABLE evenementen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titel VARCHAR(255) NOT NULL,
    beschrijving TEXT,
    start_tijd DATETIME NOT NULL,
    eind_tijd DATETIME NOT NULL,
    locatie VARCHAR(255),
    max_deelnemers INT,
    aangemaakt_door INT NOT NULL,
    aangemaakt_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (aangemaakt_door) REFERENCES gebruikers(id)
);

-- Inschrijvingen evenementen
CREATE TABLE evenement_inschrijvingen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evenement_id INT NOT NULL,
    gebruiker_id INT NOT NULL,
    ingeschreven_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (evenement_id) REFERENCES evenementen(id) ON DELETE CASCADE,
    FOREIGN KEY (gebruiker_id) REFERENCES gebruikers(id)
);

-- Berichten
CREATE TABLE berichten (
    id INT AUTO_INCREMENT PRIMARY KEY,
    afzender_id INT NOT NULL,
    ontvanger_id INT,
    groep_id INT,
    onderwerp VARCHAR(255),
    bericht TEXT NOT NULL,
    is_gelezen BOOLEAN DEFAULT FALSE,
    verzonden_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (afzender_id) REFERENCES gebruikers(id),
    FOREIGN KEY (ontvanger_id) REFERENCES gebruikers(id) ON DELETE SET NULL
);

-- Groepen
CREATE TABLE groepen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    naam VARCHAR(100) NOT NULL,
    beschrijving TEXT,
    vak_id INT,
    aangemaakt_door INT NOT NULL,
    aangemaakt_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vak_id) REFERENCES vakken(id) ON DELETE SET NULL,
    FOREIGN KEY (aangemaakt_door) REFERENCES gebruikers(id)
);

-- Groepsleden
CREATE TABLE groepsleden (
    id INT AUTO_INCREMENT PRIMARY KEY,
    groep_id INT NOT NULL,
    gebruiker_id INT NOT NULL,
    is_begeleider BOOLEAN DEFAULT FALSE,
    toegevoegd_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (groep_id) REFERENCES groepen(id) ON DELETE CASCADE,
    FOREIGN KEY (gebruiker_id) REFERENCES gebruikers(id) ON DELETE CASCADE
);

-- Aanwezigheid
CREATE TABLE aanwezigheid (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rooster_id INT NOT NULL,
    student_id INT NOT NULL,
    status ENUM('aanwezig', 'afwezig', 'te_laat', 'geoorloofd') NOT NULL,
    opmerkingen TEXT,
    geregistreerd_door INT,
    geregistreerd_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rooster_id) REFERENCES rooster(id),
    FOREIGN KEY (student_id) REFERENCES gebruikers(id),
    FOREIGN KEY (geregistreerd_door) REFERENCES gebruikers(id)
);

-- Afspraken met coach
CREATE TABLE afspraken (
    id INT AUTO_INCREMENT PRIMARY KEY,
    coach_id INT NOT NULL,
    student_id INT NOT NULL,
    titel VARCHAR(255) NOT NULL,
    beschrijving TEXT,
    start_tijd DATETIME NOT NULL,
    eind_tijd DATETIME NOT NULL,
    status ENUM('gepland', 'afgerond', 'geannuleerd') DEFAULT 'gepland',n    notities TEXT,
    aangemaakt_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (coach_id) REFERENCES gebruikers(id),
    FOREIGN KEY (student_id) REFERENCES gebruikers(id)
);

-- Doelen
CREATE TABLE doelen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    titel VARCHAR(255) NOT NULL,
    beschrijving TEXT,
    doel_datum DATE,
    status ENUM('niet_begonnen', 'bezig', 'voltooid', 'uitgesteld') DEFAULT 'niet_begonnen',
    aangemaakt_door INT NOT NULL,
    aangemaakt_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES gebruikers(id),
    FOREIGN KEY (aangemaakt_door) REFERENCES gebruikers(id)
);

-- Standaard admin gebruiker (wachtwoord: wachtwoord123)
INSERT INTO gebruikers (gebruikersnaam, wachtwoord, email, voornaam, achternaam, rol) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@campuslife.nl', 'Admin', 'Gebruiker', 'beheerder')
ON DUPLICATE KEY UPDATE aangemaakt_op = CURRENT_TIMESTAMP;
