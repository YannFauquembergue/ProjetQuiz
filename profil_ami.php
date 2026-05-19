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

/* Vérifier que c'est bien un ami */
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

/* Résultats */
$stmt = $pdo->prepare("
    SELECT r.score, q.titre, q.id AS quiz_id
    FROM resultatquiz r
    JOIN quiz q ON r.idquiz = q.id
    WHERE r.idutilisateur = ?
    ORDER BY r.id DESC
");
$stmt->execute([$friend_id]);
$resultats = $stmt->fetchAll();

/* Stats */
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
<meta charset="UTF-8">
<title>Profil ami</title>
<link rel="stylesheet" href="style.css">
</head>

<body>

<div class="container" style="max-width:900px;">

    <h1>Espace de <?= htmlspecialchars($user['identifiant']) ?></h1>

    <!-- Statistiques -->
    <div class="item-card">
        <strong>Statistiques</strong><br>
        Quiz joués : <?= $total_quiz ?><br>
        Meilleur score : <?= $best_score ?>
    </div>

    <!-- Résultats quiz -->
    <h3>Résultats</h3>
    <?php if (empty($resultats)): ?>
        <p>Aucun résultat.</p>
    <?php else: ?>
        <?php foreach ($resultats as $r): ?>
            <div class="item-card">
                <strong><?= htmlspecialchars($r['titre']) ?></strong><br>
                Score : <?= $r['score'] ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>

</body>
</html>