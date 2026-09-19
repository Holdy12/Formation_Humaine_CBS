<?php
// app/Views/site/contenu.php — textes et données du site vitrine. C'est le seul fichier à modifier
// pour changer un texte, un numéro ou une photo. Les vues ne font que la mise en page.
//
// Sources : cefod-tchad.org (histoire, pédagogie, masters, admission, horaires) et le site de l'école
// cbs-tchad.org tel qu'archivé en novembre 2024 (licences, coordonnées). Les points à confirmer par
// l'école sont listés en bas, dans « a_valider ».
return [
    'ecole' => [
        'nom'           => 'CEFOD Business School',
        'sigle'         => 'CBS',
        'surtitre'      => "Institut universitaire du CEFOD, à N'Djamena",
        'devise'        => ['Excellence', 'Probité', 'Créativité'],
        'accroche'      => "Former des cadres compétents, intègres et créatifs pour le Tchad.",
        'presentation'  => "CEFOD Business School est l'institut universitaire du CEFOD, reconnu par l'État tchadien depuis 2014. "
                         . "Licences, masters et formation continue en gestion, droit, économie, informatique et sciences de l'information.",
        'site_officiel' => 'https://cbs-tchad.org',
    ],

    // Titre de l'onglet et description pour les moteurs de recherche, par page.
    'pages' => [
        'accueil'       => ['titre' => 'Formation Humaine · CEFOD Business School',
                            'description' => "Le site de la Formation Humaine de CEFOD Business School, à N'Djamena : présences, engagement, points et résultats de chaque étudiant, avec l'accès à son espace."],
        'vie_etudiante' => ['titre' => 'Vie étudiante et Formation Humaine · CEFOD Business School',
                            'description' => "Ce que le CBS attend de ses étudiants et comment il le mesure : vingt points par semestre, quatre domaines, des règles identiques pour tous."],
        'formations'    => ['titre' => 'Formations · CEFOD Business School',
                            'description' => "Huit licences, cinq masters et MBA, et la formation continue de CEFOD Business School, institut universitaire du CEFOD à N'Djamena."],
        'admission'     => ['titre' => 'Admission · CEFOD Business School',
                            'description' => "Rejoindre CEFOD Business School : dossier, concours ou entretien selon le niveau, inscription. Candidatures de juillet à septembre."],
        'contact'       => ['titre' => 'Contact · CEFOD Business School',
                            'description' => "Nous trouver à Ardep-Djoumbal, N'Djamena, et nous écrire : téléphone, WhatsApp, e-mail, horaires."],
    ],

    // Repères, dits en une phrase ; les nombres sont mis en valeur par la vue.
    'reperes' => [
        ['texte', 'Créée en '], ['nombre', '2014'], ['texte', ' par le CEFOD, fondé en '], ['nombre', '1966'],
        ['texte', ", l'école a ouvert ses portes en "], ['nombre', '2016'], ['texte', "\u{202f}: "],
        ['nombre', '8'], ['texte', ' licences, '], ['nombre', '5'], ['texte', ' masters et MBA.'],
    ],
    'plafonds' => [['Vie des clubs', 3, 'club'], ['Comportement écologique', 2, 'ecologie'], ['Action citoyenne', 2, 'citoyen']],

    'histoire' => "Le Centre d'Études et de Formation pour le Développement, fondé en 1966 par la Compagnie de Jésus, accompagne "
                . "depuis près de soixante ans la réflexion et la formation au Tchad. En 2014, il crée CEFOD Business School pour "
                . "offrir une formation diplômante et professionnalisante aux futurs cadres du pays. L'école ouvre ses portes en 2016 "
                . "et lance son cycle master en 2019.",
    'pedagogie' => "L'étudiant est au centre : rigueur, ponctualité, cours appuyés par des études de cas, stages, visites de terrain "
                 . "et voyages d'études. Une éthique propre au CEFOD, connue pour son exigence, et un accompagnement attentif de chacun.",

    // Formation Humaine : capital de départ, domaines et exemples de critères (barème indicatif ; le
    // barème en vigueur est celui de l'application, modifiable par la direction dans Barème).
    'capital' => 20,
    'domaines' => [
        ['nom' => 'Discipline', 'definition' => 'Ponctualité, tenue, respect des règles.',
         'exemples' => [['Retard', -0.25], ['Extravagance vestimentaire', -0.5], ['Écart de comportement', -1]], 'plafond' => null, 'couleur' => null],
        ['nom' => 'Vie des clubs', 'definition' => 'Participation, engagement et initiative dans les clubs, animés par un responsable.',
         'exemples' => [["Engagement et esprit d'initiative", 3], ['Absence', -0.25], ['Indiscipline', -1]], 'plafond' => 3, 'couleur' => 'club'],
        ['nom' => 'Comportement écologique', 'definition' => 'Respect du campus et de ses ressources.',
         'exemples' => [['Action écologique positive', 0.5], ['Équipements laissés allumés', -0.5], ['Jeter des ordures', -1]], 'plafond' => 2, 'couleur' => 'ecologie'],
        ['nom' => 'Action citoyenne', 'definition' => 'Engagement communautaire, concours, distinctions.',
         'exemples' => [['Engagement communautaire', 1], ['Prix ou reconnaissance', 2.5], ['Incivisme', -2]], 'plafond' => 2, 'couleur' => 'citoyen'],
    ],
    'pourquoi_accroche' => "La formation d'un cadre ne s'arrête pas aux cours.",
    'pourquoi' => "Héritier de la pédagogie du CEFOD, le CBS suit et évalue la ponctualité, l'honnêteté, le respect des lieux "
                . "et des autres, l'engagement : les mêmes règles pour tous, et un dossier que chaque étudiant peut consulter à tout moment.",
    'fonctionnement' => [
        ['Vingt points au départ', "Chaque semestre commence avec un capital de 20 points. Le solde ne dépasse jamais 20."],
        ["L'appel", "Retards et absences sont relevés à l'appel, fait par le délégué de promotion ou l'enseignant. Une absence peut être justifiée dans les 24 heures, justificatif à l'appui, directement en ligne."],
        ['Le signalement', "Un écart de conduite fait l'objet d'un signalement, instruit par le chargé de discipline. L'étudiant est entendu et peut répondre par écrit avant toute décision."],
        ['Les bonifications', "Les engagements positifs rapportent des points, dans la limite d'un plafond par domaine : trois points pour la vie des clubs, deux pour le comportement écologique, deux pour l'action citoyenne."],
        ['Le résultat', "À la clôture du semestre, le solde devient la note de Formation Humaine, avec une mention. Solde, détail par domaine, historique et relevé imprimable sont dans l'espace étudiant."],
    ],
    'aide_connexion' => "Votre identifiant est votre matricule ou votre adresse e-mail. Le mot de passe temporaire est remis par l'administration à la création du compte.",

    'formations' => [
        'intro' => "CEFOD Business School délivre des licences en trois ans et des masters en deux ans, reconnus par le Ministère de "
                 . "l'Enseignement supérieur. Les cours s'appuient sur des études de cas, des stages et des visites en entreprise.",
        'licences' => [
            'Gestion des ressources humaines', 'Comptabilité – Finances', 'Droit et carrière judiciaire',
            'Économie et développement durable', "Science de l'information documentaire", 'Génie informatique',
            'Marketing et communication digitale', 'Logistique et transport',
        ],
        'licences_note' => "Les titulaires d'un Bac+2 et d'une expérience professionnelle peuvent rejoindre le cursus en troisième année (parcours professionnel).",
        'masters' => [
            'MBA Management des organisations', 'Entrepreneuriat et gestion de projets', 'Droit des affaires et fiscalité',
            'Gestion environnementale et développement durable', 'Action publique et gouvernance locale',
        ],
        'continue' => "Des formations courtes et certifiantes pour les professionnels en activité, en gestion, droit et management. "
                    . "Programme et calendrier sur demande auprès du secrétariat académique.",
    ],

    'admission' => [
        'intro' => "L'admission se fait sur dossier, puis sur concours ou entretien selon le niveau d'entrée. "
                 . "Les candidatures sont ouvertes chaque année de juillet à septembre.",
        'etapes' => [
            ['Le dossier', "Formulaire de candidature, copies du diplôme et des relevés, pièce d'identité, photos. Frais de dossier : 5\u{202f}000 FCFA."],
            ["Le concours ou l'entretien", "Concours écrit pour les titulaires du baccalauréat ; test et entretien pour les candidats au master."],
            ["L'inscription", "Après admission, inscription administrative au secrétariat académique."],
        ],
        'conditions' => [
            ['le baccalauréat', 'Licence 1', 'concours, de juillet à septembre'],
            ['un Bac+2 et une expérience professionnelle', 'Licence 3, parcours professionnel', 'dossier et entretien'],
            ['une licence (Bac+3)', 'Master 1', 'test et entretien ; expérience professionnelle appréciée'],
        ],
        'frais' => "Les frais de scolarité de l'année en cours sont communiqués par le secrétariat académique.",
        // Mois d'ouverture des candidatures (1 = janvier), pour le calendrier de la page.
        'mois_ouverts' => [7, 8, 9],
        'inscription_url' => 'https://cbs-edu.org/index.php?r=onlineadmission%2Fregistration%2Findex',
    ],

    'contact' => [
        'adresse'    => ['Quartier Ardep-Djoumbal', "N'Djamena, Tchad"],
        'telephones' => [['+235 65 77 23 10', '+23565772310'], ['+235 22 51 54 32', '+23522515432']],
        'email'      => 'infos@cbs-edu.org',
        'whatsapp'   => '23565772310',
        'horaires'   => 'Lundi – samedi, 7 h – 19 h',
        'facebook'   => ['Institut Universitaire du CEFOD – CBS', 'https://www.facebook.com/cbscefod21/'],
        'maps'       => "https://www.google.com/maps/search/?api=1&query=CEFOD+Business+School+N%27Djamena",
        // Formulaire : objets proposés (clé envoyée => libellé) et adresse qui reçoit les messages.
        'objets'       => ['admission' => 'Admission', 'formations' => 'Formations', 'formation_humaine' => 'Formation Humaine et espace étudiant', 'autre' => 'Autre question'],
        'destinataire' => 'infos@cbs-edu.org',
    ],

    // Photos dans public/assets/images/site/ : chaque nom existe en .jpg et .webp, en pleine largeur et en 720 px.
    'photos' => [
        'campus'    => ['fichier' => 'campus-batiment',       'largeur' => 1210, 'hauteur' => 864, 'alt' => "Le bâtiment du CBS derrière les arbres du campus, à Ardep-Djoumbal", 'legende' => "Le campus, quartier Ardep-Djoumbal"],
        'promotion' => ['fichier' => 'promotion-devant-cbs',  'largeur' => 1280, 'hauteur' => 673, 'alt' => "Une promotion en tenue devant l'entrée du CBS",                        'legende' => "Une promotion devant l'entrée de l'école"],
        'portail'   => ['fichier' => 'etudiants-portail-cbs', 'largeur' => 1280, 'hauteur' => 959, 'alt' => "Des étudiants réunis devant le portail marqué CBS",                     'legende' => "Devant le portail du CBS"],
        'semaine1'  => ['fichier' => 'semaine-etudiant-01',   'largeur' => 1080, 'hauteur' => 720, 'alt' => "Trois étudiants en t-shirt de la Semaine de l'Étudiant autour d'un téléphone", 'legende' => "Semaine de l'Étudiant, 5ᵉ édition"],
        'semaine2'  => ['fichier' => 'semaine-etudiant-02',   'largeur' => 1080, 'hauteur' => 720, 'alt' => "Des étudiantes pendant la Semaine de l'Étudiant, sur le campus",        'legende' => "Semaine de l'Étudiant, sur le campus"],
        'escalier'  => ['fichier' => 'etudiants-escalier',    'largeur' => 1080, 'hauteur' => 810, 'alt' => "Six étudiants en polo CBS dans un escalier du campus",                 'legende' => "Entre deux cours"],
        'classe'    => ['fichier' => 'salle-de-classe',       'largeur' => 1280, 'hauteur' => 959, 'alt' => "Des étudiants en tenue dans une salle de cours",                        'legende' => "En salle de cours"],
    ],

    // Points à confirmer par l'école avant la mise en ligne (voir documentation/site-vitrine.md).
    'a_valider' => [
        "Nombre d'étudiants ou de diplômés, si l'école souhaite l'afficher dans les repères",
        "Liste des masters (source : cefod-tchad.org uniquement)",
        "Dates du concours (juillet – septembre) et pièces du dossier, frais de dossier de 5 000 FCFA",
        "Numéro WhatsApp (65 77 23 10) et ligne fixe (22 51 54 32), horaires d'ouverture",
        "Adresse exacte : le CBS est-il dans l'enceinte du CEFOD ?",
        "Liste des clubs",
        "Valeurs du barème affichées (exemples) : à mettre à jour si le barème change dans l'application",
        "Consentement des personnes reconnaissables sur les photos publiées ; crédit du photographe (signature « D1S »)",
    ],
];
