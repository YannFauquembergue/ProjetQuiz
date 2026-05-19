<?php 
// connexion.php
require 'config.php';

$message = "";

// LOGIQUE 1 : Si on clique sur le bouton de connexion Admin Rapide
if (isset($_POST['connexion_admin_rapide'])) {
    $admin_pseudo = "Admin_Systeme";
    
    // On vérifie si ce compte admin existe déjà dans la base
    $stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE identifiant = ?");
    $stmt->execute([$admin_pseudo]);
    $user = $stmt->fetch();

    if (!$user) {
        // Si le compte admin n'existe pas encore, on le crée automatiquement en BDD
        // Mot de passe fictif haché "admin123" (sécurité requise par la structure)
        $mdp_hache = password_hash("admin123", PASSWORD_DEFAULT);
        $insert = $pdo->prepare("INSERT INTO utilisateur (identifiant, mdp) VALUES (?, ?)");
        $insert->execute([$admin_pseudo, $mdp_hache]);
        
        // On récupère l'ID du compte fraîchement créé
        $admin_id = $pdo->lastInsertId();
    } else {
        $admin_id = $user['id'];
    }

    // Connexion automatique instantanée : on remplit la session
    $_SESSION['user_id'] = $admin_id;
    $_SESSION['username'] = $admin_pseudo;

    // Redirection immédiate vers le tableau de bord sans mot de passe !
    header('Location: index.php');
    exit;
}

// LOGIQUE 2 : Connexion standard avec formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['connexion_admin_rapide'])) {
    $identifiant = trim($_POST['identifiant']);
    $mdp = $_POST['mdp'];

    $stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE identifiant = ?");
    $stmt->execute([$identifiant]);
    $user = $stmt->fetch();

    if ($user && password_verify($mdp, $user['mdp'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['identifiant'];
        
        header('Location: index.php');
        exit;
    } else { 
        $message = "Identifiant ou mot de passe incorrect."; 
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion - La Pyramide des Quiz</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container" style="max-width: 450px;">
        <h1 style="font-size: 28px; margin-bottom: 10px;">Authentification</h1>
        <p style="text-align:center; color:#666; margin-bottom:25px;">Connectez-vous pour affronter la pyramide</p>
        
        <?php if($message): ?>
            <p class="wrong" style="text-align:center; background: rgba(226, 27, 60, 0.1); padding: 10px; border-radius: 5px;">
                <?= htmlspecialchars($message) ?>
            </p>
        <?php endif; ?>

        <form action="connexion.php" method="POST" style="margin-bottom: 25px;">
            <button type="submit" name="connexion_admin_rapide" class="btn" style="background-color: var(--green-kahoot); margin-top: 0;">
                ⚡ Connexion Admin Rapide (Sans saisie)
            </button>
        </form>

        <div style="text-align: center; margin-bottom: 15px; color: #aaa;">— OU —</div>

        <form action="connexion.php" method="POST">
            <label>Identifiant / Pseudo</label>
            <input type="text" name="identifiant" autocomplete="username">

            <label>Mot de passe</label>
            <input type="password" name="mdp" autocomplete="current-password">

            <button type="submit" class="btn">Entrer sur le Plateau</button>
            
            <a href="inscription.php" style="margin-top:20px; text-align:center; color: #1e295d; font-weight:600; text-decoration:none;">
                Pas encore de compte ? S'inscrire
            </a>
        </form>
    </div>
</body>
</html>