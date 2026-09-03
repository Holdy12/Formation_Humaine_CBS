USE formation_humaine_db;

-- 1. Insertion des Rôles
INSERT INTO ROLE (CODE_ROLE, LIBELLE_CRITERE) VALUES
('ADMIN', 'Administrateur Système'),
('ENSEIGNANT', 'Enseignant / Formateur'),
('ETUDIANT', 'Étudiant');

-- 2. Création de l'Administrateur par défaut (Mot de passe: Admin123!)
INSERT INTO PERSONNE (
    ID_ROLE, NOM, PRENOM, EMAIL, MOT_DE_PASSE, 
    TELEPHONE, SEXE, DATE_NAISSANCE, ADRESSE, MATRICULE
) VALUES (
    1, 'ADMIN', 'Système', 'admin@formation.local', 
    '$2y$10$e8W/XGq6C4mG1Nq/4l8q9O4Cq9o/v1e5u2.9X6W1R1vX6Y1vX6Y1v', 
    '00000000', 'M', '2000-01-01', 'Administration', 'ADM-2026-001'
);

INSERT INTO PERSONNEL_ADMINISTRATIF (ID_PERSONNE, POSTE) 
VALUES (1, 'Super Administrateur');