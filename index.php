<?php
// index.php
require 'config.php';

// Si l'utilisateur n'est pas connecté, retour à la page d'authentification
if (!isset($_SESSION['user_id'])) { 
    header('Location: connexion.php'); 
    exit; 
}

$my_id = $_SESSION['user_id'];

// 1. GESTION DES DEMANDES D'AMIS (ENVOI)
if (isset($_POST['envoyer_demande'])) {
    $nom_ami = trim($_POST['nom_ami']);
    $stmt = $pdo->prepare("SELECT id FROM utilisateur WHERE identifiant = ? AND id != ?");
    $stmt->execute([$nom_ami, $my_id]);
    $dest = $stmt->fetch();
    if ($dest) {
        // On vérifie si une demande ou une amitié n'existe pas déjà
        $check = $pdo->prepare("SELECT id FROM demandeami WHERE (idtransmetteur = ? AND idrecepteur = ?) OR (idtransmetteur = ? AND idrecepteur = ?)");
        $check->execute([$my_id, $dest['id'], $dest['id'], $my_id]);
        if (!$check->fetch()) {
            $pdo->prepare("INSERT INTO demandeami (datedemande, idtransmetteur, idrecepteur) VALUES (NOW(), ?, ?)")->execute([$my_id, $dest['id']]);
        }
    }
}

// 2. GESTION DES DEMANDES D'AMIS (ACCEPTATION)
if (isset($_POST['accepter_ami'])) {
    $id_demande = intval($_POST['id_demande']);
    $stmtD = $pdo->prepare("SELECT * FROM demandeami WHERE id = ? AND idrecepteur = ?");
    $stmtD->execute([$id_demande, $my_id]);
    $demande = $stmtD->fetch();
    
    if ($demande) {
        // On crée la relation d'amitié officielle
        $pdo->prepare("INSERT INTO amis (dateamitie, idutilisateur1, idutilisateur2) VALUES (NOW(), ?, ?)")->execute([$demande['idtransmetteur'], $my_id]);
        // On supprime la demande d'ami obsolète
        $pdo->prepare("DELETE FROM demandeami WHERE id = ?")->execute([$id_demande]);
    }
}

// 3. RÉCUPÉRATION DES QUIZ DISPONIBLES
$quiz_list = $pdo->query("SELECT q.*, u.identifiant FROM quiz q JOIN utilisateur u ON q.idutilisateur = u.id ORDER BY q.id DESC")->fetchAll();

// 4. RÉCUPÉRATION DES DEMANDES D'AMIS REÇUES (Ligne corrigée qui posait problème)
$demandes = $pdo->prepare("SELECT d.id, u.identifiant FROM demandeami d JOIN utilisateur u ON d.idtransmetteur = u.id WHERE d.idrecepteur = ?");
$demandes->execute([$my_id]); 
$demandes = $demandes->fetchAll();

// 5. RÉCUPÉRATION DE MA LISTE D'AMIS ACCEPUTÉS
$amis_stmt = $pdo->prepare("SELECT u.identifiant FROM amis a JOIN utilisateur u ON (a.idutilisateur1 = u.id OR a.idutilisateur2 = u.id) WHERE (a.idutilisateur1 = ? OR a.idutilisateur2 = ?) AND u.id != ?");
$amis_stmt->execute([$my_id, $my_id, $my_id]); 
$mes_amis = $amis_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Plateau des Défis - Qui veut gagner des millions / Kahoot</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container" style="max-width: 1100px;">
        <header style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px;">
            <h2>Bienvenue Maître <?= htmlspecialchars($_SESSION['username'] ?? 'Joueur') ?> 🎖️</h2>
            <a href="deconnexion.php" class="btn btn-secondary" style="margin:0;">Déconnexion</a>
        </header>
        
        <div class="split-layout">
            <div class="column" style="flex: 1.6;">
                <h3>🎯 Les Pyramides de Questions</h3>
                <a href="creer_quiz.php" class="btn" style="margin-bottom:20px; display:inline-block;">+ Créer un Quiz Rapide</a>
                
                <?php if(empty($quiz_list)): ?>
                    <p style="color:#666; text-align:center; margin-top:20px;">Aucun quiz disponible pour le moment. Créez-en un !</p>
                <?php else: ?>
                    <?php foreach($quiz_list as $q): ?>
                        <div class="item-card">
                            <div>
                                <strong><?= htmlspecialchars($q['titre']) ?></strong><br>
                                <small>Concepteur : <?= htmlspecialchars($q['identifiant']) ?> | Catégorie : <?= htmlspecialchars($q['categorie']) ?></small>
                            </div>
                            <a href="jouer.php?id=<?= $q['id'] ?>" class="btn" style="margin:0; padding:10px 15px;">Défier</a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="column">
                <h3>👥 Inviter des Joueurs</h3>
                <form action="index.php" method="POST" style="margin-bottom:20px;">
                    <input type="text" name="nom_ami" placeholder="Rechercher un pseudo..." required>
                    <button type="submit" name="envoyer_demande" class="btn" style="margin-top:10px; padding:8px;">Inviter</button>
                </form>
                
                <?php if(!empty($demandes)): ?>
                    <h4>✉️ Demandes Reçues</h4>
                    <?php foreach($demandes as $d): ?>
                        <div class="item-card" style="background:#fff3cd;">
                            <span><strong><?= htmlspecialchars($d['identifiant']) ?></strong></span>
                            <form action="index.php" method="POST" style="margin:0;">
                                <input type="hidden" name="id_demande" value="<?= $d['id'] ?>">
                                <button type="submit" name="accepter_ami" class="btn" style="margin:0; padding:5px 10px; background:#26890c;">Accepter</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <h4>🟢 Mes Amis en Ligne</h4>
                <?php if(empty($mes_amis)): ?>
                    <p style="color:#999; font-size:14px;">Vous n'avez pas encore d'amis ajoutés.</p>
                <?php else: ?>
                    <?php foreach($mes_amis as $am): ?>
                        <div class="item-card">
                            <span>🟢 <?= htmlspecialchars($am['identifiant']) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>