-- Jeu de données de test : à importer après schema.sql et seed.sql.
-- Tous les comptes ci-dessous ont le mot de passe : Test1234!
USE formation_humaine_db;

SET @MDP = '$2y$10$3LR8xc73h1qu1hvnWhLx2OdmOoUX5LjPBoYUfQjVXidwXLpmor8hG';

-- Structure académique
INSERT INTO DEPARTEMENT (CODE_DEPT, NOM_DEPT) VALUES ('INFO', 'Informatique et Télécoms');

INSERT INTO FILIERE (ID_DEPT, CODE_FILIERE, NOM_FILIERE) VALUES
(1, 'GI', 'Génie Informatique'),
(1, 'RT', 'Réseaux et Télécoms');

INSERT INTO NIVEAU (CODE_NIVEAU, LIBELLE_NIVEAU) VALUES
('L1', 'Licence 1'),
('L2', 'Licence 2'),
('L3', 'Licence 3');

INSERT INTO ANNEE_ACADEMIQUE (LIBELLE_ANNEE, DATE_DEBUT, DATE_FIN) VALUES
('2026-2027', '2026-09-01', '2027-07-31');

INSERT INTO SEMESTRE (ID_ANNEE, CODE_SEMESTRE, LIBELLE_SEMESTRE, DATE_DEBUT, DATE_FIN) VALUES
(1, 'S1', 'Semestre 1', '2026-09-01', '2027-01-31'),
(1, 'S2', 'Semestre 2', '2027-02-01', '2027-07-31');

INSERT INTO PROMOTION (ID_ANNEE, ID_NIVEAU, ID_FILIERE, CODE_PROMO, EFFECTIF_PROMO) VALUES
(1, 1, 1, 'L1-GI-2026', 2),
(1, 2, 2, 'L2-RT-2026', 1);

-- Personnel
INSERT INTO PERSONNE (ID_ROLE, NOM, PRENOM, EMAIL, MOT_DE_PASSE, TELEPHONE, SEXE, DATE_NAISSANCE, ADRESSE, MATRICULE) VALUES
(3, 'PANDA',   'Enock',   'enock.panda@cbs.local',   @MDP, '+235 60 00 00 01', 'M', '1985-04-12', 'N''Djamena', 'PER-2026-001'),
(4, 'TCHOUA',  'Marie',   'marie.tchoua@cbs.local',  @MDP, '+235 60 00 00 02', 'F', '1988-11-03', 'N''Djamena', 'PER-2026-002'),
(6, 'MAHAMAT', 'Idriss',  'idriss.mahamat@cbs.local', @MDP, '+235 60 00 00 03', 'M', '1990-02-20', 'N''Djamena', 'PER-2026-003');

INSERT INTO PERSONNEL_ADMINISTRATIF (ID_PERSONNE, POSTE) VALUES (2, 'Chargé de discipline'), (4, 'Responsable du club environnement');
INSERT INTO ENSEIGNANT (ID_PERSONNE, GRADE, DOMAINE) VALUES (3, 'Maître-assistant', 'Formation Humaine');

-- Clubs
INSERT INTO CLUB (NOM_CLUB, DESCRIPTION, ID_RESPONSABLE) VALUES
('Club Environnement', 'Actions écologiques et entretien du campus', 4),
('Club Informatique',  'Ateliers et projets numériques', NULL);

-- Étudiants (Awa KOUASSI est déléguée)
INSERT INTO PERSONNE (ID_ROLE, NOM, PRENOM, EMAIL, MOT_DE_PASSE, TELEPHONE, SEXE, DATE_NAISSANCE, ADRESSE, MATRICULE) VALUES
(7, 'KOUASSI', 'Awa',     'awa.kouassi@cbs.local',     @MDP, '+235 66 00 00 01', 'F', '2006-03-15', 'Moursal',   'CBS2026-0001'),
(7, 'DIALLO',  'Moussa',  'moussa.diallo@cbs.local',   @MDP, '+235 66 00 00 02', 'M', '2005-08-22', 'Chagoua',   'CBS2026-0002'),
(7, 'NGUEMA',  'Chantal', 'chantal.nguema@cbs.local',  @MDP, '+235 66 00 00 03', 'F', '2004-12-01', 'Dembé',     'CBS2026-0003');

INSERT INTO ETUDIANT (ID_PERSONNE, ID_PROMO, ID_CLUB, EST_DELEGUE) VALUES
(5, 1, 2, 1),
(6, 1, 1, 0),
(7, 2, NULL, 0);

-- Responsable de la Formation Humaine
INSERT INTO PERSONNE (ID_ROLE, NOM, PRENOM, EMAIL, MOT_DE_PASSE, TELEPHONE, SEXE, DATE_NAISSANCE, ADRESSE, MATRICULE) VALUES
(2, 'NDOUBA', 'Sylvie', 'sylvie.ndouba@cbs.local', @MDP, '+235 60 00 00 04', 'F', '1982-06-30', 'N''Djamena', 'PER-2026-004');
INSERT INTO PERSONNEL_ADMINISTRATIF (ID_PERSONNE, POSTE) VALUES (8, 'Responsable de la Formation Humaine');

