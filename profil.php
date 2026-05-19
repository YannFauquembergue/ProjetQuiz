<?php
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit;
}

$user_id = $_SESSION['user_id'];

/* Information utilisateur */
$stmt = $pdo->prepare("SELECT identifiant FROM utilisateur WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

/* Résultats quiz */
$stmt = $pdo->prepare("
    SELECT r.score, q.titre, q.id AS quiz_id
    FROM resultatquiz r
    JOIN quiz q ON r.idquiz = q.id
    WHERE r.idutilisateur = ?
    ORDER BY r.id DESC
");
$stmt->execute([$user_id]);
$resultats = $stmt->fetchAll();

/* Statistiques */
$total_quiz = count($resultats);
$best_score = 0;

foreach ($resultats as $r) {
    if ($r['score'] > $best_score) {
        $best_score = $r['score'];
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Projet quiz - Mon espace</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="container" style="max-width:900px;">

    <h1>Votre espace</h1>

    <h2><?= htmlspecialchars($user['identifiant']) ?></h2>

    <!-- Statistiques -->
    <div class="item-card" style="margin-bottom:20px;">
        <strong>Statistiques</strong><br>
        Quiz joués : <?= $total_quiz ?><br>
        Meilleur score : <?= $best_score ?>
    </div>

    <!-- Résultats quiz (avec option pour rejouer) -->
    <h3>Mes résultats</h3>

    <?php if (empty($resultats)): ?>
        <p style="color:#777;">Aucun quiz joué pour le moment.</p>
    <?php else: ?>

        <?php foreach ($resultats as $r): ?>
            <div class="item-card">

                <div>
                    <strong><?= htmlspecialchars($r['titre']) ?></strong><br>
                    Score : <b><?= $r['score'] ?></b>
                </div>

                <a href="jouer.php?id=<?= $r['quiz_id'] ?>" class="btn">
                    Rejouer
                </a>

            </div>
        <?php endforeach; ?>

    <?php endif; ?>

</div>

</body>
</html>