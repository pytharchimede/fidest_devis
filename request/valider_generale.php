<?php
// Inclure les fichiers nécessaires
require_once '../model/Database.php';
require_once '../model/Devis.php';

// Vérifier si l'ID du devis est passé dans l'URL
if (isset($_GET['devisId'])) {
    $pdo = Database::getConnection();
    // Récupérer l'ID du devis depuis l'URL
    $devisId = (int) $_GET['devisId'];

    // Créer une instance de la classe Devis
    $devis = new Devis($pdo);

    // Appeler la méthode validerDevis pour valider le devis
    $result = $devis->validerGenerale($devisId);

    // Si la validation a réussi, rediriger vers la page d'origine
    if ($result) {
        // Rediriger vers la page d'origine après la validation
        header('Location: ../liste_devis.php'); // Remplacez ce chemin par celui de la page vers laquelle vous voulez rediriger
        exit; // Assurez-vous de stopper l'exécution après la redirection
    } else {
        // Gérer l'échec de la validation si nécessaire
        echo "Une erreur est survenue lors de la validation du devis.";
    }
} else {
    // Si aucun devisId n'est passé dans l'URL, afficher un message d'erreur
    echo "L'ID du devis est manquant.";
}
