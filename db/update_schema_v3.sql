-- Database schema updates for CampusLife v3
-- This script adds support for enhanced assignment and group submission features

-- First, fix any incomplete statements from previous updates
UPDATE afspraken SET status = 'gepland' WHERE status = 'gepland',n    notities';

-- Create table for assignment group submissions
CREATE TABLE IF NOT EXISTS opdracht_groep_indieningen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    opdracht_id INT NOT NULL,
    groep_id INT NOT NULL,
    opmerking TEXT,
    status ENUM('concept', 'ingeleverd', 'in_afwachting', 'geaccepteerd', 'geweigerd') DEFAULT 'concept',
    ingeleverd_op DATETIME,
    aangemaakt_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    gewijzigd_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (opdracht_id) REFERENCES opdrachten(id) ON DELETE CASCADE,
    FOREIGN KEY (groep_id) REFERENCES groepen(id) ON DELETE CASCADE,
    UNIQUE KEY unique_groep_submission (opdracht_id, groep_id)
);

-- Add group submission reference to individual submissions
ALTER TABLE opdracht_indieningen
ADD COLUMN groep_indiening_id INT,
ADD FOREIGN KEY (groep_indiening_id) REFERENCES opdracht_groep_indieningen(id) ON DELETE SET NULL;

-- Create table for group submission files
CREATE TABLE IF NOT EXISTS groep_inlevering_bestanden (
    id INT AUTO_INCREMENT PRIMARY KEY,
    groep_indiening_id INT NOT NULL,
    bestandsnaam VARCHAR(255) NOT NULL,
    bestandstype VARCHAR(100) NOT NULL,
    bestandsgrootte INT NOT NULL,
    bestandspad VARCHAR(512) NOT NULL,
    geupload_door INT NOT NULL,
    geupload_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (groep_indiening_id) REFERENCES opdracht_groep_indieningen(id) ON DELETE CASCADE,
    FOREIGN KEY (geupload_door) REFERENCES gebruikers(id) ON DELETE CASCADE
);

-- Create table for group submission comments
CREATE TABLE IF NOT EXISTS groep_inlevering_reacties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    groep_indiening_id INT NOT NULL,
    gebruiker_id INT NOT NULL,
    reactie TEXT NOT NULL,
    is_prive BOOLEAN DEFAULT FALSE,
    aangemaakt_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    gewijzigd_op TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (groep_indiening_id) REFERENCES opdracht_groep_indieningen(id) ON DELETE CASCADE,
    FOREIGN KEY (gebruiker_id) REFERENCES gebruikers(id) ON DELETE CASCADE
);

-- Create table for assignment extensions
CREATE TABLE IF NOT EXISTS opdracht_uitstel (
    id INT AUTO_INCREMENT PRIMARY KEY,
    opdracht_id INT NOT NULL,
    student_id INT NULL, -- NULL means extension applies to all students
    groep_id INT NULL,   -- NULL means not a group extension
    nieuwe_deadline DATETIME NOT NULL,
    reden TEXT,
    toegekend_door INT NOT NULL,
    toegekend_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('actief', 'ingetrokken') DEFAULT 'actief',
    FOREIGN KEY (opdracht_id) REFERENCES opdrachten(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES gebruikers(id) ON DELETE CASCADE,
    FOREIGN KEY (groep_id) REFERENCES groepen(id) ON DELETE CASCADE,
    FOREIGN KEY (toegekend_door) REFERENCES gebruikers(id) ON DELETE CASCADE,
    CONSTRAINT chk_extension_scope CHECK (
        (student_id IS NOT NULL AND groep_id IS NULL) OR
        (student_id IS NULL AND groep_id IS NOT NULL) OR
        (student_id IS NULL AND groep_id IS NULL)
    )
);

-- Create table for assignment submission status history
CREATE TABLE IF NOT EXISTS opdracht_status_historie (
    id INT AUTO_INCREMENT PRIMARY KEY,
    opdracht_id INT NOT NULL,
    status VARCHAR(50) NOT NULL,
    opmerking TEXT,
    gewijzigd_door INT NOT NULL,
    gewijzigd_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (opdracht_id) REFERENCES opdrachten(id) ON DELETE CASCADE,
    FOREIGN KEY (gewijzigd_door) REFERENCES gebruikers(id) ON DELETE CASCADE
);

-- Add submission requirements to assignments
ALTER TABLE opdrachten
ADD COLUMN minimale_vereisten TEXT,
ADD COLUMN beoordelingscriteria TEXT,
ADD COLUMN groepsopdracht BOOLEAN DEFAULT FALSE,
ADD COLUMN max_groepsgrootte INT DEFAULT 5,
ADD COLUMN laat_inleveren_toegestaan BOOLEAN DEFAULT FALSE,
ADD COLUMN maximale_laat_dagen INT DEFAULT 0,
ADD COLUMN punten_aftrek_per_dag DECIMAL(5,2) DEFAULT 0.00,
ADD COLUMN beoordeling_zichtbaar BOOLEAN DEFAULT FALSE,
ADD COLUMN feedback_template TEXT;

-- Create table for submission drafts
CREATE TABLE IF NOT EXISTS opdracht_concepten (
    id INT AUTO_INCREMENT PRIMARY KEY,
    opdracht_id INT NOT NULL,
    student_id INT NOT NULL,
    inhoud LONGTEXT,
    laatste_bewerking TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (opdracht_id) REFERENCES opdrachten(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES gebruikers(id) ON DELETE CASCADE,
    UNIQUE KEY unique_draft (opdracht_id, student_id)
);

-- Create table for assignment peer reviews
CREATE TABLE IF NOT EXISTS peer_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inlevering_id INT NOT NULL,
    beoordelaar_id INT NOT NULL,
    beoordeelde_id INT NOT NULL,
    score DECIMAL(5,2) NOT NULL,
    opmerkingen TEXT,
    anoniem BOOLEAN DEFAULT TRUE,
    ingevuld_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inlevering_id) REFERENCES opdracht_indieningen(id) ON DELETE CASCADE,
    FOREIGN KEY (beoordelaar_id) REFERENCES gebruikers(id) ON DELETE CASCADE,
    FOREIGN KEY (beoordeelde_id) REFERENCES gebruikers(id) ON DELETE CASCADE,
    UNIQUE KEY unique_review (inlevering_id, beoordelaar_id, beoordeelde_id)
);

