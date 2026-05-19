<?php
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit;
}

$my_id = $_SESSION['user_id'];

if (!isset($_GET['id'])) {
    die("Utilisateur introuvable.");
}

$friend_id = intval($_GET['id']);

/* Vérification amitié */
$check = $pdo->prepare("
    SELECT id FROM amis 
    WHERE (idutilisateur1 = ? AND idutilisateur2 = ?)
       OR (idutilisateur1 = ? AND idutilisateur2 = ?)
");

$check->execute([$my_id, $friend_id, $friend_id, $my_id]);

if (!$check->fetch()) {
    die("Vous n'êtes pas amis avec cet utilisateur.");
}

/* Infos utilisateur */
$stmt = $pdo->prepare("SELECT identifiant FROM utilisateur WHERE id = ?");
$stmt->execute([$friend_id]);
$user = $stmt->fetch();

/* Résultats quiz de l'ami */
$stmt = $pdo->prepare("
    SELECT r.score, q.titre, q.id AS quiz_id
    FROM resultatquiz r
    JOIN quiz q ON r.idquiz = q.id
    WHERE r.idutilisateur = ?
    ORDER BY r.id DESC
");

$stmt->execute([$friend_id]);
$resultats = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Profil ami</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="container">

    <h1>Profil de <?= htmlspecialchars($user['identifiant']) ?></h1>

    <h3>Stats</h3>

    <p>
        Quiz joués : <?= count($resultats) ?>
    </p>

    <h3>Ses résultats</h3>

    <?php if (empty($resultats)): ?>
        <p>Aucun résultat.</p>
    <?php else: ?>

        <?php foreach ($resultats as $r): ?>
            <div class="item-card">

                <strong><?= htmlspecialchars($r['titre']) ?></strong><br>

                Score : <?= $r['score'] ?><br><br>

                <!-- Bouton pour défier -->
                <a class="btn" href="jouer.php?id=<?= $r['quiz_id'] ?>&defi=<?= $friend_id ?>">
                    Défier
                </a>

            </div>
        <?php endforeach; ?>

    <?php endif; ?>

</div>

</body>
</html>