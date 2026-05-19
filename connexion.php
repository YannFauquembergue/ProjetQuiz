<?php 
// connexion.php
require 'config.php';

$message = "";

// LOGIQUE 1 : Si on clique sur le bouton de connexion Admin Rapide

// Admin_systeme est un compte temporaire
if (isset($_POST['connexion_admin_rapide'])) {
    $admin_pseudo = "Admin_Systeme";
    
    // On vérifie si ce compte admin existe déjà dans la base
    $stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE identifiant = ?");
    $stmt->execute([$admin_pseudo]);
    $user = $stmt->fetch();

    if (!$user) {
        // Si le compte admin n'existe pas encore, on le crée automatiquement en BDD
        // Mot de passe fictif haché "admin123"
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
    <meta charset="utf-8">
    <title>Projet quiz - Connexion</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container" style="max-width: 450px;">
        <h1 style="font-size: 28px; margin-bottom: 10px;">Authentification</h1>
        
        <?php if($message): ?>
            <p class="wrong" style="text-align:center; background: rgba(226, 27, 60, 0.1); padding: 10px; border-radius: 5px;">
                <?= htmlspecialchars($message) ?>
            </p>
        <?php endif; ?>

        <!-- Bouton admin caché -->
        <form action="connexion.php"
            method="POST"
            style="
                position: fixed;
                bottom: 3px;
                right: 3px;
                margin:0;
                padding:0;
                z-index:1;
            ">

            <button type="submit"
                    name="connexion_admin_rapide"
                    style="
                        width:12px;
                        height:12px;
                        border:none;
                        background:transparent;
                        opacity:0.03;
                        cursor:default;
                        padding:0;
                        margin:0;
                    ">
            </button>

        </form>

        <form action="connexion.php" method="POST">
            <label>Identifiant</label>
            <input type="text" name="identifiant" autocomplete="username">

            <label>Mot de passe</label>
            <input type="password" name="mdp" autocomplete="current-password">

            <button type="submit" class="btn">Connexion</button>
            
            <a href="inscription.php" style="margin-top:20px; text-align:center; color: #1e295d; font-weight:600; text-decoration:none;">
                Pas encore de compte ? S'inscrire
            </a>
        </form>
    </div>
</body>
</html>