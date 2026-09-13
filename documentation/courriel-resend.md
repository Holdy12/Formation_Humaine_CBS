# Envoi de courriels avec Resend

L'application n'envoie aujourd'hui aucun courriel : les mots de passe temporaires sont remis de
la main à la main et la réinitialisation passe par l'administration. Ce guide décrit comment
brancher l'envoi de courriels avec [Resend](https://resend.com), en restant dans le style du
projet : PHP simple, aucune bibliothèque à installer, aucun gestionnaire de dépendances.

Resend est retenu parce que son API est une simple requête HTTP (pas de configuration SMTP à
maintenir), que l'offre gratuite couvre largement les besoins de l'école (100 courriels par jour,
3 000 par mois) et que la remise des messages est traçable depuis leur tableau de bord.

## 1. Créer le compte et vérifier le domaine

1. Créer un compte sur `resend.com`.
2. Dans **Domains**, ajouter le domaine de l'école (par exemple `cbs.td`).
3. Resend affiche trois enregistrements DNS à créer chez l'hébergeur du domaine :
   - un enregistrement `MX` pour le sous-domaine d'envoi ;
   - un `TXT` **SPF** ;
   - un `TXT` **DKIM**.
   Le service informatique de l'école ou le prestataire qui gère le domaine les ajoute.
4. Attendre que Resend affiche le domaine comme **Verified** (quelques minutes à quelques heures).
5. Dans **API Keys**, créer une clé avec la permission *Sending access* et la copier : elle n'est
   affichée qu'une fois.

Tant que le domaine n'est pas vérifié, Resend n'autorise l'envoi que vers l'adresse du compte,
et depuis `onboarding@resend.dev`. C'est suffisant pour développer.

## 2. Configuration dans le projet

Ajouter les constantes dans `config/database.php`, à côté de `BASE_URL` :

```php
// Envoi de courriels (Resend). Laisser COURRIEL_ACTIF à false pour désactiver tout envoi.
define('COURRIEL_ACTIF', true);
define('RESEND_CLE_API', 're_xxxxxxxxxxxxxxxxxxxxxxxx');
define('COURRIEL_EXPEDITEUR', 'Formation Humaine CBS <formation-humaine@cbs.td>');
define('COURRIEL_REPONSE', 'formation-humaine@cbs.td');
```

`config/database.php` est ignoré par Git (voir `.gitignore`) : la clé n'est jamais versionnée.
Chaque poste et le serveur ont leur propre fichier. Ne jamais écrire la clé dans un fichier suivi
par Git, ni dans une vue.

Vérifier aussi que l'extension `curl` est active dans `php.ini` (`extension=curl`).

## 3. La classe d'envoi

Créer `core/Courriel.php` :

```php
<?php
// core/Courriel.php — envoi de courriels via l'API Resend.
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Journal.php';

class Courriel {
    private const URL = 'https://api.resend.com/emails';

    // Retourne true si le courriel est accepté par Resend, false sinon (jamais d'exception :
    // un envoi qui échoue ne doit pas interrompre l'action de l'utilisateur).
    public static function envoyer(string $destinataire, string $sujet, string $titre, string $corpsHtml, ?string $lien = null, ?string $libelleLien = null): bool {
        if (!defined('COURRIEL_ACTIF') || !COURRIEL_ACTIF) {
            return false;
        }
        $charge = [
            'from'     => COURRIEL_EXPEDITEUR,
            'to'       => [$destinataire],
            'reply_to' => COURRIEL_REPONSE,
            'subject'  => $sujet,
            'html'     => self::gabarit($titre, $corpsHtml, $lien, $libelleLien),
        ];
        $ch = curl_init(self::URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . RESEND_CLE_API, 'Content-Type: application/json'],
            CURLOPT_POSTFIELDS     => json_encode($charge, JSON_UNESCAPED_UNICODE),
        ]);
        $reponse = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code >= 200 && $code < 300) {
            Journal::ecrire('Courriel', 'Envoyé à ' . $destinataire . ' : ' . $sujet);
            return true;
        }
        error_log('Resend (' . $code . ') : ' . $reponse);
        Journal::ecrire('Courriel', 'Échec vers ' . $destinataire . ' : ' . $sujet, 'ECHEC');
        return false;
    }

    // Gabarit HTML commun : en-tête orange, corps, bouton facultatif, pied de page.
    private static function gabarit(string $titre, string $corps, ?string $lien, ?string $libelleLien): string {
        $bouton = '';
        if ($lien && $libelleLien) {
            $bouton = '<p style="margin:28px 0"><a href="' . htmlspecialchars($lien) . '"'
                . ' style="background:#ff7f00;color:#ffffff;text-decoration:none;padding:12px 22px;border-radius:8px;font-weight:600;display:inline-block">'
                . htmlspecialchars($libelleLien) . '</a></p>';
        }
        return '<div style="font-family:Segoe UI,Arial,sans-serif;background:#f4f6f9;padding:24px">'
             . '<div style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #eaeaea">'
             . '<div style="background:#1a1a1a;color:#ffffff;padding:20px 24px;border-bottom:4px solid #ff7f00">'
             . '<strong style="font-size:15px">Formation Humaine CBS</strong></div>'
             . '<div style="padding:24px;color:#212529;font-size:14px;line-height:1.6">'
             . '<h1 style="font-size:19px;margin:0 0 14px">' . htmlspecialchars($titre) . '</h1>'
             . $corps . $bouton
             . '</div>'
             . '<div style="padding:16px 24px;background:#f4f6f9;color:#666666;font-size:12px">'
             . 'Message automatique, merci de ne pas y répondre directement.</div>'
             . '</div></div>';
    }
}
```

