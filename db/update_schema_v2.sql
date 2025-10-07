-- Database schema updates for CampusLife v2
-- This script adds support for enhanced assignment and submission features

-- First, fix any existing syntax errors in the schema
UPDATE afspraken SET status = 'gepland' WHERE status = 'gepland',n    notities';

-- Add course enrollments table
CREATE TABLE IF NOT EXISTS course_enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    student_id INT NOT NULL,
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES vakken(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES gebruikers(id) ON DELETE CASCADE,
    UNIQUE KEY unique_enrollment (course_id, student_id)
);

-- Add reset token fields to users table
ALTER TABLE gebruikers
ADD COLUMN reset_token VARCHAR(64) DEFAULT NULL,
ADD COLUMN reset_expires DATETIME DEFAULT NULL,
ADD COLUMN two_factor_secret VARCHAR(32) DEFAULT NULL,
ADD COLUMN two_factor_enabled BOOLEAN DEFAULT FALSE,
ADD COLUMN last_seen DATETIME DEFAULT NULL,
ADD COLUMN profile_image VARCHAR(255) DEFAULT NULL;

-- Enhance assignments table
ALTER TABLE opdrachten
ADD COLUMN max_cijfer DECIMAL(5,2) DEFAULT 10.00,
ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN created_by INT,
ADD FOREIGN KEY (created_by) REFERENCES gebruikers(id) ON DELETE SET NULL;

-- Create table for assignment attachments
CREATE TABLE IF NOT EXISTS bijlagen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    opdracht_id INT,
    inlevering_id INT,
    bestandsnaam VARCHAR(255) NOT NULL,
    bestandstype VARCHAR(100) NOT NULL,
    bestandsgrootte INT NOT NULL,
    bestandspad VARCHAR(512) NOT NULL,
    geupload_door INT NOT NULL,
    geupload_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (opdracht_id) REFERENCES opdrachten(id) ON DELETE CASCADE,
    FOREIGN KEY (inlevering_id) REFERENCES inleveringen(id) ON DELETE CASCADE,
    FOREIGN KEY (geupload_door) REFERENCES gebruikers(id) ON DELETE CASCADE
);

-- Enhance submissions table
ALTER TABLE inleveringen
RENAME COLUMN bestandspad TO opmerking;

ALTER TABLE inleveringen
ADD COLUMN laatst_aangepast TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
ADD COLUMN status ENUM('ingeleverd', 'in_afwachting', 'geaccepteerd', 'geweigerd') DEFAULT 'ingeleverd',
ADD COLUMN beoordelingsdatum DATETIME DEFAULT NULL,
ADD COLUMN beoordeeld_door INT,
ADD COLUMN feedback_datum DATETIME DEFAULT NULL,
ADD COLUMN feedback_gezien BOOLEAN DEFAULT FALSE,
ADD COLUMN feedback_gezien_datum DATETIME DEFAULT NULL,
MODIFY COLUMN cijfer DECIMAL(5,2) DEFAULT NULL,
ADD FOREIGN KEY (beoordeeld_door) REFERENCES gebruikers(id) ON DELETE SET NULL;

-- Create notifications table
CREATE TABLE IF NOT EXISTS notificaties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    gebruiker_id INT NOT NULL,
    titel VARCHAR(255) NOT NULL,
    bericht TEXT NOT NULL,
    type ENUM('info', 'waarschuwing', 'fout', 'succes') DEFAULT 'info',
    link VARCHAR(512) DEFAULT NULL,
    is_gelezen BOOLEAN DEFAULT FALSE,
    gelezen_op DATETIME DEFAULT NULL,
    aangemaakt_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (gebruiker_id) REFERENCES gebruikers(id) ON DELETE CASCADE
);

-- Create feedback table
CREATE TABLE IF NOT EXISTS feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inlevering_id INT NOT NULL,
    docent_id INT NOT NULL,
    inhoud TEXT NOT NULL,
    datum TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inlevering_id) REFERENCES inleveringen(id) ON DELETE CASCADE,
    FOREIGN KEY (docent_id) REFERENCES gebruikers(id) ON DELETE CASCADE
);

