<?php 
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Création du quiz
    $stmt = $pdo->prepare("
        INSERT INTO quiz (titre, categorie, difficulte, idutilisateur) 
        VALUES (?, ?, ?, ?)
    ");

    $stmt->execute([
        htmlspecialchars($_POST['titre']),
        $_POST['categorie'],
        intval($_POST['difficulte']),
        $_SESSION['user_id']
    ]);

    $id_quiz = $pdo->lastInsertId();

    // 2. Parcours des questions
    if (!empty($_POST['questions']) && is_array($_POST['questions'])) {

        foreach ($_POST['questions'] as $q) {

            if (empty($q['sujet'])) continue;

            // insertion question
            $stmtQ = $pdo->prepare("
                INSERT INTO question (sujet, idquiz) 
                VALUES (?, ?)
            ");

            $stmtQ->execute([
                htmlspecialchars($q['sujet']),
                $id_quiz
            ]);

            $id_question = $pdo->lastInsertId();

            // réponses
            if (!empty($q['reponses']) && is_array($q['reponses'])) {

                for ($i = 0; $i < 4; $i++) {

                    if (!isset($q['reponses'][$i])) continue;

                    $estVraie = (isset($q['correct']) && (int)$q['correct'] === $i) ? 1 : 0;

                    $stmtR = $pdo->prepare("
                        INSERT INTO reponse (contenu, estvraie, idquestion) 
                        VALUES (?, ?, ?)
                    ");

                    $stmtR->execute([
                        htmlspecialchars($q['reponses'][$i]),
                        $estVraie,
                        $id_question
                    ]);
                }
            }
        }
    }

    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Création Quiz</title>
    <link rel="stylesheet" href="style.css">

    <style>
        .question-block {
            padding: 15px;
            border: 1px solid #ddd;
            margin-bottom: 15px;
            border-radius: 8px;
        }

        button {
            margin-top: 8px;
        }
    </style>
</head>
<body>

<div class="container" style="max-width:700px;">

    <h1>Nouveau Quiz</h1>

    <form method="POST">

        <!-- QUIZ -->
        <label>Titre</label>
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

        <hr>

        <!-- QUESTIONS -->
        <h2>Questions</h2>

        <div id="questions-container">

            <!-- QUESTION 0 -->
            <div class="question-block">

                <input type="text" name="questions[0][sujet]" placeholder="Question..." required>

                <input type="text" name="questions[0][reponses][0]" placeholder="Option 1" required>
                <input type="text" name="questions[0][reponses][1]" placeholder="Option 2" required>
                <input type="text" name="questions[0][reponses][2]" placeholder="Option 3" required>
                <input type="text" name="questions[0][reponses][3]" placeholder="Option 4" required>

                <label>Bonne réponse</label>
                <select name="questions[0][correct]">
                    <option value="0">Option 1</option>
                    <option value="1">Option 2</option>
                    <option value="2">Option 3</option>
                    <option value="3">Option 4</option>
                </select>

                <button type="button" onclick="removeQuestion(this)">Supprimer</button>

                <hr>
            </div>

        </div>

        <button type="button" onclick="addQuestion()">+ Ajouter une question</button>

        <br><br>

        <button type="submit">Publier le quiz</button>

    </form>
</div>

<script>
let index = 1;

function addQuestion() {
    const container = document.getElementById("questions-container");

    const html = `
    <div class="question-block">

        <input type="text" name="questions[${index}][sujet]" placeholder="Question..." required>

        <input type="text" name="questions[${index}][reponses][0]" placeholder="Option 1" required>
        <input type="text" name="questions[${index}][reponses][1]" placeholder="Option 2" required>
        <input type="text" name="questions[${index}][reponses][2]" placeholder="Option 3" required>
        <input type="text" name="questions[${index}][reponses][3]" placeholder="Option 4" required>

        <label>Bonne réponse</label>
        <select name="questions[${index}][correct]">
            <option value="0">Option 1</option>
            <option value="1">Option 2</option>
            <option value="2">Option 3</option>
            <option value="3">Option 4</option>
        </select>

        <button type="button" onclick="removeQuestion(this)">Supprimer</button>

        <hr>
    </div>`;

    container.insertAdjacentHTML("beforeend", html);
    index++;
}

function removeQuestion(btn) {
    btn.closest(".question-block").remove();
}
</script>

</body>
</html>