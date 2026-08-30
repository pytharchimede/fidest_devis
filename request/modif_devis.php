<?php

require_once __DIR__ . '/../auth_check.php';

require_once __DIR__ . '/../model/Database.php';
require_once __DIR__ . '/../bootstrap.php';

$con = \Database::getConnection();



if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Récupération de l'identifiant du devis

    $devisId = isset($_POST['devisId']) ? $_POST['devisId'] : '';



    $emisPar = app_branding()->issuerBlock();

    $destineA = isset($_POST['destineA']) ? $_POST['destineA'] : '';

    $delaiLivraisonJours = filter_var($_POST['delaiLivraison'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
    if ($delaiLivraisonJours === false) { http_response_code(422); exit('Le délai de livraison doit être une durée positive en jours.'); }
    $delaiLivraison = $delaiLivraisonJours . ' jours';

    $dateEmission = isset($_POST['dateEmission']) ? $_POST['dateEmission'] : '';

    $dateExpiration = isset($_POST['dateExpiration']) ? $_POST['dateExpiration'] : '';
    $dateFacturation = (string) ($_POST['dateFacturation'] ?? '') ?: null;

    $termesConditions = isset($_POST['termesConditions']) ? $_POST['termesConditions'] : '';

    $piedDePage = trim((string) ($_POST['piedDePage'] ?? '')) ?: app_branding()->footerBlock();

    $totalHT = isset($_POST['totalHT']) ? $_POST['totalHT'] : '0';

    $totalTTC = isset($_POST['totalTTC']) ? $_POST['totalTTC'] : '0';

    $tva = isset($_POST['tvaTotal']) ? $_POST['tvaTotal'] : '0';

    $clientId = isset($_POST['client_id']) ? $_POST['client_id'] : null;
    if ($clientId) {
        $clientStatement = $con->prepare('SELECT nom_client, localisation_client, commune_client, bp_client, pays_client FROM client WHERE id_client = ?');
        $clientStatement->execute([$clientId]);
        $client = $clientStatement->fetch(PDO::FETCH_ASSOC);
        if ($client) {
            $destineA = implode("\n", array_filter([
                $client['nom_client'], $client['localisation_client'], $client['commune_client'],
                $client['bp_client'], $client['pays_client'],
            ]));
        }
    }

    $originalOffer = $con->prepare('SELECT offre_id FROM devis WHERE id=?');$originalOffer->execute([$devisId]);
    $offreId = (int)$originalOffer->fetchColumn();

    $tvaFacturable = isset($_POST['tvaFacturable']) ? $_POST['tvaFacturable'] : '0';

    $publierDevis = isset($_POST['publierDevis']) ? $_POST['publierDevis'] : '0';

    $correspondant = isset($_POST['correspondant']) ? $_POST['correspondant'] : '';



    $logoStatement = $con->prepare('SELECT logo FROM devis WHERE id = ?');
    $logoStatement->execute([$devisId]);
    $logo = (string) $logoStatement->fetchColumn();

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

    $stmt = $con->prepare("UPDATE devis SET emis_par = ?, destine_a = ?, delai_livraison = ?, delai_livraison_jours = ?, date_emission = ?, date_expiration = ?, date_facturation_prevue = ?, termes_conditions = ?, pied_de_page = ?, total_ht = ?, total_ttc = ?, logo = ?, client_id = ?, offre_id = ?, tva_facturable = ?, publier_devis = ?, tva = ?, correspondant = ? WHERE id = ?");



    $stmt->execute([$emisPar, $destineA, $delaiLivraison, $delaiLivraisonJours, $dateEmission, $dateExpiration, $dateFacturation, $termesConditions, $piedDePage, $totalHT, $totalTTC, $logo, $clientId, $offreId, $tvaFacturable, $publierDevis, $tva, $correspondant, $devisId]);



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
