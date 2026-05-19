<?php 
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Création quiz
    $stmt = $pdo->prepare("
        INSERT INTO quiz (titre, categorie, difficulte, idutilisateur) 
        VALUES (?, ?, ?, ?)
    ");

    $stmt->execute([
        $_POST['titre'],
        $_POST['categorie'],
        intval($_POST['difficulte']),
        $_SESSION['user_id']
    ]);

    $id_quiz = $pdo->lastInsertId();

    // Questions
    if (!empty($_POST['questions']) && is_array($_POST['questions'])) {

        foreach ($_POST['questions'] as $q) {

            if (empty($q['sujet'])) continue;

            $media_path = !empty($q['media_path']) ? $q['media_path'] : null;
            $media_type = !empty($q['media_type']) ? $q['media_type'] : null;

            // Sécurité
            if ($media_path && !preg_match('#^uploads/[a-f0-9]{32}\.[a-z0-9]{2,4}$#', $media_path)) {
                $media_path = null;
                $media_type = null;
            }

            // insertion question
            $stmtQ = $pdo->prepare("
                INSERT INTO question (sujet, idquiz, media, media_type) 
                VALUES (?, ?, ?, ?)
            ");

            $stmtQ->execute([
                $q['sujet'],
                $id_quiz,
                $media_path,
                $media_type
            ]);

            $id_question = $pdo->lastInsertId();

            // réponses
            if (!empty($q['reponses']) && is_array($q['reponses'])) {

                for ($i = 0; $i < 4; $i++) {

                    if (!isset($q['reponses'][$i])) continue;

                    $estVraie = (
                        isset($q['correct']) &&
                        (int)$q['correct'] === $i
                    ) ? 1 : 0;

                    $stmtR = $pdo->prepare("
                        INSERT INTO reponse (contenu, estvraie, idquestion) 
                        VALUES (?, ?, ?)
                    ");

                    $stmtR->execute([
                        $q['reponses'][$i],
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
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Projet quiz - Création quiz</title>
    <link rel="stylesheet" href="style.css">

    <style>
    .question-block {
        border: 1px solid #ddd;
        padding: 15px;
        margin-bottom: 15px;
        border-radius: 8px;
        background: #fafafa;
    }

    .media-zone {
        margin: 10px 0;
        padding: 10px;
        border: 2px dashed #bbb;
        border-radius: 6px;
        text-align: center;
        cursor: pointer;
        transition: border-color .2s;
    }

    .media-zone:hover {
        border-color: #666;
    }

    .media-zone.has-media {
        border-style: solid;
        border-color: #4CAF50;
    }

    .media-preview {
        max-width: 100%;
        max-height: 180px;
        margin-top: 8px;
        border-radius: 4px;
    }

    .media-preview-audio {
        width: 100%;
        margin-top: 8px;
    }

    .media-preview-video {
        max-width: 100%;
        max-height: 180px;
        margin-top: 8px;
        border-radius: 4px;
    }

    .upload-status {
        font-size: .85em;
        color: #555;
        margin-top: 4px;
    }

    .upload-status.ok {
        color: #2e7d32;
    }

    .upload-status.err {
        color: #c62828;
    }

    .btn-remove-media {
        background: none;
        border: none;
        color: #c00;
        cursor: pointer;
        padding: 2px 0;
        font-size: .9em;
    }
    </style>
</head>

<body>

<div class="container" style="max-width:700px;">

<h1>Nouveau quiz</h1>

<form method="POST">

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

    <h2>Questions</h2>

    <div id="questions-container">

        <div class="question-block" data-index="0">

            <input type="text"
                   name="questions[0][sujet]"
                   placeholder="Question..."
                   required>

            <!-- MEDIA -->
            <div class="media-zone"
                 id="zone-0"
                 onclick="document.getElementById('file-0').click()">

                <span id="zone-label-0">
                    Ajouter un média (image, son, vidéo)
                </span>

                <div id="preview-0"></div>
            </div>

            <div class="upload-status" id="status-0"></div>

            <input type="file"
                   id="file-0"
                   accept="image/*,audio/*,video/mp4,video/webm"
                   style="display:none"
                   onchange="uploadMedia(this, 0)">

            <button type="button"
                    class="btn-remove-media"
                    onclick="removeMedia(0)">
                Supprimer le média
            </button>

            <input type="hidden"
                   name="questions[0][media_path]"
                   id="media-path-0">

            <input type="hidden"
                   name="questions[0][media_type]"
                   id="media-type-0">

            <!-- REPONSES -->
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

            <button type="button" onclick="removeQuestion(this)">
                Supprimer
            </button>

        </div>

    </div>

    <button type="button" onclick="addQuestion()">
        Ajouter une question
    </button>

    <br><br>

    <button type="submit" class="btn">
        Publier quiz
    </button>

</form>

</div>

<script>
let index = 1;

function addQuestion() {

    const container = document.getElementById('questions-container');

    const html = `
    <div class="question-block" data-index="${index}">

        <input type="text"
               name="questions[${index}][sujet]"
               placeholder="Question..."
               required>

        <div class="media-zone"
             id="zone-${index}"
             onclick="document.getElementById('file-${index}').click()">

            <span id="zone-label-${index}">
                Ajouter un média (image, son, vidéo)
            </span>

            <div id="preview-${index}"></div>
        </div>

        <div class="upload-status" id="status-${index}"></div>

        <input type="file"
               id="file-${index}"
               accept="image/*,audio/*,video/mp4,video/webm"
               style="display:none"
               onchange="uploadMedia(this, ${index})">

        <button type="button"
                class="btn-remove-media"
                onclick="removeMedia(${index})">
            Supprimer le média
        </button>

        <br></br>

        <input type="hidden"
               name="questions[${index}][media_path]"
               id="media-path-${index}">

        <input type="hidden"
               name="questions[${index}][media_type]"
               id="media-type-${index}">

        <input type="text" name="questions[${index}][reponses][0]" placeholder="Option 1" required>
        <input type="text" name="questions[${index}][reponses][1]" placeholder="Option 2" required>
        <input type="text" name="questions[${index}][reponses][2]" placeholder="Option 3" required>
        <input type="text" name="questions[${index}][reponses][3]" placeholder="Option 4" required>

        <select name="questions[${index}][correct]">
            <option value="0">Bonne réponse 1</option>
            <option value="1">Bonne réponse 2</option>
            <option value="2">Bonne réponse 3</option>
            <option value="3">Bonne réponse 4</option>
        </select>

        <button type="button" onclick="removeQuestion(this)">
            Supprimer
        </button>

    </div>`;

    container.insertAdjacentHTML('beforeend', html);
    index++;
}

function removeQuestion(btn) {
    btn.closest('.question-block').remove();
}

function removeMedia(i) {

    document.getElementById(`media-path-${i}`).value = '';
    document.getElementById(`media-type-${i}`).value = '';
    document.getElementById(`preview-${i}`).innerHTML = '';

    document.getElementById(`zone-label-${i}`).textContent =
        'Ajouter un média (image, son, vidéo)';

    document.getElementById(`zone-${i}`).classList.remove('has-media');

    const st = document.getElementById(`status-${i}`);

    st.textContent = 'Média retiré';
    st.className = 'upload-status';
}

async function uploadMedia(input, i) {

    const file = input.files[0];

    if (!file) return;

    const status  = document.getElementById(`status-${i}`);
    const zone    = document.getElementById(`zone-${i}`);
    const label   = document.getElementById(`zone-label-${i}`);
    const preview = document.getElementById(`preview-${i}`);

    status.textContent = 'Upload en cours…';
    status.className = 'upload-status';

    preview.innerHTML = '';

    const fd = new FormData();
    fd.append('media', file);

    try {

        const res = await fetch('upload_media.php', {
            method: 'POST',
            body: fd
        });

        const json = await res.json();

        if (json.error) {
            status.textContent = 'Erreur: ' + json.error;
            status.className = 'upload-status err';
            return;
        }

        document.getElementById(`media-path-${i}`).value = json.path;
        document.getElementById(`media-type-${i}`).value = json.type;

        label.textContent = 'Validé: ' + file.name;

        zone.classList.add('has-media');

        if (json.type === 'image') {

            const img = document.createElement('img');

            img.src = json.path;
            img.className = 'media-preview';

            preview.appendChild(img);

        } else if (json.type === 'audio') {

            const au = document.createElement('audio');

            au.src = json.path;
            au.controls = true;
            au.className = 'media-preview-audio';

            preview.appendChild(au);

        } else if (json.type === 'video') {

            const vi = document.createElement('video');

            vi.src = json.path;
            vi.controls = true;
            vi.className = 'media-preview-video';

            preview.appendChild(vi);
        }

        status.textContent = 'Média prêt';
        status.className = 'upload-status ok';

    } catch (e) {

        status.textContent = 'Erreur réseau: ' + e.message;
        status.className = 'upload-status err';
    }
}
</script>

</body>
</html>