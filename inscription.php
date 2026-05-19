<?php
// inscription.php
require 'config.php';

$message_erreur = "";
$message_succes = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifiant = trim($_POST['identifiant']);
    $mdp = $_POST['mdp'];
    $mdp_confirme = $_POST['mdp_confirme'];

    if (empty($identifiant) || empty($mdp)) {
        $message_erreur = "Tous les champs sont obligatoires.";
    } elseif ($mdp !== $mdp_confirme) {
        $message_erreur = "Les deux mots de passe ne correspondent pas.";
    } else {
        // Vérifier si le pseudo existe déjà
        $stmt = $pdo->prepare("SELECT id FROM utilisateur WHERE identifiant = ?");
        $stmt->execute([$identifiant]);
        
        if ($stmt->fetch()) {
            $message_erreur = "Ce pseudo est déjà utilisé par un autre joueur.";
        } else {
            // Hachage sécurisé du mot de passe
            $mdp_hache = password_hash($mdp, PASSWORD_DEFAULT);

            // Insertion en base de données
            $insert = $pdo->prepare("INSERT INTO utilisateur (identifiant, mdp) VALUES (?, ?)");
            if ($insert->execute([$identifiant, $mdp_hache])) {
                $message_succes = "Compte créé avec succès ! Vous pouvez maintenant vous connecter.";
            } else {
                $message_erreur = "Une erreur est survenue lors de l'inscription.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Projet quiz - Inscription</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container" style="max-width: 450px;">
        <h1 style="font-size: 28px; margin-bottom: 10px;">Créer un compte</h1>
        <p style="text-align:center; color:#666; margin-bottom:25px;">Rejoignez l'arène pour gravir les échelons</p>
        
        <?php if($message_erreur): ?>
            <p class="wrong" style="text-align:center; background: rgba(226, 27, 60, 0.1); padding: 10px; border-radius: 5px;">
                <?= htmlspecialchars($message_erreur) ?>
            </p>
        <?php endif; ?>

        <?php if($message_succes): ?>
            <p class="correct" style="text-align:center; background: rgba(38, 137, 12, 0.1); padding: 10px; border-radius: 5px;">
                <?= htmlspecialchars($message_succes) ?>
            </p>
        <?php endif; ?>

        <form action="inscription.php" method="POST">
            <label>Choisissez un identifiant</label>
            <input type="text" name="identifiant" required maxlength="25" autocomplete="username">

            <label>Mot de passe</label>
            <input type="password" name="mdp" required autocomplete="new-password">

            <label>Confirmez le mot de passe</label>
            <input type="password" name="mdp_confirme" required autocomplete="new-password">

            <button type="submit" class="btn">Créer mon compte</button>
            
            <a href="connexion.php" style="margin-top:20px; text-align:center; color: #1e295d; font-weight:600; text-decoration:none;">
                Déjà inscrit ? Se connecter
            </a>
        </form>
    </div>
</body>
</html>