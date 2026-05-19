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
        'id'         => $question['id'],
        'sujet'      => $question['sujet'],
        'media'      => $question['media']      ?? null,   // ← chemin fichier
        'media_type' => $question['media_type'] ?? null,   // ← 'image' | 'audio' | 'video' | null
        'reponses'   => $reponses
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

    <style>
        /* Zone média au-dessus des réponses */
        #media-box {
            text-align: center;
            margin: 12px auto;
        }
        #media-box.hidden { display: none; }

        #media-box img {
            max-height: 220px;
            max-width: 100%;
            border-radius: 8px;
            object-fit: contain;
            box-shadow: 0 2px 8px rgba(0,0,0,.25);
        }
        #media-box audio {
            width: 100%;
            margin-top: 6px;
        }
        #media-box video {
            max-height: 220px;
            max-width: 100%;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,.25);
        }

        .correct { color: #2ecc71; font-weight: bold; font-size: 1.2em; }
        .wrong   { color: #e74c3c; font-weight: bold; font-size: 1.2em; }
    </style>
</head>
<body>
    <div class="game-container">
        <h1 id="quiz-title"><?php echo htmlspecialchars($quiz['titre']); ?></h1>

        <div id="question-box">
            <h2 id="question-text">Chargement de la question...</h2>

            <!-- Média de la question (image / audio / vidéo) -->
            <div id="media-box" class="hidden"></div>
        </div>

        <div id="answers-grid" class="answers-grid"></div>

        <div id="feedback" class="feedback"></div>
    </div>

    <script>
        const data = <?php echo $json_data; ?>;
        let currentQuestionIndex = 0;
        let score = 0;

        const questionText = document.getElementById('question-text');
        const answersGrid  = document.getElementById('answers-grid');
        const feedback     = document.getElementById('feedback');
        const mediaBox     = document.getElementById('media-box');

        /* --------------------------------------------------
           Affiche le média de la question (ou rien du tout)
        -------------------------------------------------- */
        function renderMedia(q) {
            mediaBox.innerHTML = '';
            mediaBox.classList.add('hidden');

            if (!q.media || !q.media_type) return;

            let el;

            if (q.media_type === 'image') {
                el = document.createElement('img');
                el.src = q.media;
                el.alt = 'Illustration de la question';

            } else if (q.media_type === 'audio') {
                el = document.createElement('audio');
                el.src      = q.media;
                el.controls = true;

            } else if (q.media_type === 'video') {
                el = document.createElement('video');
                el.src      = q.media;
                el.controls = true;
            }

            if (el) {
                mediaBox.appendChild(el);
                mediaBox.classList.remove('hidden');
            }
        }

        /* --------------------------------------------------
           Charge la question courante
        -------------------------------------------------- */
        function loadQuestion() {
            feedback.innerText    = '';
            answersGrid.innerHTML = '';

            if (currentQuestionIndex >= data.length) {
                endGame();
                return;
            }

            const q = data[currentQuestionIndex];
            questionText.innerText = q.sujet;

            renderMedia(q);

            // Affichage des réponses sous forme de 4 gros boutons colorés
            q.reponses.forEach((reponse, index) => {
                const button = document.createElement('button');
                button.classList.add('answer-btn', `color-${index}`);
                button.innerText = reponse.contenu;
                button.onclick   = () => checkAnswer(reponse.estvraie, button);
                answersGrid.appendChild(button);
            });
        }

        /* --------------------------------------------------
           Vérification de la réponse choisie
        -------------------------------------------------- */
        function checkAnswer(isTrue, selectedButton) {
            // Désactiver tous les boutons après le clic
            document.querySelectorAll('.answer-btn').forEach(btn => btn.disabled = true);

            // Stopper la lecture audio/vidéo avant de passer à la suite
            const mediaEl = mediaBox.querySelector('audio, video');
            if (mediaEl) mediaEl.pause();

            if (isTrue == 1) {
                score += 100;
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

        /* --------------------------------------------------
           Fin de partie
        -------------------------------------------------- */
        function endGame() {
            questionText.innerText = "Partie terminée !";
            mediaBox.innerHTML     = '';
            mediaBox.classList.add('hidden');
            answersGrid.innerHTML  = `<h3>Votre score final : ${score} points</h3>`;

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
        if (data.length > 0) {
            loadQuestion();
        } else {
            questionText.innerText = "Ce quiz n'a pas encore de questions.";
        }
    </script>
</body>
</html>