-- Create table for submission status history
CREATE TABLE IF NOT EXISTS inlevering_status_historie (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inlevering_id INT NOT NULL,
    status VARCHAR(50) NOT NULL,
    opmerking TEXT,
    gewijzigd_door INT NOT NULL,
    gewijzigd_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inlevering_id) REFERENCES inleveringen(id) ON DELETE CASCADE,
    FOREIGN KEY (gewijzigd_door) REFERENCES gebruikers(id) ON DELETE CASCADE
);

-- Create table for assignment categories
CREATE TABLE IF NOT EXISTS opdracht_categorieen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    naam VARCHAR(100) NOT NULL,
    beschrijving TEXT,
    vak_id INT,
    aangemaakt_door INT NOT NULL,
    aangemaakt_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vak_id) REFERENCES vakken(id) ON DELETE SET NULL,
    FOREIGN KEY (aangemaakt_door) REFERENCES gebruikers(id) ON DELETE CASCADE,
    UNIQUE KEY unique_categorie_naam (vak_id, naam)
);

-- Add category to assignments
ALTER TABLE opdrachten
ADD COLUMN categorie_id INT,
ADD FOREIGN KEY (categorie_id) REFERENCES opdracht_categorieen(id) ON DELETE SET NULL;

-- Create table for assignment submissions
CREATE TABLE IF NOT EXISTS opdracht_indieningen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    opdracht_id INT NOT NULL,
    student_id INT NOT NULL,
    opmerking TEXT,
    status ENUM('concept', 'ingeleverd', 'in_afwachting', 'geaccepteerd', 'geweigerd') DEFAULT 'concept',
    ingeleverd_op DATETIME,
    aangemaakt_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    gewijzigd_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (opdracht_id) REFERENCES opdrachten(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES gebruikers(id) ON DELETE CASCADE,
    UNIQUE KEY unique_submission (opdracht_id, student_id)
);

-- Create table for submission files
CREATE TABLE IF NOT EXISTS inlevering_bestanden (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inlevering_id INT NOT NULL,
    bestandsnaam VARCHAR(255) NOT NULL,
    bestandstype VARCHAR(100) NOT NULL,
    bestandsgrootte INT NOT NULL,
    bestandspad VARCHAR(512) NOT NULL,
    geupload_door INT NOT NULL,
    geupload_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inlevering_id) REFERENCES opdracht_indieningen(id) ON DELETE CASCADE,
    FOREIGN KEY (geupload_door) REFERENCES gebruikers(id) ON DELETE CASCADE
);

-- Create table for submission comments
CREATE TABLE IF NOT EXISTS inlevering_reacties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inlevering_id INT NOT NULL,
    gebruiker_id INT NOT NULL,
    reactie TEXT NOT NULL,
    is_prive BOOLEAN DEFAULT FALSE,
    aangemaakt_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    gewijzigd_op TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (inlevering_id) REFERENCES opdracht_indieningen(id) ON DELETE CASCADE,
    FOREIGN KEY (gebruiker_id) REFERENCES gebruikers(id) ON DELETE CASCADE
);

-- Create table for assignment templates
CREATE TABLE IF NOT EXISTS opdracht_sjablonen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titel VARCHAR(255) NOT NULL,
    beschrijving TEXT,
    instructies TEXT,
    beoordelingscriteria JSON,
    standaard_waarde DECIMAL(5,2) DEFAULT 10.00,
    categorie_id INT,
    aangemaakt_door INT NOT NULL,
    aangemaakt_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    gewijzigd_op TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (categorie_id) REFERENCES opdracht_categorieen(id) ON DELETE SET NULL,
    FOREIGN KEY (aangemaakt_door) REFERENCES gebruikers(id) ON DELETE CASCADE
);

-- Add template reference to assignments
ALTER TABLE opdrachten
ADD COLUMN sjabloon_id INT,
ADD FOREIGN KEY (sjabloon_id) REFERENCES opdracht_sjablonen(id) ON DELETE SET NULL;

-- Create table for assignment grading criteria
CREATE TABLE IF NOT EXISTS beoordelingscriteria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    opdracht_id INT NOT NULL,
    criterium VARCHAR(255) NOT NULL,
    omschrijving TEXT,
    gewicht DECIMAL(5,2) DEFAULT 1.00,
    max_score DECIMAL(5,2) DEFAULT 10.00,
    volgorde INT DEFAULT 0,
    FOREIGN KEY (opdracht_id) REFERENCES opdrachten(id) ON DELETE CASCADE
);

