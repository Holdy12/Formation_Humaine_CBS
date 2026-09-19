#!/usr/bin/env bash
# Recette : rejoue les cas de documentation/espace-etudiant.md et documentation/espace-personnel.md
# contre une instance locale. Voir documentation/tests.md.
#
# Variables d'environnement (facultatives) :
#   URL    adresse de l'application       (défaut : http://localhost:8000/index.php)
#   PHP    exécutable php                  (défaut : php, ou C:\xampp\php\php.exe s'il existe)
#   MYSQL  exécutable mysql                (défaut : mysql, ou C:\xampp\mysql\bin\mysql.exe)
#   DB_USER / DB_PASS  compte MySQL        (défaut : root, sans mot de passe)
#   SANS_RESET=1  ne recharge pas la base avant les tests

set -u
cd "$(dirname "$0")/.." || exit 1

URL="${URL:-http://localhost:8000/index.php}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-}"
if [ -z "${PHP:-}" ]; then
    if [ -x /c/xampp/php/php.exe ]; then PHP=/c/xampp/php/php.exe; else PHP=php; fi
fi
if [ -z "${MYSQL:-}" ]; then
    if [ -x /c/xampp/mysql/bin/mysql.exe ]; then MYSQL=/c/xampp/mysql/bin/mysql.exe; else MYSQL=mysql; fi
fi
# L'application valide les dates dans le fuseau APP_TIMEZONE. Prendre la date du shell ferait
# échouer les cas « séance du jour » pendant les heures où les deux fuseaux ne sont pas le même jour.
AUJOURDHUI=$("$PHP" -r 'require "core/Env.php"; date_default_timezone_set(Env::lire("APP_TIMEZONE", "Africa/Ndjamena")); echo date("Y-m-d");')
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT

OK=0; KO=0
mysql_q() { if [ -n "$DB_PASS" ]; then "$MYSQL" -u "$DB_USER" "-p$DB_PASS" "$@"; else "$MYSQL" -u "$DB_USER" "$@"; fi; }
sql() { mysql_q formation_humaine_db -N -e "$1"; }
chemin_local() { if command -v cygpath >/dev/null 2>&1; then cygpath -w "$1"; else printf '%s' "$1"; fi; }
verif() { if [ "$2" = "$3" ]; then echo "  ok   $1"; OK=$((OK+1)); else echo "  KO   $1  (obtenu : '$2' ; attendu : '$3')"; KO=$((KO+1)); fi; }
page() { curl -s -b "$TMP/$1.txt" "$URL?action=$2"; }
jeton() { grep -o 'name="jeton" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"//'; }
connexion() { local j; j=$(curl -s -c "$TMP/$1.txt" "$URL?action=login" | jeton); curl -s -b "$TMP/$1.txt" -c "$TMP/$1.txt" -o /dev/null -w "%{redirect_url}" --data-urlencode "jeton=$j" --data-urlencode "identifiant=$2" --data-urlencode "password=$3" "$URL?action=login"; }
connexion_rester() { local j; j=$(curl -s -c "$TMP/$1.txt" "$URL?action=login" | jeton); curl -s -b "$TMP/$1.txt" -c "$TMP/$1.txt" -o /dev/null -w "%{redirect_url}" --data-urlencode "jeton=$j" --data-urlencode "identifiant=$2" --data-urlencode "password=$3" --data-urlencode "rester=1" "$URL?action=login"; }

if [ "${SANS_RESET:-0}" != "1" ]; then
    echo "--- rechargement des données ---"
    find storage -type f ! -name .gitkeep -delete 2>/dev/null
    mysql_q --default-character-set=utf8mb4 < database/schema.sql \
      && mysql_q --default-character-set=utf8mb4 < database/seed.sql \
      && mysql_q --default-character-set=utf8mb4 < database/donnees_test.sql \
      && echo "  base rechargée" || { echo "  échec du rechargement"; exit 1; }
fi

