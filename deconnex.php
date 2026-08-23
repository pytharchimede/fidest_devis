<?php
// Demarrer la session
session_start();

// Détruire toutes les données de session
session_unset();  // Supprime toutes les variables de session
session_destroy(); // Détruit la session

// Rediriger vers la page de connexion après la déconnexion
header('Location: login.php');
exit();
