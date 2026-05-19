<?php
require 'config.php';

// Vérification de l'authentification
if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit;
}

$my_id = $_SESSION['user_id'];
$id = intval($_GET['id'] ?? 0);

// On charge le quiz concerné
$stmt = $pdo->prepare("SELECT * FROM quiz WHERE id = ?");
$stmt->execute([$id]);
$quiz = $stmt->fetch();

if (!$quiz || $quiz['idutilisateur'] != $my_id) {
    die("Accès refusé");
}

// On charge les questions et leurs réponses
$stmtQ = $pdo->prepare("SELECT * FROM question WHERE idquiz = ?");
$stmtQ->execute([$id]);
$questions = $stmtQ->fetchAll();

foreach ($questions as &$q) {
    $stmtR = $pdo->prepare("SELECT * FROM reponse WHERE idquestion = ?");
    $stmtR->execute([$q['id']]);
    $q['reponses'] = $stmtR->fetchAll();
}
unset($q);

// Sauvegarde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // mise à jour quiz
    $stmt = $pdo->prepare("
        UPDATE quiz 
        SET titre = ?, categorie = ?, difficulte = ?
        WHERE id = ? AND idutilisateur = ?
    ");

    $stmt->execute([
        $_POST['titre'],
        $_POST['categorie'],
        intval($_POST['difficulte']),
        $id,
        $my_id
    ]);

    // supprimer anciennes données
    $pdo->prepare("DELETE FROM reponse WHERE idquestion IN (SELECT id FROM question WHERE idquiz = ?)")
        ->execute([$id]);

    $pdo->prepare("DELETE FROM question WHERE idquiz = ?")
        ->execute([$id]);

    // réinsertion complète
    if (!empty($_POST['questions'])) {
        foreach ($_POST['questions'] as $q) {

            if (empty($q['sujet'])) continue;

            $stmtQ = $pdo->prepare("
                INSERT INTO question (sujet, idquiz)
                VALUES (?, ?)
            ");

            $stmtQ->execute([
                htmlspecialchars($q['sujet']),
                $id
            ]);

            $id_question = $pdo->lastInsertId();

            for ($i = 0; $i < 4; $i++) {

                $stmtR = $pdo->prepare("
                    INSERT INTO reponse (contenu, estvraie, idquestion)
                    VALUES (?, ?, ?)
                ");

                $stmtR->execute([
                    htmlspecialchars($q['reponses'][$i]),
                    (isset($q['correct']) && (int)$q['correct'] === $i) ? 1 : 0,
                    $id_question
                ]);
            }
        }
    }

    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Modifier Quiz</title>
<link rel="stylesheet" href="style.css">

<style>
.question-block {
    border:1px solid #ddd;
    padding:15px;
    margin-bottom:15px;
    border-radius:8px;
}
</style>
</head>

<body>

<div class="container" style="max-width:700px;">

<h1>Modification quiz</h1>

<form method="POST">

<!-- QUIZ INFO -->
<label>Titre</label>
<input type="text" name="titre" value="<?= htmlspecialchars($quiz['titre']) ?>" required>

<label>Catégorie</label>
<select name="categorie">
            <option value="Aucune">Aucune</option>
            <option value="Sport">Sport</option>
            <option value="Culture générale">Culture générale</option>
            <option value="Divertissement">Divertissement</option>
        </select>
    
<label>Difficulté</label>
<input type="number" name="difficulte" value="<?= $quiz['difficulte'] ?>" min="1" max="5">

<hr>

<h2>Questions</h2>

<div id="questions-container">

<?php foreach ($questions as $qi => $q): ?>
<div class="question-block">

    <input type="text"
        name="questions[<?= $qi ?>][sujet]"
        value="<?= htmlspecialchars($q['sujet']) ?>"
        required>

    <?php for ($i = 0; $i < 4; $i++): ?>
        <input type="text"
            name="questions[<?= $qi ?>][reponses][<?= $i ?>]"
            value="<?= htmlspecialchars($q['reponses'][$i]['contenu']) ?>"
            required>
    <?php endfor; ?>

    <select name="questions[<?= $qi ?>][correct]">
        <?php for ($i = 0; $i < 4; $i++): ?>
            <option value="<?= $i ?>"
                <?= !empty($q['reponses'][$i]['estvraie']) ? 'selected' : '' ?>>
                Bonne réponse <?= $i + 1 ?>
            </option>
        <?php endfor; ?>
    </select>

    <button type="button" onclick="removeQuestion(this)">
        Supprimer
    </button>

</div>
<?php endforeach; ?>

</div>

<button type="button" onclick="addQuestion()">
    Ajouter une question
</button>

<br><br>

<button type="submit" class="btn">
    Enregistrer modifications
</button>

</form>

</div>

<script>
let index = <?= count($questions) ?>;

// Fonction d'ajout de question
function addQuestion() {

    const container = document.getElementById("questions-container");

    const html = `
    <div class="question-block">

        <input type="text" name="questions[${index}][sujet]" placeholder="Question..." required>

        <input type="text" name="questions[${index}][reponses][0]" placeholder="Option 1" required>
        <input type="text" name="questions[${index}][reponses][1]" placeholder="Option 2" required>
        <input type="text" name="questions[${index}][reponses][2]" placeholder="Option 3" required>
        <input type="text" name="questions[${index}][reponses][3]" placeholder="Option 4" required>

        <select name="questions[${index}][correct]">
            <option value="0">Option 1</option>
            <option value="1">Option 2</option>
            <option value="2">Option 3</option>
            <option value="3">Option 4</option>
        </select>

        <button type="button" onclick="removeQuestion(this)">
            Supprimer cette question
        </button>

    </div>`;

    container.insertAdjacentHTML("beforeend", html);
    index++;
}

// Fonction suppression d'une question
function removeQuestion(btn) {
    btn.closest(".question-block").remove();
}
</script>

</body>
</html>