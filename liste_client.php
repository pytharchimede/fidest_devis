<?php include 'auth_check.php'; ?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Clients - BTP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .client-card {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
            padding: 1rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .client-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .client-card .client-name {
            font-size: 1.25rem;
            font-weight: bold;
            color: #1d2b57;
        }

        .client-card .client-info {
            margin-top: 0.5rem;
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
                <?php include 'menu.php'; ?>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1 class="text-center">Liste des Clients</h1>

        <!-- Button to trigger modal -->
        <div class="text-center mb-4">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addClientModal">
                Ajouter un client
            </button>
        </div>

        <!-- Grid displaying client cards -->
        <div class="row">
            <!-- PHP code to fetch and display clients from the database -->
            <?php
            include('../logi/connex.php');

            $query = $con->prepare('SELECT * FROM client');
            $query->execute();
            $clients = $query->fetchAll(PDO::FETCH_ASSOC);

            foreach ($clients as $client) {
                echo '
                <div class="col-md-4 mb-4">
                    <div class="client-card p-3 h-100">
                        <div class="client-name">' . htmlspecialchars($client['nom_client']) . '</div>
                        <div class="client-info">Code Client : ' . htmlspecialchars($client['code_client']) . '</div>
                        <div class="client-info">Localisation : ' . htmlspecialchars($client['localisation_client']) . '</div>
                        <div class="client-info">Commune : ' . htmlspecialchars($client['commune_client']) . '</div>
                        <div class="client-info">BP : ' . htmlspecialchars($client['bp_client']) . '</div>
                        <div class="client-info">Pays : ' . htmlspecialchars($client['pays_client']) . '</div>
                    </div>
                </div>';
            }
            ?>
        </div>
    </div>

    <!-- Modal for adding client -->
    <div class="modal fade" id="addClientModal" tabindex="-1" aria-labelledby="addClientModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addClientModalLabel">Ajouter un Client</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="request/ajouter_client.php" method="POST">
                        <div class="mb-3">
                            <label for="code_client" class="form-label">Code Client</label>
                            <input type="text" class="form-control" id="code_client" name="code_client" required>
                        </div>
                        <div class="mb-3">
                            <label for="nom_client" class="form-label">Nom Client</label>
                            <input type="text" class="form-control" id="nom_client" name="nom_client" required>
                        </div>
                        <div class="mb-3">
                            <label for="localisation_client" class="form-label">Localisation</label>
                            <input type="text" class="form-control" id="localisation_client" name="localisation_client" required>
                        </div>
                        <div class="mb-3">
                            <label for="commune_client" class="form-label">Commune</label>
                            <input type="text" class="form-control" id="commune_client" name="commune_client" required>
                        </div>
                        <div class="mb-3">
                            <label for="bp_client" class="form-label">BP</label>
                            <input type="text" class="form-control" id="bp_client" name="bp_client" required>
                        </div>
                        <div class="mb-3">
                            <label for="pays_client" class="form-label">Pays</label>
                            <input type="text" class="form-control" id="pays_client" name="pays_client" required>
                        </div>
                        <div class="mb-3">
                            <label for="date_creat_client" class="form-label">Date de Création</label>
                            <input type="date" class="form-control" id="date_creat_client" name="date_creat_client" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Ajouter le Client</button>
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