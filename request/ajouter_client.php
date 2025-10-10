<?php
include('../../logi/connex.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collecte des données du formulaire
    $code_client = $_POST['code_client'];
    $nom_client = $_POST['nom_client'];
    $localisation_client = $_POST['localisation_client'];
    $commune_client = $_POST['commune_client'];
    $bp_client = $_POST['bp_client'];
    $pays_client = $_POST['pays_client'];
    $date_creat_client = $_POST['date_creat_client'];

    // Préparation de la requête d'insertion
    $query = $con->prepare('INSERT INTO client (code_client, nom_client, localisation_client, commune_client, bp_client, pays_client, date_creat_client) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $query->execute([$code_client, $nom_client, $localisation_client, $commune_client, $bp_client, $pays_client, $date_creat_client]);

    // Redirection après l'ajout
    header('Location: ../liste_client.php');
    exit();
}
?>
