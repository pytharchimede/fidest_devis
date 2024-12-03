<?php
require_once 'model/Database.php';
$pdo = Database::getConnection();

if (isset($_GET['id'])) {
    $userId = $_GET['id'];

    // Désactiver l'utilisateur
    $stmt = $pdo->prepare("UPDATE user_devis SET active = 1 WHERE id = :id");
    $stmt->execute(['id' => $userId]);

    // Redirection après la désactivation
    header('Location: liste_utilisateur.php');
    exit;
}
