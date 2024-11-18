<?php
session_start();
include('../../logi/connex.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $emisPar = isset($_POST['emisPar']) ? $_POST['emisPar'] : '';
    $destineA = isset($_POST['destineA']) ? $_POST['destineA'] : '';
    $numeroDevis = isset($_POST['numeroDevis']) ? $_POST['numeroDevis'] : '';
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

        // Définir le numéro de devis (à récupérer ou à calculer)
        $devisQuery = $con->prepare('SELECT COUNT(*) AS count FROM devis');
        $devisQuery->execute();
        $count = $devisQuery->fetchColumn();
        $index_actuel = $count + 1;

        // Définir le nouveau nom de fichier
        $newFileName = 'logo_' . $index_actuel . '.' . $fileExtension;
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
    
    
    $devis = $con->prepare('SELECT * FROM devis');
    $devis->execute();
    
    $nb_devis = $devis->rowcount();
    $index_actuel = $nb_devis+1;
    
    $numeroDevis = 'FI-DEV-PAB-'.$index_actuel;
    

    
    // Enregistrer le devis
    $stmt = $con->prepare("INSERT INTO devis (numero_devis, delai_livraison, date_emission, date_expiration, emis_par, destine_a, termes_conditions, pied_de_page, total_ht, total_ttc, logo, client_id, offre_id, tva_facturable, publier_devis, tva, correspondant) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$numeroDevis, $delaiLivraison, $dateEmission, $dateExpiration, $emisPar, $destineA, $termesConditions, $piedDePage, $totalHT, $totalTTC, $logo, $clientId, $offreId, $tvaFacturable, $publierDevis, $tva, $correspondant]);
    
    // Récupérer l'ID du devis nouvellement créé
    $devisId = $con->lastInsertId();
    
    // Enregistrement des lignes de devis
    $designations = $_POST['designation'];
    $prix = $_POST['prix'];
    $quantites = $_POST['quantite'];
    $tvas = $_POST['tva'];
    $remises = $_POST['remise'];
    $totaux = $_POST['total'];

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

    echo "<h1>Devis enregistré avec succès</h1>";
    
    $_SESSION['devisId'] = $devisId;
}
?>
