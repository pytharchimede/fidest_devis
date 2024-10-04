<?php
session_start();
include('../../logi/connex.php');

// Préparer la requête pour récupérer le nombre de devis par jour
$query = "
    SELECT DATE(date_emission) AS date, COUNT(*) AS count
    FROM devis
    WHERE masque = 0
    GROUP BY DATE(date_emission)
    ORDER BY DATE(date_emission) ASC
";

$result = $con->query($query);

// Initialiser les tableaux pour les labels et les données
$labels = [];
$data = [];

// Récupérer les données et les ajouter aux tableaux
while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    $labels[] = $row['date'];
    $data[] = (int)$row['count'];
}

// Retourner les données au format JSON
echo json_encode([
    'labels' => $labels,
    'data' => $data
]);
?>
