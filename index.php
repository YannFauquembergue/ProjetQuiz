<?php
require 'config.php';

if (!isset($_SESSION['user_id'])) { 
    header('Location: connexion.php'); 
    exit; 
}

$my_id = $_SESSION['user_id'];

// 1. ENVOI DEMANDE AMI
if (isset($_POST['envoyer_demande'])) {
    $nom_ami = trim($_POST['nom_ami']);

    $stmt = $pdo->prepare("SELECT id FROM utilisateur WHERE identifiant = ? AND id != ?");
    $stmt->execute([$nom_ami, $my_id]);
    $dest = $stmt->fetch();

    if ($dest) {
        $check = $pdo->prepare("
            SELECT id FROM demandeami 
            WHERE (idtransmetteur = ? AND idrecepteur = ?) 
               OR (idtransmetteur = ? AND idrecepteur = ?)
        ");
        $check->execute([$my_id, $dest['id'], $dest['id'], $my_id]);

        if (!$check->fetch()) {
            $pdo->prepare("
                INSERT INTO demandeami (datedemande, idtransmetteur, idrecepteur) 
                VALUES (NOW(), ?, ?)
            ")->execute([$my_id, $dest['id']]);
        }
    }
}

// 2. ACCEPTATION AMI
if (isset($_POST['accepter_ami'])) {
    $id_demande = intval($_POST['id_demande']);

    $stmtD = $pdo->prepare("SELECT * FROM demandeami WHERE id = ? AND idrecepteur = ?");
    $stmtD->execute([$id_demande, $my_id]);
    $demande = $stmtD->fetch();

    if ($demande) {
        $pdo->prepare("
            INSERT INTO amis (dateamitie, idutilisateur1, idutilisateur2)
            VALUES (NOW(), ?, ?)
        ")->execute([$demande['idtransmetteur'], $my_id]);

        $pdo->prepare("DELETE FROM demandeami WHERE id = ?")
            ->execute([$id_demande]);
    }
}

// 3. QUIZ LIST
$quiz_list = $pdo->query("
    SELECT q.*, u.identifiant 
    FROM quiz q 
    JOIN utilisateur u ON q.idutilisateur = u.id 
    ORDER BY q.id DESC
")->fetchAll();

// 4. DEMANDES AMIS
$demandes = $pdo->prepare("
    SELECT d.id, u.identifiant 
    FROM demandeami d 
    JOIN utilisateur u ON d.idtransmetteur = u.id 
    WHERE d.idrecepteur = ?
");
$demandes->execute([$my_id]);
$demandes = $demandes->fetchAll();

// 5. AMIS
$amis_stmt = $pdo->prepare("
    SELECT u.id, u.identifiant 
    FROM amis a 
    JOIN utilisateur u 
    ON (a.idutilisateur1 = u.id OR a.idutilisateur2 = u.id)
    WHERE (a.idutilisateur1 = ? OR a.idutilisateur2 = ?)
    AND u.id != ?
");
$amis_stmt->execute([$my_id, $my_id, $my_id]);
$mes_amis = $amis_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Projet quiz</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="container" style="max-width: 1100px;">

    <header style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px;">

        <h2>Bienvenue <?= htmlspecialchars($_SESSION['username'] ?? 'Joueur') ?></h2>

        <div style="display:flex; gap:10px; align-items:center;">
            <a href="profil.php" class="btn">Mon espace</a>
            <a href="deconnexion.php" class="btn btn-secondary">Déconnexion</a>
        </div>

    </header>

    <div class="split-layout">

        <!-- Liste des quiz -->
        <div class="column" style="flex: 1.6;">

            <h3>Liste de quiz</h3>

            <a href="creer_quiz.php" class="btn" style="margin-bottom:20px;">Créer un quiz</a>

            <?php if (empty($quiz_list)): ?>
                <p style="color:#666;">Aucun quiz disponible.</p>
            <?php else: ?>

                <?php foreach ($quiz_list as $q): ?>
                    <div class="item-card">

                        <div>
                            <strong><?= htmlspecialchars($q['titre']) ?></strong><br>
                            <small>
                                Créateur: <?= htmlspecialchars($q['identifiant']) ?> |
                                Catégorie: <?= htmlspecialchars($q['categorie']) ?>
                            </small>
                        </div>

                        <div style="display:flex; gap:10px;">

                            <a href="jouer.php?id=<?= $q['id'] ?>" class="btn" style="margin:0; padding:10px 15px;">
                                Défier
                            </a>

                            <?php if ($q['idutilisateur'] == $my_id): ?>
                                <a href="modifier_quiz.php?id=<?= $q['id'] ?>" 
                                   class="btn" 
                                   style="margin:0; padding:10px 15px; background:#ff9800;">
                                    Modifier
                                </a>
                            <?php endif; ?>

                        </div>

                    </div>
                <?php endforeach; ?>

            <?php endif; ?>

        </div>

        <!-- Section amis -->
        <div class="column">

            <h3>Inviter des joueurs</h3>

            <form method="POST" style="margin-bottom:20px;">
                <input type="text" name="nom_ami" placeholder="Rechercher un identifiant..." required>
                <button type="submit" name="envoyer_demande" class="btn" style="margin-top:10px;">
                    Inviter
                </button>
            </form>

            <?php if (!empty($demandes)): ?>
                <h4>Demandes reçues</h4>
                <?php foreach ($demandes as $d): ?>
                    <div class="item-card" style="background:#fff3cd;">

                        <strong><?= htmlspecialchars($d['identifiant']) ?></strong>

                        <form method="POST" style="margin:0;">
                            <input type="hidden" name="id_demande" value="<?= $d['id'] ?>">
                            <button type="submit" name="accepter_ami"
                                class="btn"
                                style="background:#26890c; margin:0; padding:5px 10px;">
                                Accepter
                            </button>
                        </form>

                    </div>
                <?php endforeach; ?>

            <?php endif; ?>

            <h4>Mes amis</h4>

            <?php if (empty($mes_amis)): ?>
                <p style="color:#999;">Aucun ami.</p>
            <?php else: ?>
                <?php foreach ($mes_amis as $am): ?>
                    <div class="item-card">
                        <a href="profil_ami.php?id=<?= $am['id'] ?>" style="text-decoration:none; color:inherit;">
                            <?= htmlspecialchars($am['identifiant']) ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>

    </div>
</div>

</body>
</html>