-- Séances et appels
INSERT INTO SEANCE (ID_CLUB, ID_PROMO, TITRE_SEANCE, DATE_SEANCE, HEURE_DEBUT, HEURE_FIN, LIEU) VALUES
(1, NULL, 'Nettoyage du campus',        '2026-09-05', '08:00:00', '10:00:00', 'Campus'),
(NULL, 1, 'Cours de Formation Humaine', '2026-09-08', '08:00:00', '10:00:00', 'Salle B2'),
(NULL, 1, 'Rentrée solennelle',         '2026-09-01', '09:00:00', '11:00:00', 'Amphithéâtre'),
(NULL, 1, 'Atelier savoir-être',        CURDATE(),    '07:30:00', '09:30:00', 'Salle B2'),
(1, NULL, 'Sortie reboisement',         DATE_ADD(CURDATE(), INTERVAL 3 DAY), '08:00:00', '12:00:00', 'Berges du Chari');

INSERT INTO APPEL (ID_SEANCE, ID_PERSONNE, DATE_APPEL) VALUES
(1, 4, '2026-09-05 08:10:00'),
(2, 3, '2026-09-08 08:05:00'),
(3, 3, '2026-09-01 09:10:00'),
(4, 3, CONCAT(CURDATE(), ' 07:40:00'));

INSERT INTO PRESENCE (ID_APPEL, ID_PERSONNE, STATUT) VALUES
(1, 6, 'PRESENT'),
(2, 5, 'PRESENT'),
(2, 6, 'ABSENT'),
(3, 5, 'PRESENT'),
(3, 6, 'ABSENT'),
(4, 5, 'RETARD'),
(4, 6, 'ABSENT');

INSERT INTO JUSTIFICATION_ABSENCE (ID_PRESENCE, MOTIF, CHEMIN_FICHIER, DATE_DEPOT) VALUES
(3, 'Consultation médicale le matin du 8 septembre.', NULL, '2026-09-08 18:30:00');

-- Signalements
INSERT INTO SIGNALEMENT (ID_PERSONNE_ETUDIANT, ID_PERSONNE_AUTEUR, ID_PERSONNE_VALIDATEUR, ID_CRITERE, TITRE_SIGNALEMENT, DESCRIPTION, DATE_FAITS, LIEU_SIGNALEMENT, DATE_SIGNALEMENT, STATUT, DECISION, DATE_DECISION) VALUES
(6, 3, NULL, 3, 'Perturbation en cours',
 'Discussions répétées et refus de se calmer après plusieurs rappels.',
 '2026-09-08 09:15:00', 'Salle B2', '2026-09-08 12:00:00', 'SOUMIS', NULL, NULL),
(5, 4, 2, 18, 'Participation au nettoyage du campus',
 'Présence active et encadrement des autres membres pendant toute la séance.',
 '2026-09-05 08:00:00', 'Campus', '2026-09-05 11:00:00', 'VALIDE',
 'Action confirmée par le responsable du club environnement.', '2026-09-06 10:00:00');

INSERT INTO SIGNALEMENT_HISTORIQUE (ID_SIGNALEMENT, STATUT, ID_PERSONNE, DATE_CHANGEMENT, COMMENTAIRE) VALUES
(1, 'SOUMIS', 3, '2026-09-08 12:00:00', 'Signalement transmis'),
(2, 'SOUMIS', 4, '2026-09-05 11:00:00', 'Signalement transmis'),
(2, 'EN_EXAMEN', 2, '2026-09-05 15:00:00', NULL),
(2, 'VALIDE', 2, '2026-09-06 10:00:00', 'Action confirmée par le responsable du club environnement.');

-- Registre des points
INSERT INTO MOUVEMENT_POINTS (ID_CRITERE, ID_PERSONNE, NOMBRE_POINTS, TYPE_MOUVEMENT, DATE_MOUVEMENT, MOTIF_MOUVEMENT, ID_SIGNALEMENT, ID_VALIDATEUR) VALUES
(18, 5, 0.50, 'POSITIF', '2026-09-06 10:00:00', 'Nettoyage du campus', 2, 2),
(1,  5, 0.25, 'NEGATIF', '2026-09-10 08:30:00', 'Arrivée à 08h25 – cours de Formation Humaine', NULL, 2),
(2,  6, 0.25, 'NEGATIF', '2026-09-08 12:30:00', 'Absence non justifiée – cours de Formation Humaine', NULL, 2),
(21, 7, 2.50, 'POSITIF', '2026-09-11 15:00:00', 'Lauréate du concours d''éloquence inter-écoles', NULL, 2);

INSERT INTO RESULTAT_SEMESTRIEL (ID_SEMESTRE, ID_PERSONNE, NOTE_PROVISOIRE) VALUES
(1, 5, 20.00),
(1, 6, 19.75),
(1, 7, 20.00);

-- Journal
INSERT INTO JOURNAL_CONNEXION (ID_PERSONNE, DATE_CONNEXION, ADRESSE_IP, STATUT, ACTION, DETAILS) VALUES
(1, '2026-09-12 08:00:00', '127.0.0.1', 'SUCCES', 'Connexion', 'Ouverture de session'),
(3, '2026-09-08 12:00:00', '127.0.0.1', 'SUCCES', 'Signalement', 'Nouveau signalement : Perturbation en cours'),
(2, '2026-09-06 10:00:00', '127.0.0.1', 'SUCCES', 'Validation', 'Signalement validé : Participation au nettoyage du campus');
