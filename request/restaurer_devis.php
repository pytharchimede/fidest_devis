<?php
session_start();
include('../logi/connex.php');

$devisId = isset($_GET['devisId']) ? (int)$_GET['devisId'] : 0;
if ($devisId <= 0) {
    http_response_code(400);
    echo 'devisId manquant';
    exit;
}

$stmt = $con->prepare('UPDATE devis SET masque=0 WHERE id = :id');
$stmt->execute([':id' => $devisId]);

header('Location: ../liste_corbeille.php?restaure=1');
exit;
