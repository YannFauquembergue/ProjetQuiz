<?php
// deconnexion.php
require 'config.php';

// 1. On vide toutes les variables de session actives
$_SESSION = array();

// 2. Si un cookie de session existe, on le détruit en changeant sa date d'expiration
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. On détruit officiellement la session sur le serveur
session_destroy();

// 4. Redirection automatique vers l'écran d'authentification
header("Location: connexion.php");
exit;
?>