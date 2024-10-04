<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Devis - BTP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            background-color: #f7f9fc;
        }

        .container {
            margin-top: 2rem;
        }

        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            background-color: #ffffff;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 12px rgba(0, 0, 0, 0.15);
        }

        .card-header {
            background-color: #1d2b57;
            color: #ffffff;
            font-weight: bold;
            text-align: center;
            padding: 1rem;
            border-radius: 10px 10px 0 0;
        }

        .card-body {
            padding: 1rem;
        }

        .card-footer {
            background-color: #f8f9fa;
            padding: 0.5rem 1rem;
            border-radius: 0 0 10px 10px;
            text-align: center;
        }

        .card-footer a {
            color: #007bff;
            text-decoration: none;
            font-weight: bold;
            transition: color 0.3s ease;
        }

        .card-footer a:hover {
            color: #0056b3;
        }

        .btn-primary {
            background-color: #1d2b57;
            border-color: #1d2b57;
        }

        .btn-primary:hover {
            background-color: #14203e;
            border-color: #14203e;
        }

        .navbar-brand img {
            height: 50px;
        }

        footer {
            margin-top: 3rem;
        }

        .social-icons a {
            color: #1d2b57;
            margin: 0 10px;
            transition: color 0.3s ease;
        }

        .social-icons a:hover {
            color: #fabd02;
        }
    </style>
