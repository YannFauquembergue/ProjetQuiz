<?php
// fichier php pour recevoir le score final et l'enregistrer en base de données
header('Content-Type: application/json');
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['statut' => 'erreur', 'message' => 'Veuillez vous reconnecter']);
        exit;
    }

    $score = isset($_POST['score']) ? intval($_POST['score']) : 0;
    $id_quiz = isset($_POST['idquiz']) ? intval($_POST['idquiz']) : 0;
    $id_utilisateur = $_SESSION['user_id'];

    if ($id_quiz > 0) {
        $stmt = $pdo->prepare("INSERT INTO resultatquiz (score, idutilisateur, idquiz) VALUES (?, ?, ?)");
        $stmt->execute([$score, $id_utilisateur, $id_quiz]);
        echo json_encode(['statut' => 'succes', 'message' => 'Score enregistré sur votre profil !']);
    } else {
        echo json_encode(['statut' => 'erreur', 'message' => 'Données corrompues']);
    }
}
?>