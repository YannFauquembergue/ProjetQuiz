<?php 
require 'config.php';
if (!isset($_SESSION['user_id'])) { header('Location: connexion.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Enregistrement global du Quiz
    $stmt = $pdo->prepare("INSERT INTO quiz (titre, categorie, difficulte, idutilisateur) VALUES (?, ?, ?, ?)");
    $stmt->execute([htmlspecialchars($_POST['titre']), $_POST['categorie'], intval($_POST['difficulte']), $_SESSION['user_id']]);
    $id_quiz = $pdo->lastInsertId();

    // 2. Enregistrement immédiat de la première question
    $stmtQ = $pdo->prepare("INSERT INTO question (sujet, idquiz) VALUES (?, ?)");
    $stmtQ->execute([htmlspecialchars($_POST['question_sujet']), $id_quiz]);
    $id_question = $pdo->lastInsertId();

    // 3. Boucle d'insertion des 4 choix de réponse
    for ($i = 0; $i < 4; $i++) {
        $estVraie = (intval($_POST['correct_index']) === $i) ? 1 : 0;
        $stmtR = $pdo->prepare("INSERT INTO reponse (contenu, estvraie, idquestion) VALUES (?, ?, ?)");
        $stmtR->execute([htmlspecialchars($_POST['reponse_'.$i]), $estVraie, $id_question]);
    }
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"><title>Création Rapide - Kahoot Style</title><link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container" style="max-width: 700px;">
        <h1>Nouveau Défi Pyramide</h1>
        <form action="creer_quiz.php" method="POST">
            <label>Titre de la Pyramide</label>
            <input type="text" name="titre" required>

            <label>Catégorie</label>
            <select name="categorie">
                <option value="Aucune">Aucune</option>
                <option value="Sport">Sport</option>
                <option value="Culture générale">Culture générale</option>
                <option value="Divertissement">Divertissement</option>
            </select>

            <label>Difficulté (1 à 5)</label>
            <input type="number" name="difficulte" min="1" max="5" value="3">

            <hr style="margin-top:30px; border:1px solid #f0f0f0;">
            <h3>Question Initiale</h3>
            <input type="text" name="question_sujet" placeholder="Saisissez la question..." required>
            
            <label>Option 1 (Bloc Rouge)</label>
            <input type="text" name="reponse_0" required>
            <label>Option 2 (Bloc Bleu)</label>
            <input type="text" name="reponse_1" required>
            <label>Option 3 (Bloc Jaune)</label>
            <input type="text" name="reponse_2" required>
            <label>Option 4 (Bloc Vert)</label>
            <input type="text" name="reponse_3" required>

            <label>Quelle est la seule réponse correcte ?</label>
            <select name="correct_index">
                <option value="0">Option 1 (Bloc Rouge)</option>
                <option value="1">Option 2 (Bloc Bleu)</option>
                <option value="2">Option 3 (Bloc Jaune)</option>
                <option value="3">Option 4 (Bloc Vert)</option>
            </select>

            <button type="submit" class="btn">Publier le Quiz</button>
        </form>
    </div>
</body>
</html>