</head>
<body>

    <!-- Menu -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <img src="https://app.fidest.ci/logi/img/logo_connex.jpg" alt="Logo">
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
                        <a class="nav-link" href="generer_devis.php">Générer un devis</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="liste_devis.php">Liste des devis</a>
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

    <div class="container">
     
         
         <?php
         
                     include('../logi/connex.php');
            
          // Initialiser la requête SQL de base
            $sql = 'SELECT * FROM devis WHERE masque=0';
            
            // Ajouter les filtres en fonction des paramètres fournis
            if (isset($_GET['date_debut']) && !empty($_GET['date_debut'])) {
                $sql .= ' AND date_emission >= :date_debut';
            }
            if (isset($_GET['date_fin']) && !empty($_GET['date_fin'])) {
                $sql .= ' AND date_emission <= :date_fin';
            }
            if (isset($_GET['emis_par']) && !empty($_GET['emis_par'])) {
                $sql .= ' AND emis_par LIKE :emis_par';
            }
            if (isset($_GET['destine_a']) && !empty($_GET['destine_a'])) {
                $sql .= ' AND destine_a LIKE :destine_a';
            }
            
            // Préparer et exécuter la requête
            $query = $con->prepare($sql.' ORDER BY id DESC ');
            
            if (isset($_GET['date_debut']) && !empty($_GET['date_debut'])) {
                $query->bindParam(':date_debut', $_GET['date_debut']);
            }
            if (isset($_GET['date_fin']) && !empty($_GET['date_fin'])) {
                $query->bindParam(':date_fin', $_GET['date_fin']);
            }
            if (isset($_GET['emis_par']) && !empty($_GET['emis_par'])) {
                $emis_par = '%' . $_GET['emis_par'] . '%';
                $query->bindParam(':emis_par', $emis_par);
            }
            if (isset($_GET['destine_a']) && !empty($_GET['destine_a'])) {
                $destine_a = '%' . $_GET['destine_a'] . '%';
                $query->bindParam(':destine_a', $destine_a);
            }
            
            $query->execute();
            $devis = $query->fetchAll(PDO::FETCH_ASSOC);
            
            // Calcul du montant total TTC des devis affichés
            $total_ttc = 0;
            $nb_devis = 0;
            foreach ($devis as $de) {
                $total_ttc += $de['total_ttc'];
                $nb_devis++;
            }
         
         ?>
         
    <h1 class="text-center mb-4">Liste des Devis (<?php echo $nb_devis; ?>)</h1>


    <!-- Formulaire de recherche -->
    <form method="GET" action="liste_devis.php" class="row g-3 mb-4">
        <div class="col-md-3">
            <label for="date_debut" class="form-label">Date début</label>
            <input type="date" id="date_debut" name="date_debut" class="form-control">
        </div>
        <div class="col-md-3">
            <label for="date_fin" class="form-label">Date fin</label>
            <input type="date" id="date_fin" name="date_fin" class="form-control">
        </div>
        <div class="col-md-3">
            <label for="emis_par" class="form-label">Émis par</label>
            <input type="text" id="emis_par" name="emis_par" class="form-control" placeholder="Nom de l'émetteur">
        </div>
        <div class="col-md-3">
            <label for="destine_a" class="form-label">Destiné à</label>
            <input type="text" id="destine_a" name="destine_a" class="form-control" placeholder="Nom du destinataire">
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-primary mt-4">Rechercher</button>
        </div>
    </form>
    
      <!-- Display total amount -->
        <div class="mt-4">
            <h4 class="text-end">Montant Total TTC: <span class="text-success"><?php echo number_format($total_ttc, 0, ',', ' '); ?> FCFA</span></h4>
        </div>
    
        <!-- Button to export filtered quotes in PDF -->
        <div class="text-end mt-3">
            <a target="_blank" href="https://fidest.ci/devis/request/export_resultat.php?<?php echo http_build_query($_GET); ?>" class="btn btn-primary">
                <i class="fas fa-file-pdf"></i> Exporter en PDF
            </a>
            <a target="_blank" href="generer_devis.php" class="btn btn-primary">
                <i class="fas fa-plus-circle"></i> Ajouter un devis
            </a>
        </div>

    <!-- Button to redirect to generate quote -->
    <div class="text-center mb-4">
        &nbsp;
    </div>

        <!-- Grid displaying quotes -->
        <div class="card-grid">
            <!-- PHP code to fetch and display quotes from the database -->
            <?php

            foreach ($devis as $de) {
                echo '<div class="card">';
                    echo '<div class="card-header">' . htmlspecialchars($de['numero_devis']) . '</div>';
                    echo '<div class="card-body">';
                        echo '<p><strong>Délai Livraison:</strong> ' . htmlspecialchars($de['delai_livraison']) . '</p>';
                        echo '<p><strong>Date Émission:</strong> ' . htmlspecialchars($de['date_emission']) . '</p>';
                        echo '<p><strong>Date Expiration:</strong> ' . htmlspecialchars($de['date_expiration']) . '</p>';
                        echo '<p><strong>Émis Par:</strong> ' . htmlspecialchars($de['emis_par']) . '</p>';
                        echo '<p><strong>Destiné À:</strong> ' . htmlspecialchars($de['destine_a']) . '</p>';
                        echo '<p><strong>Total HT:</strong> ' . htmlspecialchars($de['total_ht']) . ' FCFA</p>';
                        echo '<p><strong>Total TTC:</strong> ' . htmlspecialchars($de['total_ttc']) . ' FCFA</p>';
                        echo '<p><strong>Date de Création:</strong> ' . htmlspecialchars($de['created_at']) . '</p>';
                    echo '</div>';
                    echo '<div class="card-footer">';
                        echo '<a target="_blank" href="https://fidest.ci/devis/request/export_pdf.php?devisId=' . $de['id'] . '"><i class="fas fa-eye"></i> Visualiser</a>';
                        echo '&nbsp;&nbsp;&nbsp;&nbsp';
                        echo '<a href="https://fidest.ci/devis/request/masquer_devis.php?devisId=' . $de['id'] . '"><i class="fas fa-eye-slash"></i> Masquer</a>';
                    echo '</div>';
                echo '</div>';
            }
            ?>
        </div>
        
    </div>

    <!-- Footer -->
    <footer class="footer text-white text-center py-3">
        <div class="container">
            <p>&copy; <?php echo gmdate('Y'); ?> FIDEST. Tous droits réservés.</p>
            <div class="social-icons">
                <a href="#" class="fab fa-facebook-f"></a>
                <a href="#" class="fab fa-twitter"></a>
                <a href="#" class="fab fa-linkedin-in"></a>
            </div>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
