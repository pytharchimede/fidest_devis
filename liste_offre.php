<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Offres - BTP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }
        .card {
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .card-header {
            background-color: #1d2b57;
            color: #fff;
            text-align: center;
        }
        .card-body {
            background-color: #f8f9fa;
        }
    </style>
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
                        <a class="nav-link" href="generer_devis.php">Générer un devis</a>
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
                        <a class="nav-link active" href="liste_offre.php">Liste des offres</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1 class="text-center">Liste des Offres</h1>

        <!-- Button to trigger modal -->
        <div class="text-center mb-4">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addOfferModal">
                Ajouter une offre
            </button>
        </div>

        <!-- Cards displaying offers -->
        <div class="card-grid">
            <!-- PHP code to fetch and display offers from the database -->
            <?php
            include('../logi/connex.php');
            
            $query = $con->prepare('SELECT * FROM offre');
            $query->execute();
            $offers = $query->fetchAll(PDO::FETCH_ASSOC);

            foreach ($offers as $offer) {
                echo '<div class="card">';
                echo '<div class="card-header">Numéro d\'Offre: ' . htmlspecialchars($offer['num_offre']) . '</div>';
                echo '<div class="card-body">';
                echo '<p><strong>Date d\'Offre:</strong> ' . htmlspecialchars($offer['date_offre']) . '</p>';
                echo '<p><strong>Référence:</strong> ' . htmlspecialchars($offer['reference_offre']) . '</p>';
                echo '<p><strong>Commercial dédié:</strong> ' . htmlspecialchars($offer['commercial_dedie']) . '</p>';
                echo '<p><strong>Date de Création:</strong> ' . htmlspecialchars($offer['date_creat_offre']) . '</p>';
                echo '</div>';
                echo '</div>';
            }
            ?>
        </div>
    </div>

    <!-- Modal for adding offer -->
    <div class="modal fade" id="addOfferModal" tabindex="-1" aria-labelledby="addOfferModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addOfferModalLabel">Ajouter une Offre</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="request/ajouter_offre.php" method="POST">
                        <div class="mb-3">
                            <label for="num_offre" class="form-label">Numéro d'Offre</label>
                            <input type="text" class="form-control" id="num_offre" name="num_offre" required>
                        </div>
                        <div class="mb-3">
                            <label for="date_offre" class="form-label">Date d'Offre</label>
                            <input type="date" class="form-control" id="date_offre" name="date_offre" required>
                        </div>
                        <div class="mb-3">
                            <label for="reference_offre" class="form-label">Référence</label>
                            <input type="text" class="form-control" id="reference_offre" name="reference_offre" required>
                        </div>
                        <div class="mb-3">
                            <label for="commercial_dedie" class="form-label">Commercial dédié</label>
                            <input type="text" class="form-control" id="commercial_dedie" name="commercial_dedie" required>
                        </div>
                        <div class="mb-3">
                            <label for="date_creat_offre" class="form-label">Date de Création</label>
                            <input type="date" class="form-control" id="date_creat_offre" name="date_creat_offre" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Ajouter l'Offre</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container text-center py-3">
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
    <script src="js/script.js"></script>
    <!--Intégration de jquery/Ajax-->
    <script src="../logi/js/jquery_1.7.1_jquery.min.js"></script>
    <script src="js/function.js"></script>
</body>
</html>
