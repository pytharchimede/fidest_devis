<?php

session_start();

include('../../logi/connex.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Récupération de l'identifiant du devis
    $devisId = isset($_POST['devisId']) ? $_POST['devisId'] : '';

    $emisPar = isset($_POST['emisPar']) ? $_POST['emisPar'] : '';
    $destineA = isset($_POST['destineA']) ? $_POST['destineA'] : '';
    $delaiLivraison = isset($_POST['delaiLivraison']) ? $_POST['delaiLivraison'] : '';
    $dateEmission = isset($_POST['dateEmission']) ? $_POST['dateEmission'] : '';
    $dateExpiration = isset($_POST['dateExpiration']) ? $_POST['dateExpiration'] : '';
    $termesConditions = isset($_POST['termesConditions']) ? $_POST['termesConditions'] : '';
    $piedDePage = isset($_POST['piedDePage']) ? $_POST['piedDePage'] : '';
    $totalHT = isset($_POST['totalHT']) ? $_POST['totalHT'] : '0';
    $totalTTC = isset($_POST['totalTTC']) ? $_POST['totalTTC'] : '0';
    $tva = isset($_POST['tva']) ? $_POST['tva'] : '0';
    $clientId = isset($_POST['client_id']) ? $_POST['client_id'] : null;
    $offreId = isset($_POST['offre_id']) ? $_POST['offre_id'] : null;
    $tvaFacturable = isset($_POST['tvaFacturable']) ? $_POST['tvaFacturable'] : '0';
    $publierDevis = isset($_POST['publierDevis']) ? $_POST['publierDevis'] : '0';
    $correspondant = isset($_POST['correspondant']) ? $_POST['correspondant'] : '';
    
    $logo = '';
    // Gestion du logo
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['logo']['tmp_name'];
        $fileName = $_FILES['logo']['name'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        // Définir le nouveau nom de fichier
        $newFileName = 'logo_' . $devisId . '.' . $fileExtension;
        $uploadFileDir = '../logo/';
        $dest_path = $uploadFileDir . $newFileName;

        // Déplacer le fichier dans le dossier de destination
        if (move_uploaded_file($fileTmpPath, $dest_path)) {
            $logo = $newFileName; // Stocker le nom du fichier pour l'insertion dans la base de données
        } else {
            echo "Erreur lors du déplacement du fichier.";
            exit;
        }
    }

    // Mise à jour du devis
    $stmt = $con->prepare("UPDATE devis SET emis_par = ?, destine_a = ?, delai_livraison = ?, date_emission = ?, date_expiration = ?, termes_conditions = ?, pied_de_page = ?, total_ht = ?, total_ttc = ?, logo = ?, client_id = ?, offre_id = ?, tva_facturable = ?, publier_devis = ?, tva = ?, correspondant = ? WHERE id = ?");
    
    $stmt->execute([$emisPar, $destineA, $delaiLivraison, $dateEmission, $dateExpiration, $termesConditions, $piedDePage, $totalHT, $totalTTC, $logo, $clientId, $offreId, $tvaFacturable, $publierDevis, $tva, $correspondant, $devisId]);

    // Enregistrement des lignes de devis
    $designations = $_POST['designation'];
    $prix = $_POST['prix'];
    $quantites = $_POST['quantite'];
    $tvas = $_POST['tva'];
    $remises = $_POST['remise'];
    $totaux = $_POST['total'];

    // Supprimer les lignes existantes pour ce devis avant d'ajouter les nouvelles
    $deleteStmt = $con->prepare("DELETE FROM ligne_devis WHERE devis_id = ?");
    $deleteStmt->execute([$devisId]);

    // Enregistrer chaque ligne de devis
    for ($i = 0; $i < count($designations); $i++) {
        $designation = $designations[$i];
        $prixUnitaire = $prix[$i];
        $quantite = $quantites[$i];
        $tva = $tvas[$i];
        $remise = $remises[$i];
        $total = $totaux[$i];

        // Enregistrer chaque ligne de devis
        $stmt = $con->prepare("INSERT INTO ligne_devis (devis_id, designation, prix, quantite, tva, remise, total) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$devisId, $designation, $prixUnitaire, $quantite, $tva, $remise, $total]);
    }

    echo "<h1>Devis mis à jour avec succès</h1>";

    $_SESSION['devisId'] = $devisId;
}
?>
