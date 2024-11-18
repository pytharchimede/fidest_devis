<?php
session_start();
// Inclure la connexion à la base de données
include('../logi/connex.php');



// Vérifier si l'identifiant du devis est passé en paramètre
if (isset($_GET['devisId'])) {
    $id_devis = $_GET['devisId'];
    
    // Requête pour récupérer les détails du devis
    $query = $con->prepare("SELECT * FROM devis WHERE id = :id");
    $query->execute(['id' => $id_devis]);
    $devis = $query->fetch();

    if (!$devis) {
        die("Devis non trouvé.");
    }
} else {
    die("Identifiant du devis manquant.");
}


// Récupérer les clients
$clients = $con->prepare('SELECT * FROM client');
$clients->execute();

// Récupérer les offres
$offres = $con->prepare('SELECT * FROM offre');
$offres->execute();

$lignes_devis = $con->prepare('SELECT * FROM ligne_devis WHERE devis_id=:A');
$lignes_devis->execute(array('A'=>$id_devis));

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rédaction de Devis - BTP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <!-- Menu -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <img style="width:auto; height:50px;" src="https://app.fidest.ci/logi/img/logo_connex.jpg" alt="Logo">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Accueil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="generer_devis.php">Générer un devis</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="liste_devis.php">Liste des devis</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="liste_facture.php">Liste des factures</a>
                    </li>
                       <li class="nav-item">
                        <a class="nav-link" href="liste_client.php">Liste des clients</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="liste_offre.php">Liste des offres</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <form id="devisForm" action="request/modif_devis.php" method="POST" enctype="multipart/form-data">

    <input type="hidden" id="devisId" name="devisId" placeholder="N° Devis" value="<?=$devis['devisId']?>" readonly>


    <div class="container mt-4">
        <h1 class="text-center">Rédiger un Devis</h1>
        
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="clientSelect" class="form-label">Sélectionner le client</label>
                <select class="form-control" id="clientSelect" name="client_id">
                    <option value="" disabled selected>Choisissez un client</option>
                    <?php while ($client = $clients->fetch(PDO::FETCH_ASSOC)): ?>
                        <option value="<?php echo $client['id_client']; ?>" <?php if($devis['client_id']==$client['id_client']){ echo 'selected'; } ?>>
                            <?php echo $client['nom_client']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label for="offreSelect" class="form-label">Sélectionner l'offre</label>
                <select class="form-control" id="offreSelect" name="offre_id">
                    <option value="" disabled selected>Choisissez une offre</option>
                    <?php while ($offre = $offres->fetch(PDO::FETCH_ASSOC)): ?>
                        <option value="<?php echo $offre['id_offre']; ?>" <?php if($devis['offre_id']==$offre['id_offre']){ echo 'selected'; } ?>>
                            <?php echo $offre['num_offre'] . ' - ' . $offre['reference_offre']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-8">
                <label for="delaiLivraison" class="form-label">Délai de livraison</label>
                <input type="text" class="form-control" id="delaiLivraison" name="delaiLivraison" placeholder="Délai de livraison" value="<?=$devis['delai_livraison']?>">
            </div>
            <div class="col-md-4">
                <label for="correspondant" class="form-label">Correspondant</label>
                <input type="text" class="form-control" id="correspondant" name="correspondant" placeholder="Correspondant" value="<?=$devis['correspondant']?>">
            </div>
        </div>
        
        <div class="checkbox_zone">
            <div class="form-group">
                <label for="tvaFacturable">TVA Facturable</label>
                <label class="switch">
                    <input type="checkbox" id="tvaFacturable" name="tvaFacturable" value="<?=$devis['tva_facturable']?>"  <?= $devis['tva_facturable'] == 1 ? 'checked' : '' ?>>
                    <span class="slider round"></span>
                </label>
            </div>
            
            <div class="form-group">
                <label for="publierDevis">Publier le devis</label>
                <label class="switch">
                    <input type="checkbox" id="publierDevis" name="publierDevis" value="<?=$devis['publier_devis']?>"  <?= $devis['publier_devis'] == 1 ? 'checked' : '' ?>>
                    <span class="slider round"></span>
                </label>
            </div>
        </div>

        <div class="d-flex align-items-start mb-4">

            <div id="logoUploadContainer" class="border rounded p-4" onclick="document.getElementById('logoUpload').click();">
                <img id="logoPreview" src="logo/<?=$devis['logo']?>" alt="Logo" class="img-fluid mb-2" style="max-height: 150px;">
                <input type="file" id="logoUpload" name="logoUpload" accept="image/*" style="display: none;" value="<?=$devis['logo']?>">
                <p id="logoMessage" class="text-muted logo-message">Logo</p>
            </div>

            <div class="spacer"></div> <!-- Spacer added here -->

            <div class="info-container ms-3">
                <div class="form-group">
                    <label for="numeroDevis" class="form-label">N° Devis</label>
                    <input type="text" class="form-control" id="numeroDevis" name="numeroDevis" placeholder="N° Devis" value="<?=$devis['numero_devis']?>" readonly>
                </div>
                <div class="form-group">
                    <label for="dateEmission" class="form-label">Date d'émission</label>
                    <input type="date" class="form-control" id="dateEmission" name="dateEmission" value="<?=$devis['date_emission']?>">
                </div>
                <div class="form-group">
                    <label for="dateExpiration" class="form-label">Date d'expiration</label>
                    <input type="date" class="form-control" id="dateExpiration" name="dateExpiration" value="<?=$devis['date_expiration']?>">
                </div>
            </div>
        </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="emisPar" class="form-label">Émetteur</label>
                    <textarea class="form-control" id="emisPar" name="emisPar" placeholder="Informations sur l'émetteur" rows="3"><?=$devis['emis_par']?></textarea>
                </div>
                <div class="col-md-6">
                    <label for="destineA" class="form-label">Destinataire</label>
                    <textarea class="form-control" id="destineA" name="destineA" placeholder="Informations sur le destinataire" rows="3"><?=$devis['destine_a']?></textarea>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered" id="devisTable">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Désignation</th>
                            <th>Prix</th>
                            <th>Quantité</th>
                            <th>TVA (%)</th>
                            <th>Remise (%)</th>
                            <th>Total</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($lignes_devis as $ligne){ ?>
                        <tr>
                            <td class="index">1</td>
                            <td><input type="text" class="form-control" name="designation[]" placeholder="Désignation" value="<?=$ligne['designation']?>"></td>
                            <td><input type="number" class="form-control prix" name="prix[]" placeholder="Prix" value="<?=$ligne['prix']?>"></td>
                            <td><input type="number" class="form-control quantite" name="quantite[]" placeholder="Quantité" value="<?=$ligne['quantite']?>"></td>
                            <td><input type="number" class="form-control tva" name="tva[]" placeholder="TVA" value="<?=$ligne['tva']?>"></td>
                            <td><input type="number" class="form-control remise" name="remise[]" placeholder="Remise" value="<?=$ligne['remise']?>"></td>
                            <td><input type="number" class="form-control total" name="total[]" value="<?=$ligne['total']?>" readonly></td>
                            <td>
                                <button type="button" class="btn btn-danger remove-row">-</button>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            <button type="button" class="btn btn-success mt-3" id="addRow">+ Ajouter une ligne</button>

    <!-- Additional Info Section -->
    <div class="row footer-info mt-4">
        <div class="col-md-8">
            <div class="form-group">
                <label for="termesConditions" class="form-label">Termes et conditions</label>
                <textarea class="form-control" id="termesConditions" name="termesConditions" rows="5" placeholder="Termes et conditions"><?=$devis['termes_conditions']?></textarea>
            </div>
            <div class="form-group mt-3">
                <label for="piedDePage" class="form-label">Pied de page</label>
                <textarea class="form-control" id="piedDePage" name="piedDePage" rows="5" placeholder="Pied de page"><?=$devis['pied_de_page']?></textarea>
            </div>
        </div>
       <div class="col-md-4">
            <div class="form-group mt-3">
                <label for="totalHT" class="form-label">Total HT</label>
                <input type="text" class="form-control" id="totalHT" name="totalHT" value="<?=$devis['total_ht']?>" readonly>
            </div>
            <div class="form-group mt-3 tvaZone">
                <label for="tva" class="form-label">TVA 18%</label>
                <input type="text" class="form-control" id="tva" name="tva" value="<?=$devis['tva']?>" readonly>
            </div>
            <div class="form-group mt-3">
                <label for="totalTTC" class="form-label">Total TTC</label>
                <input type="text" class="form-control" id="totalTTC" name="totalTTC" value="<?=$devis['total_ttc']?>" readonly>
            </div>
            <!-- Buttons -->
            <div class="btn-group d-flex flex-column mt-3">
                <button type="button" class="btn btn-primary mt-2" id="saveBtn" style="margin-bottom:2px;">
                    <i class="fas fa-save"></i> Enregistrer le Devis
                </button>
                <button type="button" class="btn btn-secondary" id="exportPdfBtn">
                    <i class="fas fa-file-pdf"></i> Exporter PDF
                </button>
            </div>
        </div>
    </div>
    </form>


    </div>


     <!-- Footer -->
     <footer class="footer">
        <div class="container">
            <div class="text-center">
                <p>&copy; <?php echo gmdate('Y'); ?> FIDEST. Tous droits réservés.</p>
                <div class="social-icons">
                    <a href="#" class="fab fa-facebook-f"></a>
                    <a href="#" class="fab fa-twitter"></a>
                    <a href="#" class="fab fa-linkedin-in"></a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js"></script>
    <!--Intégration de jquery/Ajax-->
    <script src="../logi/js/jquery_1.7.1_jquery.min.js"></script>
	<script src="js/function.js"></script> 
</body>
</html>
