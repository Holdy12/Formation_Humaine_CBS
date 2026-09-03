<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Formation Humaine</title>
</head>
<body>
    <h2>Connexion Système</h2>
    <?php if (isset($error)): ?>
        <p style="color: red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <form action="" method="POST">
        <div>
            <label>Email :</label><br>
            <input type="email" name="email" required placeholder="admin@formation.local">
        </div>
        <br>
        <div>
            <label>Mot de passe :</label><br>
            <input type="password" name="password" required>
        </div>
        <br>
        <button type="submit">Se connecter</button>
    </form>
</body>
</html>