echo "--- syntaxe PHP ---"
ERR=0
for f in core/*.php app/Models/*.php app/Controllers/*.php app/Controllers/Admin/*.php app/Views/etudiant/*.php app/Views/admin/*/*.php app/Views/auth/*.php app/Views/partials/*.php app/Views/site/*.php app/Views/erreur.php public/index.php; do
    "$PHP" -l "$f" | grep -q "No syntax errors" || { echo "  erreur : $f"; ERR=1; }
done
[ $ERR = 0 ] && echo "  aucune erreur de syntaxe"

echo "--- cas de test : espace étudiant ---"
R=$(connexion m CBS2026-0002 'Test1234!'); verif "1  connexion Moussa vers son tableau de bord" "${R##*action=}" "etudiant_dashboard"
verif "1b solde de Moussa 19,75" "$(page m etudiant_dashboard | grep -c '19,75<small>/ 20,00')" "1"
R=$(connexion a CBS2026-0001 'Test1234!'); verif "2  connexion Awa vers son tableau de bord" "${R##*action=}" "etudiant_dashboard"
verif "2b menu délégué pour Awa" "$(page a etudiant_dashboard | grep -c 'action=etudiant_appel')" "1"
verif "2c solde d'Awa plafonné à 20,00" "$(page a etudiant_dashboard | grep -c 'bloquée à 20,00')" "1"
connexion adm admin@formation.local 'Admin123!' >/dev/null
verif "3  administrateur renvoyé vers son tableau de bord" "$(curl -s -b "$TMP/adm.txt" -o /dev/null -w '%{redirect_url}' "$URL?action=etudiant_dashboard")" "$URL?action=admin_dashboard"
verif "4  registre de Moussa : validé par Enock PANDA" "$(page m etudiant_points | grep -c 'Enock PANDA')" "1"
P=$(page m etudiant_presences)
verif "5  absence du 08/09 en attente, pas de nouveau dépôt" "$(echo "$P" | grep -c '>En attente<')" "1"
verif "6  absence du 01/09 : délai dépassé" "$(echo "$P" | grep -c 'Délai dépassé')" "1"
JET=$(echo "$P" | jeton); PRES=$(echo "$P" | grep -o 'name="presence" value="[0-9]*"' | head -1 | grep -o '[0-9]*')
printf '%%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\ntrailer<</Root 1 0 R>>\n%%%%EOF\n' > "$TMP/test.pdf"
curl -s -b "$TMP/m.txt" -o /dev/null -F "jeton=$JET" -F "presence=$PRES" -F "motif=Rendez-vous medical" -F "fichier=@$(chemin_local "$TMP/test.pdf");type=application/pdf" "$URL?action=etudiant_justificatif"
verif "7  dépôt d'un justificatif sur l'absence du jour" "$(sql "SELECT CONCAT(STATUT_VALIDATION, ':', CHEMIN_FICHIER IS NOT NULL) FROM JUSTIFICATION_ABSENCE WHERE ID_PRESENCE=$PRES")" "EN_ATTENTE:1"
ID=$(sql "SELECT MAX(ID_JUSTIFICATION) FROM JUSTIFICATION_ABSENCE")
verif "8  téléchargement par le propriétaire" "$(curl -s -b "$TMP/m.txt" -o /dev/null -w '%{http_code} %{content_type}' "$URL?action=etudiant_fichier&id=$ID")" "200 application/pdf"
verif "8b téléchargement par un autre étudiant refusé" "$(curl -s -b "$TMP/a.txt" -o /dev/null -w '%{http_code}' "$URL?action=etudiant_fichier&id=$ID")" "403"
D=$(page m "etudiant_signalements&id=1"); JET=$(echo "$D" | jeton)
verif "9  dossier de Moussa avec formulaire de réponse" "$(echo "$D" | grep -c 'Enregistrer ma réponse')" "1"
curl -s -b "$TMP/m.txt" -o /dev/null --data-urlencode "jeton=$JET" --data-urlencode "signalement=1" --data-urlencode "reponse=Je m excuse." "$URL?action=etudiant_repondre"
verif "9b réponse enregistrée, statut inchangé" "$(sql "SELECT CONCAT(STATUT, ':', REPONSE_ETUDIANT IS NOT NULL) FROM SIGNALEMENT WHERE ID_SIGNALEMENT=1")" "SOUMIS:1"
verif "10 dossier validé d'Awa sans formulaire" "$(page a "etudiant_signalements&id=2" | grep -c 'Enregistrer ma réponse')" "0"
verif "11 résultats : provisoire seulement" "$(page m etudiant_resultats | grep -c 'Semestre en cours')" "1"
JET=$(page a etudiant_parametres | jeton)
verif "12 mot de passe actuel erroné refusé" "$(curl -s -b "$TMP/a.txt" -L --data-urlencode "jeton=$JET" --data-urlencode "actuel=faux" --data-urlencode "nouveau=Nouveau123!" --data-urlencode "confirmation=Nouveau123!" "$URL?action=etudiant_mot_de_passe" | grep -c 'actuel est incorrect')" "1"
curl -s -b "$TMP/a.txt" -o /dev/null --data-urlencode "jeton=$JET" --data-urlencode "actuel=Test1234!" --data-urlencode "nouveau=Nouveau123!" --data-urlencode "confirmation=Nouveau123!" "$URL?action=etudiant_mot_de_passe"
R=$(connexion a2 CBS2026-0001 'Nouveau123!'); verif "13 reconnexion avec le nouveau mot de passe" "${R##*action=}" "etudiant_dashboard"
JET=$(page a2 etudiant_parametres | jeton); curl -s -b "$TMP/a2.txt" -o /dev/null --data-urlencode "jeton=$JET" --data-urlencode "actuel=Nouveau123!" --data-urlencode "nouveau=Test1234!" --data-urlencode "confirmation=Test1234!" "$URL?action=etudiant_mot_de_passe"
JET=$(page a2 etudiant_appel | jeton)
curl -s -b "$TMP/a2.txt" -o /dev/null --data-urlencode "jeton=$JET" --data-urlencode "titre=TD" --data-urlencode "date=2026-09-12" --data-urlencode "debut=10:00" --data-urlencode "fin=12:00" --data-urlencode "lieu=B2" --data-urlencode "statut[5]=PRESENT" --data-urlencode "statut[6]=ABSENT" "$URL?action=etudiant_appel"
verif "14 appel : séance, appel, 2 présences, aucun point" "$(sql "SELECT CONCAT((SELECT COUNT(*) FROM SEANCE WHERE TITRE_SEANCE='TD'), ':', (SELECT COUNT(*) FROM PRESENCE pr JOIN APPEL a ON a.ID_APPEL=pr.ID_APPEL JOIN SEANCE s ON s.ID_SEANCE=a.ID_SEANCE WHERE s.TITRE_SEANCE='TD'), ':', (SELECT COUNT(*) FROM MOUVEMENT_POINTS))")" "1:2:4"
printf 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==' | base64 -d > "$TMP/preuve.png"
JET=$(page a2 etudiant_signaler | jeton)
curl -s -b "$TMP/a2.txt" -o /dev/null -F "jeton=$JET" -F "etudiant=6" -F "critere=3" -F "titre=Retard repete" -F "date_faits=2026-09-12T09:00" -F "lieu=Salle B2" -F "description=Troisieme retard." -F "preuves[]=@$(chemin_local "$TMP/preuve.png");type=image/png" "$URL?action=etudiant_signaler"
verif "15 signalement créé avec une preuve et son historique" "$(sql "SELECT CONCAT(s.STATUT, ':', COUNT(p.ID_PIECE), ':', (SELECT COUNT(*) FROM SIGNALEMENT_HISTORIQUE h WHERE h.ID_SIGNALEMENT=s.ID_SIGNALEMENT AND h.STATUT='SOUMIS')) FROM SIGNALEMENT s LEFT JOIN PIECE_JUSTIFICATIVE p ON p.ID_SIGNALEMENT=s.ID_SIGNALEMENT WHERE s.TITRE_SIGNALEMENT='Retard repete' GROUP BY s.ID_SIGNALEMENT")" "SOUMIS:1:1"
verif "15b Moussa voit le nouveau dossier" "$(page m etudiant_signalements | grep -c 'Retard repete')" "1"
verif "16 Awa ne peut pas se signaler elle-même" "$(curl -s -b "$TMP/a2.txt" -L -F "jeton=$JET" -F "etudiant=5" -F "critere=3" -F "titre=x" -F "date_faits=2026-09-12T09:00" -F "lieu=x" -F "description=x" "$URL?action=etudiant_signaler" | grep -c 'vous signaler vous-même')" "1"
verif "17 Moussa n'accède pas à l'appel" "$(curl -s -b "$TMP/m.txt" -o /dev/null -w '%{redirect_url}' "$URL?action=etudiant_appel")" "$URL?action=etudiant_dashboard"
verif "18 formulaire sans jeton refusé" "$(curl -s -b "$TMP/m.txt" -o /dev/null -w '%{redirect_url}' --data-urlencode "signalement=1" --data-urlencode "reponse=x" "$URL?action=etudiant_repondre")" "$URL?action=etudiant_dashboard"
printf 'MZ' | cat - "$TMP/test.pdf" > "$TMP/faux.pdf"; sql "DELETE FROM JUSTIFICATION_ABSENCE WHERE ID_JUSTIFICATION=$ID"; JET=$(page m etudiant_presences | jeton)
curl -s -b "$TMP/m.txt" -o /dev/null -F "jeton=$JET" -F "presence=$PRES" -F "motif=x" -F "fichier=@$(chemin_local "$TMP/faux.pdf");type=application/pdf" "$URL?action=etudiant_justificatif"
verif "19 faux PDF refusé" "$(page m etudiant_presences | grep -c 'ne correspond pas à son extension')" "1"
verif "20 page introuvable avec retour à l'accueil" "$(curl -s -b "$TMP/m.txt" -o /dev/null -w '%{http_code}' "$URL?action=nexistepas") $(curl -s -b "$TMP/m.txt" "$URL?action=nexistepas" | grep -c 'etudiant_dashboard')" "404 1"
JET=$(page a2 etudiant_profil | jeton)
curl -s -b "$TMP/a2.txt" -o /dev/null -F "jeton=$JET" -F "photo=@$(chemin_local "$TMP/preuve.png");type=image/png" "$URL?action=etudiant_photo"
verif "21 photo de profil enregistrée" "$(sql "SELECT PHOTO LIKE 'assets/uploads/%' FROM PERSONNE WHERE ID_PERSONNE=5")" "1"
curl -s -b "$TMP/a2.txt" -o /dev/null -F "jeton=$JET" -F "retirer=1" "$URL?action=etudiant_photo"
verif "21b photo retirée" "$(sql "SELECT PHOTO IS NULL FROM PERSONNE WHERE ID_PERSONNE=5")" "1"
verif "22 relevé imprimable" "$(page a2 etudiant_releve | grep -c 'class="releve-entete"')" "1"
verif "23 aucune erreur PHP sur les pages" "$(for p in dashboard points presences signalements resultats club profil parametres releve appel signaler; do page a2 etudiant_$p; done | grep -ci 'warning\|fatal\|notice\|deprecated')" "0"
echo "--- cas de test : espace personnel ---"
connexion enock enock.panda@cbs.local 'Test1234!' >/dev/null; connexion mar marie.tchoua@cbs.local 'Test1234!' >/dev/null
connexion idriss idriss.mahamat@cbs.local 'Test1234!' >/dev/null; connexion sylvie sylvie.ndouba@cbs.local 'Test1234!' >/dev/null
for i in 1 2 3 4 5; do connexion "essai$i" inconnu@cbs.local 'faux' >/dev/null; done
verif "P0  cinq échecs, chacun avec une session neuve, bloquent l'identifiant" "$(connexion essai6 inconnu@cbs.local 'faux' | sed 's/.*erreur=//') $(sql "SELECT COUNT(*) FROM TENTATIVE_CONNEXION WHERE IDENTIFIANT='inconnu@cbs.local'")" "blocage 5"
R=$(connexion enock2 enock.panda@cbs.local 'Test1234!'); verif "P1  connexion du personnel vers son tableau de bord" "${R##*action=}" "admin_dashboard"
verif "P2  droits : Marie refusée sur les justificatifs, admise sur l'appel" "$(curl -s -b "$TMP/mar.txt" -o /dev/null -w '%{http_code}' "$URL?action=admin_justificatifs") $(curl -s -b "$TMP/mar.txt" -o /dev/null -w '%{http_code}' "$URL?action=admin_appel")" "403 200"
verif "P3  Marie ne voit que ses propres signalements" "$(page mar admin_signalements | grep -c 'Perturbation en cours') $(page mar admin_signalements | grep -c 'Participation au nettoyage')" "1 0"
JET=$(page adm admin_etudiant_nouveau | jeton)
curl -s -b "$TMP/adm.txt" -o /dev/null --data-urlencode "jeton=$JET" --data-urlencode "nom=Test" --data-urlencode "prenom=Recette" --data-urlencode "sexe=F" --data-urlencode "date_naissance=2005-01-01" --data-urlencode "email=recette.test@cbs.local" --data-urlencode "telephone=+235 60 00 00 00" --data-urlencode "adresse=" --data-urlencode "id_promo=1" --data-urlencode "id_club=" "$URL?action=admin_etudiant_nouveau"
verif "P4  création d'un étudiant : matricule, compte actif, mot de passe à changer" "$(sql "SELECT CONCAT(MATRICULE LIKE 'CBS2026-%', ':', STATUT_COMPTE, ':', DOIT_CHANGER_MDP) FROM PERSONNE WHERE EMAIL='recette.test@cbs.local'")" "1:ACTIF:1"
IDN=$(sql "SELECT ID_PERSONNE FROM PERSONNE WHERE EMAIL='recette.test@cbs.local'"); MAT=$(sql "SELECT MATRICULE FROM PERSONNE WHERE ID_PERSONNE=$IDN")
MDP=$(page adm "admin_etudiant&id=$IDN" | grep -o 'Mot de passe temporaire de [^<]*<code>[^<]*' | sed 's/.*<code>//')
R=$(connexion rec "$MAT" "$MDP"); verif "P4b première connexion : changement de mot de passe imposé" "${R##*action=}" "premiere_connexion"
JET=$(page mar admin_signalement_nouveau | jeton)
curl -s -b "$TMP/mar.txt" -o /dev/null -F "jeton=$JET" -F "promo=1" -F "etudiants[]=5" -F "etudiants[]=6" -F "critere=4" -F "titre=Tricherie au devoir" -F "date_faits=2026-09-11T10:00" -F "lieu=Salle B2" -F "description=Antiseches trouvees." -F "temoin_nom[]=SARR" -F "temoin_prenom[]=Fatou" -F "temoin_contact[]=" "$URL?action=admin_signalement_nouveau"
verif "P5  signalement collectif : un dossier par étudiant, témoin copié" "$(sql "SELECT CONCAT(COUNT(*), ':', (SELECT COUNT(*) FROM TEMOIN t JOIN SIGNALEMENT s2 ON s2.ID_SIGNALEMENT=t.ID_SIGNALEMENT WHERE s2.TITRE_SIGNALEMENT='Tricherie au devoir')) FROM SIGNALEMENT WHERE TITRE_SIGNALEMENT='Tricherie au devoir'")" "2:2"
D1=$(sql "SELECT MIN(ID_SIGNALEMENT) FROM SIGNALEMENT WHERE TITRE_SIGNALEMENT='Tricherie au devoir'"); JET=$(page mar "admin_signalement&id=$D1" | jeton)
verif "P6  l'auteur ne peut pas instruire son dossier" "$(curl -s -b "$TMP/mar.txt" -o /dev/null -w '%{http_code}' --data-urlencode "jeton=$JET" "$URL?action=admin_signalement_ouvrir&id=$D1")" "403"
JET=$(page enock "admin_signalement&id=$D1" | jeton); curl -s -b "$TMP/enock.txt" -o /dev/null --data-urlencode "jeton=$JET" "$URL?action=admin_signalement_ouvrir&id=$D1"
verif "P7  retrait de points refusé avant audition" "$(curl -s -b "$TMP/enock.txt" -L --data-urlencode "jeton=$JET" --data-urlencode "decision=VALIDE" --data-urlencode "motif=x" "$URL?action=admin_signalement_decider&id=$D1" | grep -c 'alerte-erreur') $(sql "SELECT COUNT(*) FROM MOUVEMENT_POINTS WHERE ID_SIGNALEMENT=$D1")" "1 0"
curl -s -b "$TMP/enock.txt" -o /dev/null --data-urlencode "jeton=$JET" --data-urlencode "date_audition=2026-09-12T09:00" --data-urlencode "notes_audition=Reconnait les faits." "$URL?action=admin_signalement_audition&id=$D1"
curl -s -b "$TMP/enock.txt" -o /dev/null --data-urlencode "jeton=$JET" --data-urlencode "decision=VALIDE" --data-urlencode "motif=Faits etablis." --data-urlencode "conseil=1" "$URL?action=admin_signalement_decider&id=$D1"
verif "P8  audition puis validation avec conseil : deux retraits datés des faits" "$(sql "SELECT CONCAT(s.STATUT, ':', COUNT(m.ID_MOUVEMENT), ':', SUM(m.NOMBRE_POINTS), ':', MIN(DATE(m.DATE_MOUVEMENT))) FROM SIGNALEMENT s JOIN MOUVEMENT_POINTS m ON m.ID_SIGNALEMENT=s.ID_SIGNALEMENT WHERE s.ID_SIGNALEMENT=$D1")" "VALIDE:2:5.00:2026-09-11"
verif "P8b seconde décision refusée" "$(curl -s -b "$TMP/enock.txt" -L --data-urlencode "jeton=$JET" --data-urlencode "decision=REJETE" --data-urlencode "motif=x" "$URL?action=admin_signalement_decider&id=$D1" | grep -c 'décision a déjà été rendue')" "1"
JET=$(page sylvie admin_signalement_nouveau | jeton)
curl -s -b "$TMP/sylvie.txt" -o /dev/null -F "jeton=$JET" -F "promo=2" -F "etudiants[]=7" -F "critere=22" -F "titre=Concours de plaidoirie" -F "date_faits=2026-09-11T10:00" -F "lieu=Amphi" -F "description=Participation."  "$URL?action=admin_signalement_nouveau"
D2=$(sql "SELECT ID_SIGNALEMENT FROM SIGNALEMENT WHERE TITRE_SIGNALEMENT='Concours de plaidoirie'"); JET=$(page enock "admin_signalement&id=$D2" | jeton)
verif "P9  bonification au-delà du plafond refusée" "$(curl -s -b "$TMP/enock.txt" -L --data-urlencode "jeton=$JET" --data-urlencode "decision=VALIDE" --data-urlencode "motif=x" "$URL?action=admin_signalement_decider&id=$D2" | grep -c 'Plafond du domaine') $(sql "SELECT STATUT FROM SIGNALEMENT WHERE ID_SIGNALEMENT=$D2")" "1 SOUMIS"
JET=$(page enock admin_justificatifs | jeton); IDJ=$(sql "SELECT ID_JUSTIFICATION FROM JUSTIFICATION_ABSENCE WHERE STATUT_VALIDATION='EN_ATTENTE' ORDER BY ID_JUSTIFICATION LIMIT 1")
verif "P10 rejet d'un justificatif sans commentaire refusé" "$(curl -s -b "$TMP/enock.txt" -L --data-urlencode "jeton=$JET" --data-urlencode "id=$IDJ" --data-urlencode "decision=rejeter" --data-urlencode "commentaire=" "$URL?action=admin_justificatif_decider" | grep -c 'pourquoi le justificatif est rejeté')" "1"
curl -s -b "$TMP/enock.txt" -o /dev/null --data-urlencode "jeton=$JET" --data-urlencode "id=$IDJ" --data-urlencode "decision=valider" --data-urlencode "commentaire=Recevable." "$URL?action=admin_justificatif_decider"
verif "P11 justificatif validé : absence justifiée, visible par l'étudiant" "$(sql "SELECT CONCAT(j.STATUT_VALIDATION, ':', p.STATUT) FROM JUSTIFICATION_ABSENCE j JOIN PRESENCE p ON p.ID_PRESENCE=j.ID_PRESENCE WHERE j.ID_JUSTIFICATION=$IDJ") $(page m etudiant_presences | grep -c 'Recevable.')" "VALIDEE:ABSENT_JUSTIFIE 1"
JET=$(page idriss admin_appel | jeton)
verif "P12 un responsable de club ne fait pas l'appel d'une promotion" "$(curl -s -b "$TMP/idriss.txt" -L --data-urlencode "jeton=$JET" --data-urlencode "promo=1" --data-urlencode "club=0" --data-urlencode "seance=0" --data-urlencode "titre=x" --data-urlencode "date=2026-09-12" --data-urlencode "debut=08:00" --data-urlencode "fin=10:00" --data-urlencode "lieu=x" "$URL?action=admin_appel" | grep -c 'que pour les clubs que vous animez')" "1"
IDS=$(sql "SELECT ID_SEANCE FROM SEANCE WHERE TITRE_SEANCE='Sortie reboisement'")
verif "P12b appel d'une séance à venir refusé" "$(curl -s -b "$TMP/idriss.txt" -L --data-urlencode "jeton=$JET" --data-urlencode "seance=$IDS" --data-urlencode "club=1" --data-urlencode "statut[6]=PRESENT" "$URL?action=admin_appel" | grep -c 'pas encore eu lieu')" "1"
JET=$(page enock admin_appel | jeton)
curl -s -b "$TMP/enock.txt" -o /dev/null --data-urlencode "jeton=$JET" --data-urlencode "promo=1" --data-urlencode "club=0" --data-urlencode "seance=0" --data-urlencode "titre=Cours du jour" --data-urlencode "date=$AUJOURDHUI" --data-urlencode "debut=08:00" --data-urlencode "fin=10:00" --data-urlencode "lieu=A1" --data-urlencode "statut[5]=PRESENT" "$URL?action=admin_appel"
IDS=$(sql "SELECT ID_SEANCE FROM SEANCE WHERE TITRE_SEANCE='Cours du jour'")
verif "P12c séance datée du jour acceptée, second appel sur la même séance refusé" "$(sql "SELECT COUNT(*) FROM APPEL WHERE ID_SEANCE=${IDS:-0}") $(curl -s -b "$TMP/enock.txt" -L --data-urlencode "jeton=$JET" --data-urlencode "promo=1" --data-urlencode "club=0" --data-urlencode "seance=${IDS:-0}" --data-urlencode "statut[5]=ABSENT" "$URL?action=admin_appel" | grep -c 'a déjà été enregistré') $(sql "SELECT COUNT(*) FROM APPEL WHERE ID_SEANCE=${IDS:-0}")" "1 1 1"
A=$(page enock admin_assiduite); JET=$(echo "$A" | jeton); IDS_P=$(echo "$A" | grep -o 'name="presences\[\]" value="[0-9]*"' | grep -o '[0-9]*' | paste -sd,)
ARGS=""; for i in $(echo "$IDS_P" | tr ',' ' '); do ARGS="$ARGS --data-urlencode presences[]=$i"; done
curl -s -b "$TMP/enock.txt" -o /dev/null --data-urlencode "jeton=$JET" $ARGS "$URL?action=admin_assiduite_penaliser"
verif "P13 pénalités d'assiduité appliquées une seule fois par présence" "$(sql "SELECT COUNT(*) FROM MOUVEMENT_POINTS WHERE ID_PRESENCE IS NOT NULL") $(curl -s -b "$TMP/enock.txt" -L --data-urlencode "jeton=$JET" $ARGS "$URL?action=admin_assiduite_penaliser" | grep -c '0 pénalité(s) appliquée(s)')" "$(echo "$IDS_P" | tr ',' '\n' | grep -c .) 1"
IDM=$(sql "SELECT ID_MOUVEMENT FROM MOUVEMENT_POINTS WHERE TYPE_MOUVEMENT='NEGATIF' ORDER BY ID_MOUVEMENT LIMIT 1"); JET=$(page adm admin_points | jeton)
curl -s -b "$TMP/adm.txt" -o /dev/null --data-urlencode "jeton=$JET" --data-urlencode "mouvement=$IDM" --data-urlencode "motif=Erreur de saisie" "$URL?action=admin_point_corriger"
verif "P14 correction par écriture inverse, une seule fois" "$(sql "SELECT CONCAT(TYPE_MOUVEMENT, ':', NOMBRE_POINTS) FROM MOUVEMENT_POINTS WHERE ID_MOUVEMENT_CORRIGE=$IDM") $(curl -s -b "$TMP/adm.txt" -L --data-urlencode "jeton=$JET" --data-urlencode "mouvement=$IDM" --data-urlencode "motif=x" "$URL?action=admin_point_corriger" | grep -c 'déjà été corrigé')" "POSITIF:0.25 1"
verif "P15 export CSV du registre avec BOM et point-virgule" "$(page adm admin_points_export | head -c 3 | od -An -tx1 | tr -d ' ') $(page adm admin_points_export | head -1 | grep -c 'Date;Matricule')" "efbbbf 1"
JET=$(page adm admin_structure | jeton)
curl -s -b "$TMP/adm.txt" -o /dev/null --data-urlencode "jeton=$JET" --data-urlencode "semestre=1" "$URL?action=admin_semestre_cloturer"
NB=$(sql "SELECT COUNT(*) FROM ETUDIANT e JOIN PERSONNE p ON p.ID_PERSONNE=e.ID_PERSONNE WHERE p.STATUT_COMPTE='ACTIF'")
verif "P16 clôture : notes figées, mention, étudiant informé" "$(sql "SELECT CONCAT(COUNT(*), ':', SUM(STATUT_VALIDATION='CLOTURE'), ':', SUM(MENTION IS NOT NULL)) FROM RESULTAT_SEMESTRIEL WHERE ID_SEMESTRE=1") $(page m etudiant_resultats | grep -c 'Semestre en cours')" "$NB:$NB:$NB 0"
verif "P16b écriture refusée sur un semestre clôturé" "$(curl -s -b "$TMP/adm.txt" -L --data-urlencode "jeton=$JET" --data-urlencode "mouvement=3" --data-urlencode "motif=x" "$URL?action=admin_point_corriger" | grep -c 'est clôturé')" "1"
curl -s -b "$TMP/adm.txt" -o /dev/null --data-urlencode "jeton=$JET" --data-urlencode "semestre=1" "$URL?action=admin_semestre_rouvrir"
verif "P16c réouverture" "$(sql "SELECT DISTINCT STATUT_VALIDATION FROM RESULTAT_SEMESTRIEL WHERE ID_SEMESTRE=1")" "PROVISOIRE"
verif "P17 suppression d'une promotion rattachée refusée" "$(curl -s -b "$TMP/adm.txt" -L --data-urlencode "jeton=$JET" --data-urlencode "op=supprimer" --data-urlencode "id=1" "$URL?action=admin_structure&entite=promotion" | grep -c 'Suppression impossible')" "1"
JET=$(page adm admin_clubs | jeton)
curl -s -b "$TMP/adm.txt" -o /dev/null --data-urlencode "jeton=$JET" --data-urlencode "nom=Club Recette" --data-urlencode "description=" --data-urlencode "responsable=3" "$URL?action=admin_club_enregistrer"
connexion mar2 marie.tchoua@cbs.local 'Test1234!' >/dev/null
verif "P18 une enseignante désignée responsable accède à son club et garde l'appel des promotions" "$(page mar2 admin_clubs | grep -c 'Club Recette') $(curl -s -b "$TMP/mar2.txt" -o /dev/null -w '%{http_code}' "$URL?action=admin_club&id=1") $(page mar2 admin_appel | grep -c 'value="promo:1"')" "1 403 1"
JET=$(page adm admin_bareme | jeton)
curl -s -b "$TMP/adm.txt" -o /dev/null --data-urlencode "jeton=$JET" --data-urlencode "valeur[PLAFOND_BONUS_ECOLOGIE]=4" "$URL?action=admin_bareme_parametres"
verif "P19 paramètre modifié et pris en compte" "$(sql "SELECT VALEUR FROM PARAMETRE_SYSTEME WHERE CODE_PARAMETRE='PLAFOND_BONUS_ECOLOGIE'") $(curl -s -b "$TMP/adm.txt" -L --data-urlencode "jeton=$JET" --data-urlencode "valeur[PLAFOND_BONUS_ECOLOGIE]=-1" "$URL?action=admin_bareme_parametres" | grep -c 'nombre positif')" "4 1"
JET=$(page adm admin_comptes | jeton)
curl -s -b "$TMP/adm.txt" -o /dev/null --data-urlencode "jeton=$JET" --data-urlencode "nom=Bemba" --data-urlencode "prenom=Paul" --data-urlencode "email=paul.bemba@cbs.local" --data-urlencode "telephone=+235 60 00 00 09" --data-urlencode "role=4" --data-urlencode "sexe=M" "$URL?action=admin_compte_enregistrer"
MDP=$(page adm admin_comptes | grep -o '<code class="mdp">[^<]*' | sed 's/.*>//')
R=$(connexion paul paul.bemba@cbs.local "$MDP"); verif "P20 compte du personnel créé avec matricule, mot de passe temporaire, première connexion" "$(sql "SELECT CONCAT(NOM, ':', DOIT_CHANGER_MDP, ':', MATRICULE LIKE 'PER-%') FROM PERSONNE WHERE EMAIL='paul.bemba@cbs.local'") ${R##*action=}" "BEMBA:1:1 premiere_connexion"
verif "P20b l'administrateur ne peut pas se désactiver" "$(curl -s -b "$TMP/adm.txt" -L --data-urlencode "jeton=$JET" --data-urlencode "id=1" "$URL?action=admin_compte_statut" | grep -c 'votre propre compte')" "1"
verif "P21 rapports et exports" "$(page adm admin_rapports | grep -c 'Répartition des mentions') $(curl -s -b "$TMP/adm.txt" "$URL?action=admin_rapports_export&type=soldes" | head -1 | grep -c 'Matricule;Nom')" "1 1"
verif "P22 journal : actions attribuées, accès réservé" "$(page adm admin_journal | grep -c 'badge-bleu') $(curl -s -b "$TMP/enock.txt" -o /dev/null -w '%{http_code}' "$URL?action=admin_journal")" "$(page adm admin_journal | grep -c 'badge-bleu') 403"
verif "P23 aucune erreur PHP sur les pages du personnel" "$(for p in admin_dashboard admin_etudiants "admin_etudiant&id=6" admin_etudiant_nouveau admin_etudiants_import admin_signalements admin_signalement_nouveau "admin_signalement&id=$D1" admin_appel admin_seances admin_justificatifs admin_assiduite admin_points "admin_structure&entite=semestre" admin_clubs "admin_club&id=1" admin_bareme admin_comptes admin_mon_compte admin_rapports admin_journal; do page adm "$p"; done | grep -ci 'warning\|fatal\|notice\|deprecated')" "0"
echo "--- cas de test : site vitrine et reconnexion ---"
SITE="${URL%/index.php}"
H=$(curl -s -D "$TMP/v1h.txt" -o "$TMP/v1b.txt" -w '%{http_code}' "$SITE/")
verif "V1  accueil public : 200, devise affichée, aucun cookie déposé" "$H $(grep -q 'Excellence' "$TMP/v1b.txt" && echo devise || echo -) $(grep -ci '^set-cookie' "$TMP/v1h.txt")" "200 devise 0"
V2=""; for s in "vie-etudiante|Vie étudiante|Les quatre domaines" "formations|Formations|Un cursus LMD" "admission|Admission|Trois étapes" "contact|Contact|nous écrire" "formation-humaine|Vie étudiante|Les quatre domaines"; do IFS='|' read -r SLUG TITRE CORPS <<< "$s"; V2="$V2 $(curl -s -o "$TMP/p.txt" -w '%{http_code}' "$SITE/$SLUG")$(grep -o '<title>[^<]*' "$TMP/p.txt" | grep -q "$TITRE" && echo t || echo -)$(grep -q "$CORPS" "$TMP/p.txt" && echo c || echo -)"; done
verif "V2  pages du site (titre et contenu) et alias formation-humaine" "${V2# }" "200tc 200tc 200tc 200tc 200tc"
verif "V3  adresse inconnue en 404, barre finale redirigée" "$(curl -s -o /dev/null -w '%{http_code}' "$SITE/nexistepas") $(curl -s -o /dev/null -w '%{http_code} %{redirect_url}' "$SITE/formations/")" "404 301 $SITE/formations"
A=$(curl -s "$SITE/"); M=$(curl -s -b "$TMP/m.txt" "$SITE/")
verif "V4  en-tête : Se connecter pour un visiteur, Mon espace de Moussa vers son tableau de bord" "$(echo "$A" | grep -q 'Se connecter' && echo oui || echo non) $(echo "$A" | grep -c 'etudiant_dashboard') $(echo "$M" | grep -q 'Mon espace' && echo oui || echo non) $(echo "$M" | grep -q 'action=etudiant_dashboard' && echo oui || echo non)" "oui 0 oui oui"
verif "V5  aucune erreur PHP sur les pages du site" "$(for s in '' vie-etudiante formations admission contact; do curl -s "$SITE/$s"; done | grep -ci 'warning\|fatal\|notice\|deprecated')" "0"
F=$(curl -s -c "$TMP/v.txt" -b "$TMP/v.txt" "$SITE/contact"); JET=$(echo "$F" | jeton)
verif "V6  formulaire de contact : jeton, champ piège, quatre objets" "$([ -n "$JET" ] && echo jeton) $(echo "$F" | grep -c 'name="site_web"') $(echo "$F" | grep -c 'name="objet"')" "jeton 1 4"
R=$(curl -s -c "$TMP/v.txt" -b "$TMP/v.txt" -L --data-urlencode "jeton=$JET" --data-urlencode "nom=" --data-urlencode "email=pas-un-email" --data-urlencode "objet=admission" --data-urlencode "message=" --data-urlencode "site_web=" "$SITE/contact")
verif "V7  champs manquants ou invalides : une erreur par champ" "$(echo "$R" | grep -c 'class="champ-erreur"')" "3"
R=$(curl -s -c "$TMP/v.txt" -b "$TMP/v.txt" -L --data-urlencode "nom=Test" --data-urlencode "email=a@b.td" --data-urlencode "objet=admission" --data-urlencode "message=Un message sans jeton." "$SITE/contact")
verif "V8  envoi sans jeton refusé" "$(echo "$R" | grep -c 'Le formulaire a expiré')" "1"
JET=$(curl -s -c "$TMP/v.txt" -b "$TMP/v.txt" "$SITE/contact" | jeton)
R=$(curl -s -c "$TMP/v.txt" -b "$TMP/v.txt" -L --data-urlencode "jeton=$JET" --data-urlencode "nom=Mahamat Idriss" --data-urlencode "email=parent@exemple.td" --data-urlencode "telephone=+235 66 00 00 00" --data-urlencode "objet=admission" --data-urlencode "message=Bonjour, quelles sont les dates du concours cette année ?" --data-urlencode "site_web=" "$SITE/contact")
verif "V9  message valide, envoi pas encore branché : avis clair, texte conservé" "$(echo "$R" | grep -c 'pas encore en service') $(echo "$R" | grep -c 'quelles sont les dates du concours')" "1 1"
JET=$(curl -s -c "$TMP/v.txt" -b "$TMP/v.txt" "$SITE/contact" | jeton)
R=$(curl -s -c "$TMP/v.txt" -b "$TMP/v.txt" -L --data-urlencode "jeton=$JET" --data-urlencode "nom=" --data-urlencode "email=" --data-urlencode "objet=autre" --data-urlencode "message=" --data-urlencode "site_web=http://spam.example" "$SITE/contact")
verif "V10 champ piège rempli : message écarté sans erreur affichée" "$(echo "$R" | grep -c 'class="champ-erreur"') $(echo "$R" | grep -c 'Message reçu')" "0 1"
R=$(connexion_rester r1 CBS2026-0003 'Test1234!')
verif "R1  « rester connecté » : cookie de reconnexion et jeton en base ; rien sans la case" "${R##*action=} $(grep -c 'reconnexion' "$TMP/r1.txt") $(sql "SELECT COUNT(*) FROM JETON_CONNEXION WHERE ID_PERSONNE=7") $(grep -c 'reconnexion' "$TMP/a2.txt")" "etudiant_dashboard 1 1 0"
V1=$(awk '$6=="reconnexion"{print $7}' "$TMP/r1.txt")
C=$(curl -s -b "reconnexion=$V1" -c "$TMP/r2.txt" -o /dev/null -w '%{http_code}' "$URL?action=etudiant_dashboard")
V2=$(awk '$6=="reconnexion"{print $7}' "$TMP/r2.txt")
verif "R2  reprise de session par le cookie seul, jeton renouvelé" "$C $([ -n "$V2" ] && [ "$V2" != "$V1" ] && echo renouvele || echo identique) $(sql "SELECT DATE_UTILISATION IS NOT NULL FROM JETON_CONNEXION WHERE ID_PERSONNE=7")" "200 renouvele 1"
curl -s -b "$TMP/r2.txt" -c "$TMP/r2.txt" -o /dev/null "$URL?action=logout"
verif "R3  déconnexion : jeton supprimé et cookie effacé" "$(sql "SELECT COUNT(*) FROM JETON_CONNEXION WHERE ID_PERSONNE=7") $(awk '$6=="reconnexion"{print $7}' "$TMP/r2.txt" | grep -c .)" "0 0"
connexion_rester r4 CBS2026-0003 'Test1234!' >/dev/null
V1=$(awk '$6=="reconnexion"{print $7}' "$TMP/r4.txt")
curl -s -b "reconnexion=$V1" -o /dev/null "$URL?action=etudiant_dashboard"
sql "UPDATE JETON_CONNEXION SET DATE_UTILISATION = DATE_UTILISATION - INTERVAL 2 MINUTE WHERE ID_PERSONNE=7"
R=$(curl -s -b "reconnexion=$V1" -o /dev/null -w '%{redirect_url}' "$URL?action=etudiant_dashboard")
verif "R4  ancien jeton rejoué après renouvellement : vol présumé, tous les jetons révoqués" "${R##*action=} $(sql "SELECT COUNT(*) FROM JETON_CONNEXION WHERE ID_PERSONNE=7")" "login 0"
connexion_rester r5 CBS2026-0003 'Test1234!' >/dev/null
sql "UPDATE JETON_CONNEXION SET DATE_EXPIRATION = NOW() - INTERVAL 1 DAY WHERE ID_PERSONNE=7"
V1=$(awk '$6=="reconnexion"{print $7}' "$TMP/r5.txt")
R=$(curl -s -b "reconnexion=$V1" -o /dev/null -w '%{redirect_url}' "$URL?action=etudiant_dashboard")
verif "R5  jeton expiré : refusé et purgé" "${R##*action=} $(sql "SELECT COUNT(*) FROM JETON_CONNEXION WHERE ID_PERSONNE=7")" "login 0"
connexion_rester r6 CBS2026-0003 'Test1234!' >/dev/null
JET=$(page r6 etudiant_parametres | jeton)
curl -s -b "$TMP/r6.txt" -o /dev/null --data-urlencode "jeton=$JET" --data-urlencode "actuel=Test1234!" --data-urlencode "nouveau=Nouveau123!" --data-urlencode "confirmation=Nouveau123!" "$URL?action=etudiant_mot_de_passe"
verif "R6  changement de mot de passe : appareils mémorisés déconnectés" "$(sql "SELECT COUNT(*) FROM JETON_CONNEXION WHERE ID_PERSONNE=7")" "0"
JET=$(page r6 etudiant_parametres | jeton); curl -s -b "$TMP/r6.txt" -o /dev/null --data-urlencode "jeton=$JET" --data-urlencode "actuel=Nouveau123!" --data-urlencode "nouveau=Test1234!" --data-urlencode "confirmation=Test1234!" "$URL?action=etudiant_mot_de_passe"
connexion_rester r7 CBS2026-0003 'Test1234!' >/dev/null
V1=$(awk '$6=="reconnexion"{print $7}' "$TMP/r7.txt")
C1=$(curl -s -b "reconnexion=$V1" -o /dev/null -w '%{http_code}' "$URL?action=etudiant_dashboard")
C2=$(curl -s -b "reconnexion=$V1" -o /dev/null -w '%{http_code}' "$URL?action=etudiant_dashboard")
verif "R7  deux requêtes simultanées avec le même cookie : pas de faux vol, appareil conservé" "$C1 $C2 $(sql "SELECT COUNT(*) FROM JETON_CONNEXION WHERE ID_PERSONNE=7")" "200 200 1"
echo "--- résultat : $OK ok, $KO ko ---"
[ $KO = 0 ]