-- Create table for assignment rubric scores
CREATE TABLE IF NOT EXISTS beoordeling_scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inlevering_id INT NOT NULL,
    criterium_id INT NOT NULL,
    score DECIMAL(5,2) NOT NULL,
    opmerking TEXT,
    beoordeeld_door INT NOT NULL,
    beoordeeld_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inlevering_id) REFERENCES opdracht_indieningen(id) ON DELETE CASCADE,
    FOREIGN KEY (criterium_id) REFERENCES beoordelingscriteria(id) ON DELETE CASCADE,
    FOREIGN KEY (beoordeeld_door) REFERENCES gebruikers(id) ON DELETE CASCADE,
    UNIQUE KEY unique_criteria_score (inlevering_id, criterium_id)
);

-- Create table for assignment extensions
CREATE TABLE IF NOT EXISTS opdracht_uitstel (
    id INT AUTO_INCREMENT PRIMARY KEY,
    opdracht_id INT NOT NULL,
    student_id INT NOT NULL,
    nieuwe_deadline DATETIME NOT NULL,
    reden TEXT NOT NULL,
    status ENUM('aangevraagd', 'goedgekeurd', 'afgewezen') DEFAULT 'aangevraagd',
    beoordeeld_door INT,
    beoordelings_opmerking TEXT,
    beoordeeld_op DATETIME,
    aangevraagd_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (opdracht_id) REFERENCES opdrachten(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES gebruikers(id) ON DELETE CASCADE,
    FOREIGN KEY (beoordeeld_door) REFERENCES gebruikers(id) ON DELETE SET NULL
);

-- Create table for assignment groups
CREATE TABLE IF NOT EXISTS opdracht_groepen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    opdracht_id INT NOT NULL,
    naam VARCHAR(100) NOT NULL,
    max_groepsgrootte INT DEFAULT 1,
    aangemaakt_door INT NOT NULL,
    aangemaakt_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (opdracht_id) REFERENCES opdrachten(id) ON DELETE CASCADE,
    FOREIGN KEY (aangemaakt_door) REFERENCES gebruikers(id) ON DELETE CASCADE
);

-- Create table for group members
CREATE TABLE IF NOT EXISTS opdracht_groep_leden (
    id INT AUTO_INCREMENT PRIMARY KEY,
    groep_id INT NOT NULL,
    student_id INT NOT NULL,
    is_verantwoordelijke BOOLEAN DEFAULT FALSE,
    toegevoegd_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (groep_id) REFERENCES opdracht_groepen(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES gebruikers(id) ON DELETE CASCADE,
    UNIQUE KEY unique_group_member (groep_id, student_id)
);

-- Add group reference to submissions
ALTER TABLE opdracht_indieningen
ADD COLUMN groep_id INT,
ADD FOREIGN KEY (groep_id) REFERENCES opdracht_groepen(id) ON DELETE SET NULL;

-- Create table for submission history
CREATE TABLE IF NOT EXISTS inlevering_historie (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inlevering_id INT NOT NULL,
    actie VARCHAR(50) NOT NULL,
    beschrijving TEXT,
    uitgevoerd_door INT NOT NULL,
    uitgevoerd_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inlevering_id) REFERENCES opdracht_indieningen(id) ON DELETE CASCADE,
    FOREIGN KEY (uitgevoerd_door) REFERENCES gebruikers(id) ON DELETE CASCADE
);

-- Create indexes for better performance
CREATE INDEX idx_opdrachten_vak_id ON opdrachten(vak_id);
CREATE INDEX idx_opdracht_indieningen_opdracht_id ON opdracht_indieningen(opdracht_id);
CREATE INDEX idx_opdracht_indieningen_student_id ON opdracht_indieningen(student_id);
CREATE INDEX idx_opdracht_indieningen_status ON opdracht_indieningen(status);
CREATE INDEX idx_inlevering_bestanden_inlevering_id ON inlevering_bestanden(inlevering_id);
CREATE INDEX idx_bijlagen_opdracht_id ON bijlagen(opdracht_id);
CREATE INDEX idx_bijlagen_inlevering_id ON bijlagen(inlevering_id);
CREATE INDEX idx_notificaties_gebruiker_id ON notificaties(gebruiker_id);
CREATE INDEX idx_notificaties_is_gelezen ON notificaties(is_gelezen);

