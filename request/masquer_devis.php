<?php

session_start();
include('../fpdf186/fpdf.php');
include('../../logi/connex.php');
require_once("../phpqrcode/qrlib.php");

// Vérifiez que le devisId est défini dans la session ou dans l'URL
if (!isset($_SESSION['devisId']) && !isset($_GET['devisId'])) {
    die('ID de devis non défini.');
}

// Prioriser l'ID du devis reçu via $_GET
if (isset($_GET['devisId'])) {
    $devisId = $_GET['devisId'];
    $_SESSION['devisId'] = $devisId; // Mettre à jour la session avec le nouvel ID
} else {
    $devisId = $_SESSION['devisId'];
}

// Déboguer pour vérifier la valeur de $devisId
var_dump($devisId);

// Récupérer les données du devis depuis la base de données
$stmt = $con->prepare("SELECT * FROM devis WHERE id = ?");
$stmt->execute([$devisId]);

$nbFind = $stmt->rowcount();

if($nbFind>0){
    $stmt = $con->prepare("UPDATE devis SET masque=1 WHERE id = ?");
    $stmt->execute([$devisId]);
}

unset($_SESSION['devisId']);

header('Location: ../liste_devis.php');

exit();
?>
