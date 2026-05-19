<?php
// upload_media.php
// Appelé en AJAX (fetch) depuis creer_quiz.php et modifier_quiz.php
// Retourne JSON { "path": "uploads/xxx.jpg", "type": "image" } si valide

require 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Non connecté']);
    exit;
}

if (!isset($_FILES['media']) || $_FILES['media']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => 'Aucun fichier reçu ou erreur upload']);
    exit;
}

// Limites de taille et types autorisés
const MAX_SIZE_IMAGE = 5  * 1024 * 1024;   // 5 Mo
const MAX_SIZE_AUDIO = 10 * 1024 * 1024;   // 10 Mo
const MAX_SIZE_VIDEO = 30 * 1024 * 1024;   // 30 Mo

$allowed = [
    // images
    'image/jpeg' => ['ext' => 'jpg',  'type' => 'image', 'max' => MAX_SIZE_IMAGE],
    'image/png' => ['ext' => 'png',  'type' => 'image', 'max' => MAX_SIZE_IMAGE],
    'image/gif' => ['ext' => 'gif',  'type' => 'image', 'max' => MAX_SIZE_IMAGE],
    'image/webp' => ['ext' => 'webp', 'type' => 'image', 'max' => MAX_SIZE_IMAGE],

    // audio
    'audio/mpeg' => ['ext' => 'mp3',  'type' => 'audio', 'max' => MAX_SIZE_AUDIO],
    'audio/ogg' => ['ext' => 'ogg',  'type' => 'audio', 'max' => MAX_SIZE_AUDIO],
    'audio/wav' => ['ext' => 'wav',  'type' => 'audio', 'max' => MAX_SIZE_AUDIO],

    // vidéo
    'video/mp4' => ['ext' => 'mp4',  'type' => 'video', 'max' => MAX_SIZE_VIDEO],
    'video/webm' => ['ext' => 'webm', 'type' => 'video', 'max' => MAX_SIZE_VIDEO],
];

// Vérification MIME réelle (pas celle du navigateur)
$finfo    = new finfo(FILEINFO_MIME_TYPE);
$mime     = $finfo->file($_FILES['media']['tmp_name']);

if (!array_key_exists($mime, $allowed)) {
    echo json_encode(['error' => 'Type de fichier non autorisé : ' . $mime]);
    exit;
}

$info = $allowed[$mime];

if ($_FILES['media']['size'] > $info['max']) {
    $mo = $info['max'] / 1024 / 1024;
    echo json_encode(['error' => "Fichier trop lourd (max {$mo} Mo)"]);
    exit;
}

// Création du dossier uploads si inexistant
$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Nom unique
$filename = bin2hex(random_bytes(16)) . '.' . $info['ext'];
$dest = $uploadDir . $filename;

if (!move_uploaded_file($_FILES['media']['tmp_name'], $dest)) {
    echo json_encode(['error' => 'Impossible de déplacer le fichier']);
    exit;
}

echo json_encode([
    'path' => 'uploads/' . $filename,
    'type' => $info['type'],
]);
