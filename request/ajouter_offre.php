<?php
include('../../logi/connex.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $num_offre = $_POST['num_offre'];
    $date_offre = $_POST['date_offre'];
    $reference_offre = $_POST['reference_offre'];
    $commercial_dedie = $_POST['commercial_dedie'];
    $date_creat_offre = $_POST['date_creat_offre'];

    $query = $con->prepare('INSERT INTO offre (num_offre, date_offre, reference_offre, commercial_dedie, date_creat_offre) VALUES (?, ?, ?, ?, ?)');
    $query->execute([$num_offre, $date_offre, $reference_offre, $commercial_dedie, $date_creat_offre]);

    header('Location: ../liste_offre.php');
}
?>
