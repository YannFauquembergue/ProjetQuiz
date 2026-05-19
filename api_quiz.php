<?php
header('Content-Type: application/json');
require 'config.php';

$id_quiz = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_quiz === 0) {
    echo json_encode(['erreur' => 'Identifiant de quiz manquant']);
    exit;
}

$stmt = $pdo->prepare("SELECT titre FROM quiz WHERE id = ?");
$stmt->execute([$id_quiz]);
$quiz = $stmt->fetch();

if (!$quiz) {
    echo json_encode(['erreur' => 'Ce défi n\'existe pas']);
    exit;
}

$stmtQ = $pdo->prepare("SELECT id, sujet, media, media_type FROM question WHERE idquiz = ?");
$stmtQ->execute([$id_quiz]);
$questions = $stmtQ->fetchAll();

$questions_pack = [];
foreach ($questions as $question) {
    $stmtR = $pdo->prepare("SELECT id, contenu, estvraie FROM reponse WHERE idquestion = ?");
    $stmtR->execute([$question['id']]);
    $reponses = $stmtR->fetchAll();

    $questions_pack[] = [
        'id'         => $question['id'],
        'sujet'      => $question['sujet'],
        'media'      => $question['media'],       // chemin relatif ou null
        'media_type' => $question['media_type'],  // 'image' | 'audio' | 'video' | null
        'reponses'   => $reponses
    ];
}

echo json_encode([
    'titre'     => $quiz['titre'],
    'questions' => $questions_pack
]);