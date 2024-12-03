<?php
require_once 'model/Database.php';
$pdo = Database::getConnection();

if (isset($_GET['id'])) {
    $userId = $_GET['id'];

    // Supprimer l'utilisateur
    $stmt = $pdo->prepare("DELETE FROM user_devis WHERE id = :id");
    $stmt->execute(['id' => $userId]);

    // Redirection après la suppression
    header('Location: liste_utilisateur.php');
    exit;
}
