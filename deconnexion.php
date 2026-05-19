<?php
require 'config.php';

// On vide toutes les variables de session actives
$_SESSION = array();

// Si un cookie de session existe, on le détruit en changeant sa date d'expiration
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// On détruit officiellement la session sur le serveur
session_destroy();

// Redirection vers l'écran d'authentification
header("Location: connexion.php");
exit;
?>