-- Create a view for assignment overview
CREATE OR REPLACE VIEW opdracht_overzicht AS
SELECT 
    o.id,
    o.titel,
    o.beschrijving,
    o.deadline,
    o.max_cijfer,
    o.vak_id,
    v.naam AS vak_naam,
    v.code AS vak_code,
    v.docent_id,
    d.voornaam AS docent_voornaam,
    d.achternaam AS docent_achternaam,
    o.aangemaakt_op,
    o.updated_at,
    COUNT(DISTINCT i.id) AS aantal_inzendingen,
    COUNT(DISTINCT CASE WHEN i.status = 'ingeleverd' THEN i.id END) AS ingeleverd,
    COUNT(DISTINCT CASE WHEN i.cijfer IS NOT NULL THEN i.id END) AS nagekeken,
    o.categorie_id,
    oc.naam AS categorie_naam,
    o.sjabloon_id,
    os.titel AS sjabloon_naam
FROM 
    opdrachten o
JOIN 
    vakken v ON o.vak_id = v.id
JOIN 
    gebruikers d ON v.docent_id = d.id
LEFT JOIN 
    opdracht_indieningen i ON o.id = i.opdracht_id
LEFT JOIN
    opdracht_categorieen oc ON o.categorie_id = oc.id
LEFT JOIN
    opdracht_sjablonen os ON o.sjabloon_id = os.id
GROUP BY 
    o.id, o.titel, o.beschrijving, o.deadline, o.vak_id, v.naam, v.code, 
    v.docent_id, d.voornaam, d.achternaam, o.aangemaakt_op, o.updated_at,
    o.categorie_id, oc.naam, o.sjabloon_id, os.titel;

-- Create a view for student submissions
CREATE OR REPLACE VIEW student_inzendingen AS
SELECT 
    i.id AS inzending_id,
    i.opdracht_id,
    o.titel AS opdracht_titel,
    o.deadline,
    i.student_id,
    s.voornaam AS student_voornaam,
    s.achternaam AS student_achternaam,
    i.status,
    i.ingeleverd_op,
    i.cijfer,
    i.opmerking,
    o.vak_id,
    v.naam AS vak_naam,
    v.code AS vak_code,
    v.docent_id,
    d.voornaam AS docent_voornaam,
    d.achternaam AS docent_achternaam,
    i.groep_id,
    g.naam AS groep_naam,
    (SELECT COUNT(*) FROM inlevering_bestanden WHERE inlevering_id = i.id) AS aantal_bestanden,
    (SELECT COUNT(*) FROM inlevering_reacties WHERE inlevering_id = i.id) AS aantal_reacties,
    (SELECT COUNT(*) FROM inlevering_reacties WHERE inlevering_id = i.id AND is_prive = FALSE) AS aantal_publieke_reacties,
    (SELECT COUNT(*) FROM beoordeling_scores WHERE inlevering_id = i.id) AS aantal_beoordelingen,
    (SELECT MAX(beoordeeld_op) FROM beoordeling_scores WHERE inlevering_id = i.id) AS laatste_beoordeling
FROM 
    opdracht_indieningen i
JOIN 
    opdrachten o ON i.opdracht_id = o.id
JOIN 
    gebruikers s ON i.student_id = s.id
JOIN 
    vakken v ON o.vak_id = v.id
JOIN 
    gebruikers d ON v.docent_id = d.id
LEFT JOIN 
    opdracht_groepen g ON i.groep_id = g.id;

-- Add a function to calculate the average grade for an assignment
DELIMITER //
CREATE FUNCTION bereken_gemiddelde_cijfer(opdracht_id_param INT) 
RETURNS DECIMAL(5,2)
DETERMINISTIC
BEGIN
    DECLARE avg_grade DECIMAL(5,2);
    
    SELECT AVG(cijfer) INTO avg_grade
    FROM opdracht_indieningen
    WHERE opdracht_id = opdracht_id_param
    AND cijfer IS NOT NULL;
    
    RETURN COALESCE(avg_grade, 0);
END //
DELIMITER ;

