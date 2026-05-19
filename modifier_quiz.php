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

    // Mise à jour quiz
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

    // Récupérer les anciens médias pour supprimer les fichiers orphelins
    $oldMediaStmt = $pdo->prepare("SELECT media FROM question WHERE idquiz = ?");
    $oldMediaStmt->execute([$id]);
    $oldMediaPaths = array_column($oldMediaStmt->fetchAll(), 'media');

    // Supprimer anciennes données
    $pdo->prepare("DELETE FROM reponse WHERE idquestion IN (SELECT id FROM question WHERE idquiz = ?)")
        ->execute([$id]);
    $pdo->prepare("DELETE FROM question WHERE idquiz = ?")
        ->execute([$id]);

    // Réinsertion complète
    $newMediaPaths = [];

    if (!empty($_POST['questions'])) {
        foreach ($_POST['questions'] as $q) {

            if (empty($q['sujet'])) continue;

            $media_path = !empty($q['media_path']) ? $q['media_path'] : null;
            $media_type = !empty($q['media_type']) ? $q['media_type'] : null;

            // Sécurité : restreindre au dossier uploads/
            if ($media_path && !preg_match('#^uploads/[a-f0-9]{32}\.[a-z0-9]{2,4}$#', $media_path)) {
                $media_path = null;
                $media_type = null;
            }

            if ($media_path) $newMediaPaths[] = $media_path;

            $stmtQ = $pdo->prepare("
                INSERT INTO question (sujet, idquiz, media, media_type)
                VALUES (?, ?, ?, ?)
            ");
            $stmtQ->execute([
                htmlspecialchars($q['sujet']),
                $id,
                $media_path,
                $media_type,
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

    // Supprimer les fichiers médias qui ne sont plus utilisés
    foreach ($oldMediaPaths as $oldPath) {
        if ($oldPath && !in_array($oldPath, $newMediaPaths)) {
            $full = __DIR__ . '/' . $oldPath;
            if (file_exists($full)) unlink($full);
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
    <title>Projet quiz - Modifier quiz</title>
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
    .media-zone:hover { border-color: #666; }
    .media-zone.has-media { border-style: solid; border-color: #4CAF50; }
    .media-preview { max-width: 100%; max-height: 180px; margin-top: 8px; border-radius: 4px; }
    .media-preview-audio { width: 100%; margin-top: 8px; }
    .media-preview-video { max-width: 100%; max-height: 180px; margin-top: 8px; border-radius: 4px; }
    .upload-status { font-size: .85em; color: #555; margin-top: 4px; }
    .upload-status.ok { color: #2e7d32; }
    .upload-status.err { color: #c62828; }
    .btn-remove-media { background: none; border: none; color: #c00; cursor: pointer; padding: 2px 0; font-size: .9em; }
    </style>
</head>

<body>

<div class="container" style="max-width:700px;">

<h1>Modification quiz</h1>
<form method="POST">

    <!-- Information quiz -->
    <label>Titre</label>
    <input type="text" name="titre" value="<?= htmlspecialchars($quiz['titre']) ?>" required>

    <label>Catégorie</label>
    <select name="categorie">
        <?php foreach (['Aucune','Sport','Culture générale','Divertissement'] as $cat): ?>
        <option value="<?= $cat ?>" <?= $quiz['categorie'] === $cat ? 'selected' : '' ?>>
            <?= $cat ?>
        </option>
        <?php endforeach; ?>
    </select>

    <label>Difficulté</label>
    <input type="number" name="difficulte" value="<?= $quiz['difficulte'] ?>" min="1" max="5">

    <hr>

    <h2>Questions</h2>

    <div id="questions-container">

    <?php foreach ($questions as $qi => $q): ?>
    <div class="question-block" data-index="<?= $qi ?>">

        <input type="text"
            name="questions[<?= $qi ?>][sujet]"
            value="<?= htmlspecialchars($q['sujet']) ?>"
            placeholder="Question..."
            required>

        <!-- Zone média -->
        <div class="media-zone <?= $q['media'] ? 'has-media' : '' ?>"
             id="zone-<?= $qi ?>"
             onclick="document.getElementById('file-<?= $qi ?>').click()">
            <span id="zone-label-<?= $qi ?>">
                <?= $q['media']
                    ? 'Média actuel (cliquer pour remplacer)'
                    : 'Ajouter un média (image, son, vidéo)' ?>
            </span>
            <div id="preview-<?= $qi ?>">
                <?php if ($q['media']): ?>
                    <?php if ($q['media_type'] === 'image'): ?>
                        <img src="<?= htmlspecialchars($q['media']) ?>" class="media-preview">
                    <?php elseif ($q['media_type'] === 'audio'): ?>
                        <audio src="<?= htmlspecialchars($q['media']) ?>" controls class="media-preview-audio"></audio>
                    <?php elseif ($q['media_type'] === 'video'): ?>
                        <video src="<?= htmlspecialchars($q['media']) ?>" controls class="media-preview-video"></video>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="upload-status <?= $q['media'] ? 'ok' : '' ?>" id="status-<?= $qi ?>">
            <?= $q['media'] ? 'Média enregistré' : '' ?>
        </div>

        <!-- Input file caché -->
        <input type="file"
               id="file-<?= $qi ?>"
               accept="image/*,audio/*,video/mp4,video/webm"
               style="display:none"
               onchange="uploadMedia(this, <?= $qi ?>)">

        <?php if ($q['media']): ?>
        <button type="button" class="btn-remove-media" onclick="removeMedia(<?= $qi ?>)">
            Supprimer le média
        </button>
        <?php endif; ?>

        <!-- Champs cachés remplis après upload -->
        <input type="hidden" name="questions[<?= $qi ?>][media_path]"
               id="media-path-<?= $qi ?>"
               value="<?= htmlspecialchars($q['media'] ?? '') ?>">
        <input type="hidden" name="questions[<?= $qi ?>][media_type]"
               id="media-type-<?= $qi ?>"
               value="<?= htmlspecialchars($q['media_type'] ?? '') ?>">

        <!-- Réponses -->
        <?php for ($i = 0; $i < 4; $i++): ?>
            <input type="text"
                name="questions[<?= $qi ?>][reponses][<?= $i ?>]"
                value="<?= htmlspecialchars($q['reponses'][$i]['contenu']) ?>"
                placeholder="Option <?= $i + 1 ?>"
                required>
        <?php endfor; ?>

        <select name="questions[<?= $qi ?>][correct]">
            <?php for ($i = 0; $i < 4; $i++): ?>
                <option value="<?= $i ?>" <?= !empty($q['reponses'][$i]['estvraie']) ? 'selected' : '' ?>>
                    Bonne réponse <?= $i + 1 ?>
                </option>
            <?php endfor; ?>
        </select>

        <button type="button" onclick="removeQuestion(this)" style="margin-top:8px;">
            Supprimer
        </button>

    </div>
    <?php endforeach; ?>

    </div><!-- /questions-container -->

    <button type="button" onclick="addQuestion()">Ajouter une question</button>

    <br><br>

    <button type="submit" class="btn">Enregistrer modifications</button>

</form>

</div>

<script>
let index = <?= count($questions) ?>;

// Fonction ajout de question
function addQuestion() {
    const container = document.getElementById('questions-container');

    const html = `
    <div class="question-block" data-index="${index}">

        <input type="text" name="questions[${index}][sujet]" placeholder="Question..." required>

        <div class="media-zone" id="zone-${index}" onclick="document.getElementById('file-${index}').click()">
            <span id="zone-label-${index}">📎 Ajouter un média (image, son, vidéo)</span>
            <div id="preview-${index}"></div>
        </div>
        <div class="upload-status" id="status-${index}"></div>

        <input type="file" id="file-${index}"
               accept="image/*,audio/*,video/mp4,video/webm"
               style="display:none"
               onchange="uploadMedia(this, ${index})">

        <input type="hidden" name="questions[${index}][media_path]" id="media-path-${index}">
        <input type="hidden" name="questions[${index}][media_type]" id="media-type-${index}">

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

        <button type="button" onclick="removeQuestion(this)" style="margin-top:8px;">
            Supprimer
        </button>

    </div>`;

    container.insertAdjacentHTML('beforeend', html);
    index++;
}

function removeQuestion(btn) {
    btn.closest('.question-block').remove();
}

// Fonction suppression média
function removeMedia(i) {
    document.getElementById(`media-path-${i}`).value = '';
    document.getElementById(`media-type-${i}`).value = '';
    document.getElementById(`preview-${i}`).innerHTML = '';
    document.getElementById(`zone-label-${i}`).textContent = '📎 Ajouter un média (image, son, vidéo)';
    document.getElementById(`zone-${i}`).classList.remove('has-media');
    const st = document.getElementById(`status-${i}`);
    st.textContent = 'Média retiré (supprimé à l\'enregistrement)';
    st.className   = 'upload-status';
}

// Fonction upload média
async function uploadMedia(input, i) {
    const file = input.files[0];
    if (!file) return;

    const status  = document.getElementById(`status-${i}`);
    const zone    = document.getElementById(`zone-${i}`);
    const label   = document.getElementById(`zone-label-${i}`);
    const preview = document.getElementById(`preview-${i}`);

    status.textContent = 'Upload en cours…';
    status.className   = 'upload-status';
    preview.innerHTML  = '';

    const fd = new FormData();
    fd.append('media', file);

    try {
        const res  = await fetch('upload_media.php', { method: 'POST', body: fd });
        const json = await res.json();

        if (json.error) {
            status.textContent = 'Erreur: ' + json.error;
            status.className   = 'upload-status err';
            return;
        }

        document.getElementById(`media-path-${i}`).value = json.path;
        document.getElementById(`media-type-${i}`).value = json.type;

        label.textContent = 'Validé: ' + file.name;
        zone.classList.add('has-media');
        preview.innerHTML = '';

        if (json.type === 'image') {
            const img = document.createElement('img');
            img.src = json.path; img.className = 'media-preview';
            preview.appendChild(img);
        } else if (json.type === 'audio') {
            const au = document.createElement('audio');
            au.src = json.path; au.controls = true; au.className = 'media-preview-audio';
            preview.appendChild(au);
        } else if (json.type === 'video') {
            const vi = document.createElement('video');
            vi.src = json.path; vi.controls = true; vi.className = 'media-preview-video';
            preview.appendChild(vi);
        }

        status.textContent = 'Média prêt';
        status.className   = 'upload-status ok';

    } catch (e) {
        status.textContent = 'Erreur réseau: ' + e.message;
        status.className   = 'upload-status err';
    }
}
</script>

</body>
</html>