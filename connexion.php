<?php 
require 'config.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

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
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Connexion</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="container" style="max-width: 450px;">

    <h1>Authentification</h1>

    <?php if ($message): ?>
        <p class="wrong">
            <?= htmlspecialchars($message) ?>
        </p>
    <?php endif; ?>

    <form action="connexion.php" method="POST">

        <label>Identifiant</label>
        <input type="text" name="identifiant" required>

        <label>Mot de passe</label>
        <input type="password" name="mdp" required>

        <button type="submit" class="btn">Connexion</button>

        <a href="inscription.php"
           style="display:block; margin-top:15px; text-align:center;">
            Pas encore de compte ? S'inscrire
        </a>

    </form>

</div>

</body>
</html>