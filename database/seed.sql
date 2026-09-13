USE formation_humaine_db;

-- 1. Rôles
INSERT INTO ROLE (CODE_ROLE, LIBELLE_ROLE) VALUES
('ADMIN',             'Administrateur'),
('RESPONSABLE_FH',    'Responsable de la Formation Humaine'),
('CHARGE_DISCIPLINE', 'Chargé de discipline'),
('ENSEIGNANT',        'Enseignant'),
('RESPONSABLE_CLUB',  'Responsable de club'),
('RESPONSABLE_ENV',   'Responsable du club environnement'),
('ETUDIANT',          'Étudiant');

-- 2. Domaines d'évaluation
INSERT INTO DOMAINE (CODE_DOMAINE, NOM_DOMAINE) VALUES
('DISCIPLINE',  'Discipline'),
('CLUB',        'Participation aux clubs'),
('ECOLOGIE',    'Comportement écologique'),
('CITOYENNETE', 'Action citoyenne');

-- 3. Barème (document préparatoire, section 7)
INSERT INTO CRITERE (ID_DOMAINE, LIBELLE_CRITERE, VALEUR_POINTS) VALUES
(1, 'Retard',                            -0.25),
(1, 'Absence',                           -0.25),
(1, 'Écart de comportement',             -1.00),
(1, 'Malhonnêteté',                      -2.00),
(1, 'Extravagance vestimentaire',        -0.50),
(1, 'Passage au conseil de discipline',  -3.00),
(2, 'Retard',                            -0.25),
(2, 'Absence',                           -0.25),
(2, 'Indiscipline',                      -1.00),
(2, 'Engagement et esprit d''initiative', 3.00),
(3, 'Jeter des ordures',                 -1.00),
(3, 'Gaspillage de l''eau',              -1.00),
(3, 'Saccage des infrastructures',       -5.00),
(3, 'Équipements laissés allumés',       -0.50),
(3, 'Sanction appliquée à la salle',     -2.00),
(3, 'Non-respect des espaces verts',     -1.00),
(3, 'Saccage des tableaux ou affiches',  -2.00),
(3, 'Action écologique positive',         0.50),
(4, 'Engagement communautaire',           1.00),
(4, 'Incivisme',                         -2.00),
(4, 'Prix ou reconnaissance',             2.50),
(4, 'Participation à un concours',        2.50);

-- 4. Paramètres système (spécifications techniques, section 3)
INSERT INTO PARAMETRE_SYSTEME (CODE_PARAMETRE, VALEUR, DESCRIPTION) VALUES
('CAPITAL_INITIAL_NOTE',            '20.00', 'Capital de points au début de chaque semestre'),
('PLAFOND_BONUS_ECOLOGIE',          '2.00',  'Plafond de bonification Écologie par semestre'),
('PLAFOND_BONUS_CITOYENNETE',       '2.00',  'Plafond de bonification Citoyenneté par semestre'),
('PLAFOND_BONUS_CLUB',              '3.00',  'Plafond de bonification Club par semestre'),
('PENALITE_CONSEIL_DISCIPLINE',     '3.00',  'Retrait automatique en cas de passage au conseil'),
('PENALITE_SANCTION_COLLECTIVE',    '2.00',  'Retrait appliqué individuellement à chaque membre'),
('DELAI_DEPOT_JUSTIFICATIF_HEURES', '24',    'Délai maximal en heures pour déposer un justificatif'),
('RETARD_SEUIL_ABSENCE_MINUTES',    '20',    'Seuil de retard en minutes assimilé à une absence'),
('NOTE_MINIMALE_POSSIBLE',          '0.00',  'Plancher de la note semestrielle'),
('NOTE_MAXIMALE_POSSIBLE',          '20.00', 'Plafond de la note semestrielle'),
('SEUIL_CRITIQUE_NOTE',             '10.00', 'Note en dessous de laquelle un étudiant est signalé comme en difficulté'),
('MENTION_TRES_BIEN',               '16.00', 'Note minimale pour la mention Très bien'),
('MENTION_BIEN',                    '14.00', 'Note minimale pour la mention Bien'),
('MENTION_ASSEZ_BIEN',              '12.00', 'Note minimale pour la mention Assez bien'),
('MENTION_PASSABLE',                '10.00', 'Note minimale pour la mention Passable');

-- 5. Administrateur par défaut (mot de passe : Admin123!)
INSERT INTO PERSONNE (ID_ROLE, NOM, PRENOM, EMAIL, MOT_DE_PASSE, TELEPHONE, SEXE, DATE_NAISSANCE, ADRESSE, MATRICULE) VALUES
(1, 'ADMIN', 'Système', 'admin@formation.local',
 '$2y$10$cMP/dv6ACEkBBlZeleC5hukA3CgoacWdTe44BmM2mwAwOV6eUeMJW',
 '00000000', 'M', '2000-01-01', 'Administration', 'ADM-2026-001');

INSERT INTO PERSONNEL_ADMINISTRATIF (ID_PERSONNE, POSTE) VALUES (1, 'Super Administrateur');
