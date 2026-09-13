#!/usr/bin/env bash
# Recette de l'espace étudiant : rejoue les cas de documentation/espace-etudiant.md
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
for f in core/*.php app/Models/*.php app/Controllers/*.php app/Views/etudiant/*.php app/Views/partials/*.php app/Views/erreur.php public/index.php; do
    "$PHP" -l "$f" | grep -q "No syntax errors" || { echo "  erreur : $f"; ERR=1; }
done
[ $ERR = 0 ] && echo "  aucune erreur de syntaxe"

echo "--- cas de test ---"
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
verif "15 signalement créé avec une preuve" "$(sql "SELECT CONCAT(s.STATUT, ':', COUNT(p.ID_PIECE)) FROM SIGNALEMENT s LEFT JOIN PIECE_JUSTIFICATIVE p ON p.ID_SIGNALEMENT=s.ID_SIGNALEMENT WHERE s.TITRE_SIGNALEMENT='Retard repete' GROUP BY s.ID_SIGNALEMENT")" "SOUMIS:1"
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
echo "--- résultat : $OK ok, $KO ko ---"
[ $KO = 0 ]
