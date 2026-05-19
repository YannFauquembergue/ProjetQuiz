<?php
require 'config.php';

$id_quiz = isset($_GET['id']) ? intval($_GET['id']) : 0;
$defi_id = isset($_GET['defi']) ? intval($_GET['defi']) : 0;

$stmt = $pdo->prepare("SELECT * FROM quiz WHERE id = ?");
$stmt->execute([$id_quiz]);
$quiz = $stmt->fetch();

if (!$quiz) {
    die("Quiz introuvable.");
}

/* QUESTIONS */
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
        'media' => $question['media'],
        'media_type' => $question['media_type'],
        'reponses' => $reponses
    ];
}

$defi_data = ['nom' => null, 'score' => null];

if ($defi_id > 0) {

    $stmt = $pdo->prepare("SELECT identifiant FROM utilisateur WHERE id = ?");
    $stmt->execute([$defi_id]);
    $u = $stmt->fetch();

    if ($u) $defi_data['nom'] = $u['identifiant'];

    $stmt = $pdo->prepare("
        SELECT MAX(score) AS best_score
        FROM resultatquiz
        WHERE idutilisateur = ? AND idquiz = ?
    ");
    $stmt->execute([$defi_id, $id_quiz]);
    $r = $stmt->fetch();

    if ($r && $r['best_score'] !== null) {
        $defi_data['score'] = (int)$r['best_score'];
    }
}

$json_data = json_encode($quiz_data);
$defi_json = json_encode($defi_data, JSON_UNESCAPED_UNICODE);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Quiz</title>
    <link rel="stylesheet" href="style.css">

    <style>
    .game-container {
        max-width: 800px;
        margin: auto;
        padding: 20px;
        text-align: center;
    }

    #question-text {
        font-size: 1.6em;
        margin: 20px 0;
    }

    #media-box {
        text-align: center;
        margin: 12px auto;
    }

    #media-box.hidden {
        display: none;
    }

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

    #answers-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        margin-top: 20px;
    }

    .answer-btn {
        padding: 18px;
        border: none;
        border-radius: 12px;
        font-size: 1.1em;
        cursor: pointer;
        transition: 0.2s;
        color: white;
    }

    .color-0 { background: #3498db; }
    .color-1 { background: #e67e22; }
    .color-2 { background: #9b59b6; }
    .color-3 { background: #2ecc71; }

    .answer-btn:hover {
        transform: scale(1.04);
        opacity: 0.95;
    }

    #feedback {
        margin-top: 15px;
        font-size: 1.2em;
    }

    .correct {
        color: #2ecc71;
        font-weight: bold;
        font-size: 1.2em;
    }

    .wrong {
        color: #e74c3c;
        font-weight: bold;
        font-size: 1.2em;
    }
    </style>
</head>

<body>

<div class="game-container">

<h1><?= htmlspecialchars($quiz['titre']) ?></h1>

<h2 id="question-text"></h2>

<div id="media-box"></div>

<div id="answers-grid"></div>

<div id="feedback"></div>

</div>

<script>

const data = <?= $json_data ?>;
const defi = <?= $defi_json ?>;

let i = 0;
let score = 0;

const qText = document.getElementById("question-text");
const grid = document.getElementById("answers-grid");
const feedback = document.getElementById("feedback");
const mediaBox = document.getElementById("media-box");

function load() {

    if (i >= data.length) return end();

    const q = data[i];

    qText.innerText = q.sujet;
    grid.innerHTML = "";
    mediaBox.innerHTML = "";
    feedback.innerHTML = "";

    if (q.media) {

        if (q.media_type === "image") {
            mediaBox.innerHTML = `<img src="${q.media}">`;
        }

        if (q.media_type === "audio") {
            mediaBox.innerHTML = `<audio controls src="${q.media}"></audio>`;
        }

        if (q.media_type === "video") {
            mediaBox.innerHTML = `<video controls src="${q.media}"></video>`;
        }
    }

    q.reponses.forEach((r, index) => {

        const btn = document.createElement("button");
        btn.className = "answer-btn color-" + index;
        btn.innerText = r.contenu;

        btn.onclick = () => {

            document.querySelectorAll(".answer-btn").forEach(b => b.disabled = true);

            if (r.estvraie == 1) {
                score += 100;
                feedback.innerHTML = "<p class='correct'>Bonne réponse +100</p>";
            } else {
                feedback.innerHTML = "<p class='wrong'>Mauvaise réponse</p>";
            }

            setTimeout(() => {
                i++;
                load();
            }, 1000);
        };

        grid.appendChild(btn);
    });
}

function end() {

    let html = `<h3>Score final : ${score}</h3>`;

    if (defi.score !== null) {

        html += `<h4>Score de ${defi.nom} : ${defi.score}</h4>`;

        if (score > defi.score) {
            html += `<p class='correct'>Vous avez gagné 🎉</p>`;
        } else if (score < defi.score) {
            html += `<p class='wrong'>Vous avez perdu 😢</p>`;
        } else {
            html += `<p>Égalité 🤝</p>`;
        }
    }

    html += `
        <br><br>
        <a href="index.php" class="btn">Retour à l'accueil</a>
    `;

    grid.innerHTML = html;

    fetch("sauvegarder_score.php", {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: `score=${score}&idquiz=<?= $id_quiz ?>`
    })
    .then(r => r.json())
    .then(d => {
        feedback.innerHTML += `<p>${d.message}</p>`;
    });
}

load();

</script>

</body>
</html>