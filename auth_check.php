<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    // Redirigez vers la page de connexion si non connecté
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $scriptDir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');

    // Si on est dans /request, on remonte à la racine de l'app
    $appDir = $scriptDir;
    if (preg_match('#/request$#', $scriptDir)) {
        $appDir = rtrim(str_replace('\\', '/', dirname($scriptDir)), '/');
    }
    if ($appDir === '') {
        $appDir = '/';
    }

    header('Location: ' . $appDir . '/login.php');
    exit();
}

// Vous pouvez également ajouter des contrôles de permissions ici si nécessaire
// Exemple: vérifiez si l'utilisateur a le droit de visualiser une page spécifique
// if (!$user->hasPermission($_SESSION['user_id'], 'view_page')) {
//     die("Accès refusé.");
// }