-- Create table for assignment statistics
CREATE TABLE IF NOT EXISTS opdracht_statistieken (
    id INT AUTO_INCREMENT PRIMARY KEY,
    opdracht_id INT NOT NULL,
    totaal_ingeleverd INT DEFAULT 0,
    gemiddelde_score DECIMAL(5,2) DEFAULT 0.00,
    hoogste_score DECIMAL(5,2) DEFAULT 0.00,
    laagste_score DECIMAL(5,2) DEFAULT 0.00,
    mediaan_score DECIMAL(5,2) DEFAULT 0.00,
    standaard_afwijking DECIMAL(10,4) DEFAULT 0.0000,
    bijgewerkt_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (opdracht_id) REFERENCES opdrachten(id) ON DELETE CASCADE,
    UNIQUE KEY unique_assignment_stats (opdracht_id)
);

-- Create triggers for assignment statistics
DELIMITER //
CREATE TRIGGER after_submission_insert
AFTER INSERT ON opdracht_indieningen
FOR EACH ROW
BEGIN
    DECLARE avg_score DECIMAL(5,2);
    DECLARE max_score DECIMAL(5,2);
    DECLARE min_score DECIMAL(5,2);
    DECLARE median_score DECIMAL(5,2);
    DECLARE std_dev DECIMAL(10,4);
    DECLARE total INT;
    
    -- Calculate statistics
    SELECT 
        COUNT(*),
        AVG(cijfer),
        MAX(cijfer),
        MIN(cijfer)
    INTO 
        total,
        avg_score,
        max_score,
        min_score
    FROM opdracht_indieningen
    WHERE opdracht_id = NEW.opdracht_id AND cijfer IS NOT NULL;
    
    -- Calculate median (simplified)
    SELECT AVG(cijfer) INTO median_score
    FROM (
        SELECT cijfer
        FROM opdracht_indieningen
        WHERE opdracht_id = NEW.opdracht_id AND cijfer IS NOT NULL
        ORDER BY cijfer
        LIMIT 2 - (SELECT COUNT(*) FROM opdracht_indieningen WHERE opdracht_id = NEW.opdracht_id AND cijfer IS NOT NULL) % 2
        OFFSET (SELECT (COUNT(*) - 1) / 2 FROM opdracht_indieningen WHERE opdracht_id = NEW.opdracht_id AND cijfer IS NOT NULL)
    ) AS t;
    
    -- Calculate standard deviation
    SELECT IFNULL(STDDEV(cijfer), 0) INTO std_dev
    FROM opdracht_indieningen
    WHERE opdracht_id = NEW.opdracht_id AND cijfer IS NOT NULL;
    
    -- Update or insert statistics
    INSERT INTO opdracht_statistieken (
        opdracht_id,
        totaal_ingeleverd,
        gemiddelde_score,
        hoogste_score,
        laagste_score,
        mediaan_score,
        standaard_afwijking
    ) VALUES (
        NEW.opdracht_id,
        total,
        IFNULL(avg_score, 0),
        IFNULL(max_score, 0),
        IFNULL(min_score, 0),
        IFNULL(median_score, 0),
        IFNULL(std_dev, 0)
    )
    ON DUPLICATE KEY UPDATE
        totaal_ingeleverd = VALUES(totaal_ingeleverd),
        gemiddelde_score = VALUES(gemiddelde_score),
        hoogste_score = VALUES(hoogste_score),
        laagste_score = VALUES(laagste_score),
        mediaan_score = VALUES(mediaan_score),
        standaard_afwijking = VALUES(standaard_afwijking);
END//

-- Similar triggers for UPDATE and DELETE would be needed in a real implementation
DELIMITER ;

-- Add indexes for better performance
CREATE INDEX idx_opdracht_indieningen_opdracht_id ON opdracht_indieningen(opdracht_id);
CREATE INDEX idx_opdracht_indieningen_student_id ON opdracht_indieningen(student_id);
CREATE INDEX idx_opdracht_groep_indieningen_opdracht_id ON opdracht_groep_indieningen(opdracht_id);
CREATE INDEX idx_opdracht_groep_indieningen_groep_id ON opdracht_groep_indieningen(groep_id);
CREATE INDEX idx_inlevering_bestanden_inlevering_id ON inlevering_bestanden(inlevering_id);
CREATE INDEX idx_groep_inlevering_bestanden_groep_indiening_id ON groep_inlevering_bestanden(groep_indiening_id);

-- Update database version
INSERT INTO database_versies (versie, uitgevoerd_op, opmerkingen)
VALUES ('1.3.0', NOW(), 'Toegevoegd: Groepsopdrachten, uitbreidingen, peer reviews en statistieken');