## 4. Où brancher les envois

Les points d'accroche existent déjà : chaque événement à notifier écrit aussi une entrée dans le
journal d'audit. Ajouter l'appel à `Courriel::envoyer()` juste après.

| Événement | Fichier | Contenu du message |
|---|---|---|
| Création d'un compte | `Admin/EtudiantController::nouveau()`, `Admin/CompteController::nouveau()` | identifiants de connexion et invitation à changer le mot de passe |
| Réinitialisation du mot de passe | `Admin/EtudiantController::reinitialiser()` | mot de passe temporaire, valable jusqu'à la première connexion |
| Convocation à une audition | instruction d'un signalement | date, heure et lieu de l'audition |
| Décision rendue | validation ou rejet d'un signalement | critère, décision, points appliqués, lien vers le dossier |
| Justificatif traité | validation ou rejet d'un justificatif | séance concernée, décision, commentaire |
| Clôture d'un semestre | `Admin/StructureController::cloturer()` | note finale et mention |

Exemple, après la réinitialisation d'un mot de passe :

```php
$motDePasse = Personne::reinitialiser($id);
Courriel::envoyer(
    $etudiant['EMAIL'],
    'Votre mot de passe a été réinitialisé',
    'Nouveau mot de passe temporaire',
    '<p>Bonjour ' . htmlspecialchars($etudiant['PRENOM']) . ',</p>'
    . '<p>Votre mot de passe a été réinitialisé par l\'administration. Connectez-vous avec le mot de passe '
    . 'temporaire <strong>' . htmlspecialchars($motDePasse) . '</strong> ; il vous sera demandé d\'en choisir '
    . 'un nouveau immédiatement.</p>',
    BASE_URL . '/index.php?action=login',
    'Se connecter'
);
```

## 5. Réinitialisation autonome du mot de passe

Une fois l'envoi en place, la page « Mot de passe oublié » peut devenir autonome. Prévoir :

1. Une table `REINITIALISATION_MDP` : `ID_PERSONNE`, `JETON_HASH` (le jeton est stocké haché,
   comme un mot de passe), `DATE_EXPIRATION` (une heure), `UTILISE`.
2. Le formulaire demande l'adresse email et affiche **toujours** le même message de confirmation,
   que le compte existe ou non, pour ne pas révéler quels comptes sont enregistrés.
3. Si le compte existe et qu'il est actif : générer `bin2hex(random_bytes(32))`, en stocker le
   hachage, envoyer le lien `BASE_URL . '/index.php?action=reinitialiser&jeton=' . $jeton`.
4. La page de réinitialisation vérifie le jeton (non expiré, non utilisé), demande le nouveau mot
   de passe, met à jour le compte, marque le jeton comme utilisé et écrit dans le journal.
5. Limiter les demandes : une par adresse et par quart d'heure.

## 6. Vérifications

- Envoyer un message de test depuis un script :
  `php -r "require 'core/Courriel.php'; var_dump(Courriel::envoyer('votre@adresse.test', 'Test', 'Test', '<p>Bonjour.</p>'));"`
- Le tableau de bord Resend liste chaque envoi avec son statut (remis, rejeté, plainte).
- `COURRIEL_ACTIF = false` doit désactiver tous les envois sans provoquer d'erreur.
- Une clé invalide ne doit jamais bloquer l'application : l'action se termine normalement et
  l'échec apparaît dans le journal.

## 7. Précautions

- Ne jamais envoyer de pièce justificative ni de détail disciplinaire par courriel : le message
  annonce la décision et renvoie vers l'application, où les droits d'accès s'appliquent.
- Un mot de passe temporaire envoyé par courriel doit obligatoirement être changé à la première
  connexion, ce que l'application impose déjà (`DOIT_CHANGER_MDP`).
- Vérifier l'adresse avant l'envoi (`filter_var($email, FILTER_VALIDATE_EMAIL)`).
- Prévoir que l'école puisse désactiver complètement les notifications depuis
  `PARAMETRE_SYSTEME` si elle le souhaite.
