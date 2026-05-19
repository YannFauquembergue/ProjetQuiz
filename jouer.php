<?php
// jouer.php
require 'config.php';

$id_quiz = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Récupérer les infos du quiz
$stmt = $pdo->prepare("SELECT * FROM quiz WHERE id = ?");
$stmt->execute([$id_quiz]);
$quiz = $stmt->fetch();

if (!$quiz) {
    die("Quiz introuvable.");
}

// Récupérer les questions et leurs réponses associées
$stmtQ = $pdo->prepare("SELECT * FROM question WHERE idquiz = ?");
$stmtQ->execute([$id_quiz]);
$questions = $stmtQ->fetchAll();

$quiz_data = [];

foreach ($questions as $question) {
    $stmtR = $pdo->prepare("SELECT * FROM reponse WHERE idquestion = ?");
    $stmtR->execute([$question['id']]);
    $reponses = $stmtR->fetchAll();

    $quiz_data[] = [
        'id' => $question['id'],
        'sujet' => $question['sujet'],
        'reponses' => $reponses
    ];
}

// Encodage en JSON pour que le JavaScript puisse le lire facilement
$json_data = json_encode($quiz_data);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Kahoot Clone - En plein jeu</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="game-container">
        <h1 id="quiz-title"><?php echo htmlspecialchars($quiz['titre']); ?></h1>
        
        <div id="question-box">
            <h2 id="question-text">Chargement de la question...</h2>
        </div>

        <div id="answers-grid" class="answers-grid">
            </div>

        <div id="feedback" class="feedback"></div>
    </div>

    <script>
        // On récupère les données PHP en JS
        const data = <?php echo $json_data; ?>;
        let currentQuestionIndex = 0;
        let score = 0;

        const questionText = document.getElementById('question-text');
        const answersGrid = document.getElementById('answers-grid');
        const feedback = document.getElementById('feedback');

        function loadQuestion() {
            feedback.innerText = "";
            answersGrid.innerHTML = "";

            if (currentQuestionIndex >= data.length) {
                endGame();
                return;
            }

            let currentQuestion = data[currentQuestionIndex];
            questionText.innerText = currentQuestion.sujet;

            // Affichage des réponses sous forme de 4 gros boutons colorés
            currentQuestion.reponses.forEach((reponse, index) => {
                const button = document.createElement('button');
                button.classList.add('answer-btn', `color-${index}`);
                button.innerText = reponse.contenu;
                
                // Événement au clic sur une réponse
                button.onclick = () => checkAnswer(reponse.estvraie, button);
                answersGrid.appendChild(button);
            });
        }

        function checkAnswer(isTrue, selectedButton) {
            // Désactiver tous les boutons après le clic
            const buttons = document.querySelectorAll('.answer-btn');
            buttons.forEach(btn => btn.disabled = true);

            // Vérification (estvraie vaut "1" ou 1 en base de données)
            if (isTrue == 1) {
                score += 100; // Système de points basique
                feedback.innerHTML = "<p class='correct'>Bonne réponse ! +100 pts</p>";
                selectedButton.style.border = "4px solid #2ecc71";
            } else {
                feedback.innerHTML = "<p class='wrong'>Mauvaise réponse...</p>";
                selectedButton.style.border = "4px solid #e74c3c";
            }

            // Passer à la question suivante après 2 secondes
            setTimeout(() => {
                currentQuestionIndex++;
                loadQuestion();
            }, 2000);
        }

        function endGame() {
            questionText.innerText = "Partie terminée !";
            answersGrid.innerHTML = `<h3>Votre score final : ${score} points</h3>`;
            
            // Envoi du score à la base de données via l'API Fetch (AJAX)
            fetch('sauvegarder_score.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `score=${score}&idquiz=<?php echo $id_quiz; ?>`
            })
            .then(res => res.text())
            .then(msg => {
                feedback.innerHTML += `<p>${msg}</p><a href='index.php' class='btn'>Retour à l'accueil</a>`;
            });
        }

        // Lancement du jeu au chargement de la page
        if(data.length > 0) {
            loadQuestion();
        } else {
            questionText.innerText = "Ce quiz n'a pas encore de questions.";
        }
    </script>
</body>
</html>