<?php
// app/Controllers/AdminDashboardController.php

require_once __DIR__ . '/../../config/database.php';

class AdminDashboardController {
    
    public function index() {
        $choixAnnee = $_GET['annee'] ?? 'active';
        $choixSemestre = $_GET['semestre'] ?? 'actif';
        
        $data = $this->getDashboardData($choixAnnee, $choixSemestre);
        require_once __DIR__ . '/../Views/admin/dashboard.php';
    }

    public function getDashboardData($choixAnnee = 'active', $choixSemestre = 'actif'): array {
        try {
            $db = Database::getConnection();

            $totalEtudiants = $db->query("SELECT COUNT(*) FROM ETUDIANT")->fetchColumn();
            $totalUtilisateurs = $db->query("SELECT COUNT(*) FROM PERSONNE")->fetchColumn();
            $totalRoles = $db->query("SELECT COUNT(*) FROM ROLE")->fetchColumn();
            $totalClasses = $db->query("SELECT COUNT(*) FROM PROMOTION")->fetchColumn();

            // 1. Récupération de la liste des années pour le filtre déroulant
            $listeAnnees = [];
            try {
                $stmtListeAnnees = $db->query("SELECT ID_ANNEE, LIBELLE_ANNEE FROM ANNEE_ACADEMIQUE ORDER BY DATE_DEBUT DESC");
                $listeAnnees = $stmtListeAnnees->fetchAll(PDO::FETCH_ASSOC);
            } catch (\Exception $e) {}

            // 2. Gestion de l'ANNEE_ACADEMIQUE
            $anneeDebut = null;
            $anneeFin = null;
            try {
                if ($choixAnnee === 'active') {
                    $stmtAnnee = $db->query("SELECT DATE_DEBUT, DATE_FIN FROM ANNEE_ACADEMIQUE WHERE CURRENT_DATE() BETWEEN DATE_DEBUT AND DATE_FIN LIMIT 1");
                    $anneeInfo = $stmtAnnee->fetch(PDO::FETCH_ASSOC);
                    if ($anneeInfo) {
                        $anneeDebut = $anneeInfo['DATE_DEBUT'];
                        $anneeFin = $anneeInfo['DATE_FIN'];
                    }
                } elseif (is_numeric($choixAnnee)) {
                    $stmtAnnee = $db->prepare("SELECT DATE_DEBUT, DATE_FIN FROM ANNEE_ACADEMIQUE WHERE ID_ANNEE = ? LIMIT 1");
                    $stmtAnnee->execute([$choixAnnee]);
                    $anneeInfo = $stmtAnnee->fetch(PDO::FETCH_ASSOC);
                    if ($anneeInfo) {
                        $anneeDebut = $anneeInfo['DATE_DEBUT'];
                        $anneeFin = $anneeInfo['DATE_FIN'];
                    }
                }
            } catch (\Exception $e) {}

            $dateDebut = $anneeDebut;
            $dateFin = $anneeFin;

            // 3. Signalements filtrés par période
            $totalSignalements = 0;
            try {
                if ($dateDebut && $dateFin) {
                    $stmt = $db->prepare("SELECT COUNT(*) FROM SIGNALEMENT WHERE DATE_SIGNALEMENT BETWEEN ? AND ?");
                    $stmt->execute([$dateDebut, $dateFin]);
                } else {
                    $stmt = $db->query("SELECT COUNT(*) FROM SIGNALEMENT");
                }
                $totalSignalements = $stmt->fetchColumn();
            } catch (\Exception $e) {}

            // 4. Décisions / Justifications validées filtrées par période
            $totalDecisions = 0;
            try {
                if ($dateDebut && $dateFin) {
                    $stmt = $db->prepare("SELECT COUNT(*) FROM JUSTIFICATION_ABSENCE WHERE STATUT_VALIDATION != 'EN_ATTENTE' AND DATE_DEPOT BETWEEN ? AND ?");
                    $stmt->execute([$dateDebut, $dateFin]);
                } else {
                    $stmt = $db->query("SELECT COUNT(*) FROM JUSTIFICATION_ABSENCE WHERE STATUT_VALIDATION != 'EN_ATTENTE'");
                }
                $totalDecisions = $stmt->fetchColumn();
            } catch (\Exception $e) {}

            // 5. Répartition par domaine filtrée par période
            $domainesStats = [];
            $totalPointsPositifs = 0;
            $totalPointsNegatifs = 0;

            try {
                $sqlDomaines = "SELECT d.NOM_DOMAINE AS cat, 
                                       SUM(CASE WHEN m.TYPE_MOUVEMENT = 'POSITIF' THEN m.NOMBRE_POINTS ELSE 0 END) AS pos,
                                       SUM(CASE WHEN m.TYPE_MOUVEMENT = 'NEGATIF' THEN m.NOMBRE_POINTS ELSE 0 END) AS neg
                                FROM DOMAINE d
                                LEFT JOIN CRITERE c ON d.ID_DOMAINE = c.ID_DOMAINE
                                LEFT JOIN MOUVEMENT_POINTS m ON c.ID_CRITERE = m.ID_CRITERE";
                
                if ($dateDebut && $dateFin) {
                    $sqlDomaines .= " WHERE (m.DATE_MOUVEMENT BETWEEN ? AND ? OR m.DATE_MOUVEMENT IS NULL)";
                }
                $sqlDomaines .= " GROUP BY d.ID_DOMAINE, d.NOM_DOMAINE";

                $stmt = $db->prepare($sqlDomaines);
                if ($dateDebut && $dateFin) {
                    $stmt->execute([$dateDebut, $dateFin]);
                } else {
                    $stmt->execute();
                }
                $resultDomaines = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $totalNetGlobal = 0;
                foreach ($resultDomaines as $row) {
                    $pos = isset($row['pos']) ? (int)$row['pos'] : 0;
                    $neg = isset($row['neg']) ? (int)$row['neg'] : 0;
                    
                    $net = $pos - $neg;
                    
                    $totalPointsPositifs += $pos;
                    $totalPointsNegatifs += $neg;
                    $totalNetGlobal += abs($net);

                    $domainesStats[] = [
                        'cat' => $row['cat'] ?? 'Inconnu',
                        'pos' => $pos,
                        'neg' => $neg,
                        'net' => $net
                    ];
                }
                foreach ($domainesStats as &$row) {
                    $row['pct'] = $totalNetGlobal > 0 ? round((abs($row['net']) / $totalNetGlobal) * 100) . '%' : '0%';
                }
            } catch (\Exception $e) {}
            $totalPointsAttribues = $totalPointsPositifs + $totalPointsNegatifs;

            // 6. Génération dynamique des mois et de l'évolution par domaine basée sur DATE_DEBUT et DATE_FIN
            $moisLabels = [];
            $moisCles = [];
            $evolutionDatasets = [];

            // 6. Génération dynamique (SANS le bloc try/catch pour voir lâchement l'erreur)
            
            // Valeurs par défaut si aucune date d'année n'est trouvée
            $pDeb = $dateDebut ?? date('Y-m-01', strtotime('-1 year'));
            $pFin = $dateFin ?? date('Y-m-t');

            $debutDt = new DateTime($pDeb);
            $finDt = new DateTime($pFin);
            
            $debutDt->modify('first day of this month');
            $finDt->modify('last day of this month');

            $interval = DateInterval::createFromDateString('1 month');
            // Ajout d'un jour pour inclure la fin de période dans DatePeriod
            $finPeriod = clone $finDt;
            $finPeriod->modify('+1 day');
            $periode = new DatePeriod($debutDt, $interval, $finPeriod);

            $moisNomsFr = [
                1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril', 
                5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août', 
                9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
            ];

            $moisLabels = [];
            $moisCles = [];
            foreach ($periode as $dt) {
                $moisLabels[] = $moisNomsFr[(int)$dt->format('n')] . ' ' . $dt->format('Y');
                $moisCles[] = $dt->format('Y-m');
            }

            // Requête d'évolution par domaine groupée par mois
            $sqlEvo = "SELECT d.NOM_DOMAINE, 
                              DATE_FORMAT(m.DATE_MOUVEMENT, '%Y-%m') as MOIS, 
                              COUNT(m.ID_MOUVEMENT) as TOTAL 
                       FROM DOMAINE d
                       LEFT JOIN CRITERE c ON d.ID_DOMAINE = c.ID_DOMAINE
                       LEFT JOIN MOUVEMENT_POINTS m ON c.ID_CRITERE = m.ID_CRITERE AND m.DATE_MOUVEMENT BETWEEN ? AND ?
                       GROUP BY d.NOM_DOMAINE, MOIS";

            $stmtEvo = $db->prepare($sqlEvo);
            $stmtEvo->execute([$pDeb, $pFin]);
            $resultEvo = $stmtEvo->fetchAll(PDO::FETCH_ASSOC);

            $domainesListe = $db->query("SELECT NOM_DOMAINE FROM DOMAINE")->fetchAll(PDO::FETCH_COLUMN);

            $evoParDomaine = [];
            foreach ($domainesListe as $nomDom) {
                foreach ($moisCles as $mKey) {
                    $evoParDomaine[$nomDom][$mKey] = 0;
                }
            }

            foreach ($resultEvo as $row) {
                if (!empty($row['NOM_DOMAINE']) && isset($row['MOIS'])) {
                    $evoParDomaine[$row['NOM_DOMAINE']][$row['MOIS']] = (int)$row['TOTAL'];
                }
            }

            $evolutionDatasets = [];
            $couleurs = ['#ef4444', '#10b981', '#f97316', '#6366f1', '#3b82f6', '#8b5cf6'];
            $i = 0;
            foreach ($evoParDomaine as $domaineNom => $valeursMois) {
                $dataPoints = [];
                foreach ($moisCles as $moisKey) {
                    $dataPoints[] = $valeursMois[$moisKey] ?? 0;
                }
                $evolutionDatasets[] = [
                    'label' => $domaineNom,
                    'data' => $dataPoints,
                    'borderColor' => $couleurs[$i % count($couleurs)],
                    'backgroundColor' => $couleurs[$i % count($couleurs)],
                    'tension' => 0.3,
                    'fill' => false
                ];
                $i++;
            }
           // 7. Cartes supplémentaires
            $catPlusPositive = 'Non disponible';
            $catPlusNegative = 'Non disponible';
            $etudiantMeritant = 'Aucun étudiant';
            $etudiantSanctionne = 'Aucun étudiant';

            try {
                if (!empty($domainesStats)) {
                    // Tri protégé pour la catégorie la plus positive
                    $sortedPos = $domainesStats;
                    usort($sortedPos, fn($a, $b) => ($b['pos'] ?? 0) <=> ($a['pos'] ?? 0));
                    if (($sortedPos[0]['pos'] ?? 0) > 0) {
                        $catPlusPositive = $sortedPos[0]['cat'] ?? 'Non disponible';
                    }

                    // Tri protégé pour la catégorie la plus négative
                    $sortedNeg = $domainesStats;
                    usort($sortedNeg, fn($a, $b) => ($b['neg'] ?? 0) <=> ($a['neg'] ?? 0));
                    if (($sortedNeg[0]['neg'] ?? 0) > 0) {
                        $catPlusNegative = $sortedNeg[0]['cat'] ?? 'Non disponible';
                    }
                }

                // ... Le reste de la section 7 (requêtes $stmtMeritant et $stmtSanctionne) reste identique ...

                $stmtMeritant = $db->query("
                    SELECT p.NOM, p.PRENOM, SUM(m.NOMBRE_POINTS) as total_pts 
                    FROM MOUVEMENT_POINTS m 
                    JOIN PERSONNE p ON m.ID_PERSONNE = p.ID_PERSONNE 
                    WHERE m.TYPE_MOUVEMENT = 'POSITIF' 
                    GROUP BY p.ID_PERSONNE, p.NOM, p.PRENOM 
                    ORDER BY total_pts DESC 
                    LIMIT 1
                ");
                $resMeritant = $stmtMeritant->fetch(PDO::FETCH_ASSOC);
                if ($resMeritant) {
                    $etudiantMeritant = trim($resMeritant['PRENOM'] . ' ' . $resMeritant['NOM']) . ' (' . $resMeritant['total_pts'] . ' pts)';
                }

                $stmtSanctionne = $db->query("
                    SELECT p.NOM, p.PRENOM, SUM(m.NOMBRE_POINTS) as total_pts 
                    FROM MOUVEMENT_POINTS m 
                    JOIN PERSONNE p ON m.ID_PERSONNE = p.ID_PERSONNE 
                    WHERE m.TYPE_MOUVEMENT = 'NEGATIF' 
                    GROUP BY p.ID_PERSONNE, p.NOM, p.PRENOM 
                    ORDER BY total_pts DESC 
                    LIMIT 1
                ");
                $resSanctionne = $stmtSanctionne->fetch(PDO::FETCH_ASSOC);
                if ($resSanctionne) {
                    $etudiantSanctionne = trim($resSanctionne['PRENOM'] . ' ' . $resSanctionne['NOM']) . ' (' . $resSanctionne['total_pts'] . ' pts)';
                }
            } catch (\Exception $e) {}

            // 8. Activités récentes
            $activitesRecentes = [];
            try {
                $stmt = $db->query("
                    SELECT j.DATE_CONNEXION, j.STATUT, j.ACTION, j.DETAILS, j.ADESSE_IP, 
                           p.NOM, p.PRENOM, p.PHOTO, 
                           r.LIBELLE_ROLE 
                    FROM JOURNAL_CONNEXION j 
                    JOIN PERSONNE p ON j.ID_PERSONNE = p.ID_PERSONNE 
                    LEFT JOIN ROLE r ON p.ID_ROLE = r.ID_ROLE 
                    ORDER BY j.DATE_CONNEXION DESC 
                    LIMIT 10
                ");
                $activitesRecentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (\Exception $e) {}

            return [
                'etudiants'             => (int)$totalEtudiants,
                'utilisateurs'          => (int)$totalUtilisateurs,
                'roles'                 => (int)$totalRoles,
                'classes'               => (int)$totalClasses,
                'signalements'          => (int)$totalSignalements,
                'decisions'             => (int)$totalDecisions,
                'domaines_stats'        => $domainesStats,
                'total_points_positifs' => $totalPointsPositifs,
                'total_points_negatifs' => $totalPointsNegatifs,
                'total_points_attribues'=> $totalPointsAttribues,
                'cat_plus_positive'     => $catPlusPositive,
                'cat_plus_negative'     => $catPlusNegative,
                'etudiant_meritant'     => $etudiantMeritant,
                'etudiant_sanctionne'   => $etudiantSanctionne,
                'activites_recentes'    => $activitesRecentes,
                'liste_annees'          => $listeAnnees,
                'annee_actuelle'        => $choixAnnee,
                'semestre_actuel'       => $choixSemestre,
                'evolution_labels'      => $moisLabels,
                'evolution_datasets'    => $evolutionDatasets
            ];

        } catch (PDOException $e) {
            return [
                'etudiants'             => 0,
                'utilisateurs'          => 0,
                'roles'                 => 0,
                'classes'               => 0,
                'signalements'          => 0,
                'decisions'             => 0,
                'domaines_stats'        => [],
                'total_points_positifs' => 0,
                'total_points_negatifs' => 0,
                'total_points_attribues'=> 0,
                'cat_plus_positive'     => 'Non disponible',
                'cat_plus_negative'     => 'Non disponible',
                'etudiant_meritant'     => 'Aucun étudiant',
                'etudiant_sanctionne'   => 'Aucun étudiant',
                'activites_recentes'    => [],
                'liste_annees'          => [],
                'annee_actuelle'        => $choixAnnee,
                'semestre_actuel'       => $choixSemestre,
                'evolution_labels'      => [],
                'evolution_datasets'    => []
            ];
        }
    }
}