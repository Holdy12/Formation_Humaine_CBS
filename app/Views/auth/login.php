<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Formation Humaine CBS</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: Arial, sans-serif; }
        body { display: flex; min-height: 100vh; background-color: #f4f4f4; }
        
        /* Colonne gauche (Noir & Orange) */
        .left-pane {
            width: 45%;
            background-color: #1a1a1a;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 40px;
            border-right: 5px solid #ff7f00;
        }
        .logo-box {
            background: white;
            padding: 10px;
            text-align: center;
            border-radius: 5px;
        }
        .logo-box img {
            max-width: 100%;
            height:160px;
            object-fit: contain;
            display: block;
            margin: 0 auto;
        }
        .left-content {
            text-align: center;
        }
        .left-content h1 { font-size: 24px; margin-bottom: 15px; color: #ff7f00; }
        .left-content p { font-size: 14px; line-height: 1.5; color: #ccc; }
        .footer-text { font-size: 12px; color: #777; text-align: center; }

        /* Colonne droite (Formulaire Blanc) */
        .right-pane {
            width: 55%;
            background: white;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
        }
        .login-card h2 { text-align: center; margin-bottom: 5px; font-size: 22px; letter-spacing: 1px; }
        .login-card p { text-align: center; font-size: 12px; color: #666; margin-bottom: 30px; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #333; }
        
        /* Conteneur pour l'input et son icône */
        .input-container {
            position: relative;
            display: flex;
            align-items: center;
        }
        .input-container svg {
            position: absolute;
            left: 12px;
            width: 20px;
            height: 20px;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 12px 12px 42px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
        }
        .form-control:focus { border-color: #ff7f00; outline: none; }
        
        /* Style pour le bouton toggle du mot de passe */
        .toggle-password {
            position: absolute;
            right: 12px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 12px;
            color: #666;
            font-weight: bold;
        }
        .toggle-password:hover { color: #ff7f00; }

        /* Style pour l'alerte d'erreur dynamique */
        .alert-error {
            background-color: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 4px;
            font-size: 12px;
            margin-bottom: 20px;
            text-align: center;
            display: none;
            border: 1px solid #ef9a9a;
        }
        
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            margin-bottom: 25px;
        }
        .form-options a { color: #666; text-decoration: none; }
        .form-options a:hover { text-decoration: underline; }

        .btn-submit {
            width: 100%;
            padding: 12px;
            background-color: #ff7f00;
            border: none;
            color: white;
            font-weight: bold;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.2s;
             border: 1px solid #f0400f;
        }
        .btn-submit:hover { 
            background-color: #e57200;  
        }

        /* --- AJOUT POUR LA RESPONSIVITÉ (Écrans < 768px) --- */
        @media (max-width: 768px) {
            body {
                flex-direction: column; /* Empile les blocs l'un sous l'autre */
                height: auto;
                min-height: 100vh;
            }
            .left-pane {
                width: 100%;
                padding: 30px 20px;
                border-right: none;
                border-bottom: 5px solid #ff7f00;
                gap: 20px;
            }
            .left-pane .logo-box img {
                height: 100px; /* Réduit un peu le logo sur mobile */
            }
            .right-pane {
                width: 100%;
                padding: 40px 20px;
            }
        }
    </style>
</head>
<body>

    <!-- Bloc de gauche -->
    <div class="left-pane">
        <div class="logo-box">
            <img src="../assets/images/images.jpeg" alt="Logo CBS">
        </div>
        <div class="left-content">
            <h1>CBS</h1>
            <p>Application de Gestion et d'évaluation de la Formation Humaine des Étudiants</p>
        </div>
        <div class="footer-text">
            &copy; 2026 CBS - tous droits réservés
        </div>
    </div>

    <!-- Bloc de droite (Formulaire) -->
    <div class="right-pane">
        <div class="login-card">
            <h2>CONNEXION</h2>
            <p>Connectez vous pour accéder à votre espace.</p>
            
            <!-- Zone d'alerte dynamique gérée en JS -->
            <div id="error-alert" class="alert-error"></div>
            
            <form id="loginForm" action="index.php" method="POST">
                <div class="form-group">
                    <label>Identifiant</label>
                    <div class="input-container">
                        <!-- Icône Utilisateur (Bleue) -->
                        <svg fill="#2196F3" viewBox="0 0 24 24" width="20px" height="20px"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                        <input type="text" name="identifiant" id="identifiant" class="form-control" placeholder="Entrer votre Identifiant" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Mot de passe</label>
                    <div class="input-container">
                        <!-- Icône Cadenas (Jaune/Orange) -->
                        <svg fill="#fbb904" viewBox="0 0 24 24" width="20px" height="20px"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
                        <input type="password" name="password" id="password" class="form-control" placeholder="Mot de passe" required style="padding-right: 50px;">
                        <!-- Bouton JS pour afficher/masquer le mot de passe -->
                        <button type="button" id="togglePassword" class="toggle-password">Voir</button>
                    </div>
                </div>
                
                <div class="form-options">
                    <label><input type="checkbox" name="remember"> Se souvenir de moi</label>
                    <a href="#">Mot de passe oublié ?</a>
                </div>
                
                <button type="submit" id="submitBtn" class="btn-submit">Se connecter</button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 1. Gestion affichage/masquage du mot de passe
            const toggleBtn = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');

            toggleBtn.addEventListener('click', function() {
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    toggleBtn.textContent = 'Cacher';
                } else {
                    passwordInput.type = 'password';
                    toggleBtn.textContent = 'Voir';
                }
            });

            // 2. Gestion des messages d'erreur via les paramètres URL (ex: ?erreur=auth_echouee)
            const urlParams = new URLSearchParams(window.location.search);
            const erreur = urlParams.get('erreur');
            const errorAlert = document.getElementById('error-alert');

            if (erreur) {
                errorAlert.style.display = 'block';
                if (erreur === 'auth_echouee') {
                    errorAlert.textContent = 'Identifiant ou mot de passe incorrect.';
                } else if (erreur === 'champs_vides') {
                    errorAlert.textContent = 'Veuillez remplir tous les champs.';
                } else if (erreur === 'role_inconnu') {
                    errorAlert.textContent = 'Erreur liée au rôle utilisateur.';
                } else {
                    errorAlert.textContent = 'Une erreur est survenue lors de la connexion.';
                }
            }

            // 3. Petit effet visuel au chargement et soumission du formulaire
            const form = document.getElementById('loginForm');
            const submitBtn = document.getElementById('submitBtn');

            form.addEventListener('submit', function() {
                submitBtn.textContent = 'Connexion en cours...';
                submitBtn.style.opacity = '0.7';
            });
        });
    </script>
</body>
</html>