-- Add a procedure to submit an assignment
DELIMITER //
CREATE PROCEDURE dienOpdrachtIn(
    IN p_opdracht_id INT,
    IN p_student_id INT,
    IN p_opmerking TEXT
)
BEGIN
    DECLARE v_count INT;
    
    -- Check if submission already exists
    SELECT COUNT(*) INTO v_count 
    FROM opdracht_indieningen 
    WHERE opdracht_id = p_opdracht_id 
    AND student_id = p_student_id;
    
    IF v_count = 0 THEN
        -- Insert new submission
        INSERT INTO opdracht_indieningen (opdracht_id, student_id, opmerking, status, ingeleverd_op)
        VALUES (p_opdracht_id, p_student_id, p_opmerking, 'ingeleverd', NOW());
        
        -- Log the submission
        INSERT INTO inlevering_historie (inlevering_id, actie, beschrijving, uitgevoerd_door)
        VALUES (LAST_INSERT_ID(), 'ingeleverd', 'Opdracht ingeleverd', p_student_id);
    ELSE
        -- Update existing submission
        UPDATE opdracht_indieningen 
        SET opmerking = p_opmerking,
            status = 'ingeleverd',
            ingeleverd_op = NOW(),
            gewijzigd_op = NOW()
        WHERE opdracht_id = p_opdracht_id 
        AND student_id = p_student_id;
        
        -- Log the update
        INSERT INTO inlevering_historie (inlevering_id, actie, beschrijving, uitgevoerd_door)
        SELECT id, 'bijgewerkt', 'Inzending bijgewerkt', p_student_id
        FROM opdracht_indieningen
        WHERE opdracht_id = p_opdracht_id 
        AND student_id = p_student_id;
    END IF;
END //
DELIMITER ;

-- Add a procedure to grade a submission
DELIMITER //
CREATE PROCEDURE beoordeelInzending(
    IN p_inzending_id INT,
    IN p_cijfer DECIMAL(5,2),
    IN p_feedback TEXT,
    IN p_beoordeeld_door INT
)
BEGIN
    DECLARE v_opdracht_id INT;
    DECLARE v_student_id INT;
    
    -- Get submission details
    SELECT opdracht_id, student_id INTO v_opdracht_id, v_student_id
    FROM opdracht_indieningen
    WHERE id = p_inzending_id;
    
    -- Update the submission
    UPDATE opdracht_indieningen
    SET cijfer = p_cijfer,
        status = 'beoordeeld',
        beoordeeld_door = p_beoordeeld_door,
        beoordelingsdatum = NOW()
    WHERE id = p_inzending_id;
    
    -- Add feedback if provided
    IF p_feedback IS NOT NULL AND p_feedback != '' THEN
        INSERT INTO feedback (inlevering_id, docent_id, inhoud)
        VALUES (p_inzending_id, p_beoordeeld_door, p_feedback);
    END IF;
    
    -- Log the grading
    INSERT INTO inlevering_historie (inlevering_id, actie, beschrijving, uitgevoerd_door)
    VALUES (p_inzending_id, 'beoordeeld', CON('Cijfer toegekend: ', p_cijfer), p_beoordeeld_door);
    
    -- Update the grade in the grades table
    INSERT INTO cijfers (student_id, vak_id, opdracht_id, cijfer, toegekend_door)
    VALUES (v_student_id, 
           (SELECT vak_id FROM opdrachten WHERE id = v_opdracht_id),
           v_opdracht_id,
           p_cijfer,
           p_beoordeeld_door)
    ON DUPLICATE KEY UPDATE 
        cijfer = p_cijfer,
        toegekend_door = p_beoordeeld_door,
        toegekend_op = NOW();
END //
DELIMITER ;

-- Add a trigger to update the updated_at timestamp on assignments
DELIMITER //
CREATE TRIGGER before_opdracht_update
BEFORE UPDATE ON opdrachten
FOR EACH ROW
BEGIN
    SET NEW.updated_at = NOW();
END //
DELIMITER ;

-- Add a trigger to log submission status changes
DELIMITER //
CREATE TRIGGER after_inzending_status_change
AFTER UPDATE ON opdracht_indieningen
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status THEN
        INSERT INTO inlevering_status_historie (inlevering_id, status, gewijzigd_door)
        VALUES (NEW.id, NEW.status, COALESCE(NEW.beoordeeld_door, NEW.student_id));
    END IF;
END //
DELIMITER ;
