<?php include 'auth_check.php'; ?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Corbeille — Devis supprimés</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background-color: #f7f9fc;
        }

        .navbar {
            background: linear-gradient(135deg, #1d2b57 0%, #2b3f8a 100%);
        }

        .navbar-brand img {
            height: 40px;
            border-radius: 4px;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, .1);
        }

        .pill {
            border-radius: 999px;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <img src="img/logo_fidest.png" alt="Logo">
            </a>
            <div class="collapse navbar-collapse" id="navbarNav">
                <?php include 'menu.php'; ?>
            </div>
        </div>
    </nav>
    <div class="container py-4">
        <h1 class="mb-3">Corbeille — Devis supprimés</h1>
        <?php
        require_once __DIR__ . '/model/Database.php';
        $con = \Database::getConnection();
        $sql = 'SELECT * FROM devis WHERE masque=1 ORDER BY id DESC';
        $query = $con->prepare($sql);
        $query->execute();
        $devis = $query->fetchAll(PDO::FETCH_ASSOC);
        if (!$devis) {
            echo '<div class="alert alert-info">Aucun devis en corbeille.</div>';
        } else {
            echo '<div class="list-group">';
            foreach ($devis as $de) {
                echo '<div class="list-group-item d-flex justify-content-between align-items-center">';
                echo '<div>';
                echo '<div><strong>#' . (int)$de['id'] . '</strong> — ' . htmlspecialchars($de['numero_devis']) . '</div>';
                echo '<div class="text-muted small">Émis le ' . htmlspecialchars($de['date_emission']) . ' • Destiné à ' . htmlspecialchars($de['destine_a']) . '</div>';
                echo '</div>';
                echo '<div class="d-flex gap-2">';
                echo '<a class="btn btn-outline-primary pill" target="_blank" href="request/export_pdf.php?devisId=' . (int)$de['id'] . '"><i class="fas fa-eye"></i> Voir</a>';
                echo '<a class="btn btn-success pill" href="request/restaurer_devis.php?devisId=' . (int)$de['id'] . '" onclick="return confirm(\'Restaurer ce devis ?\')"><i class="fas fa-undo"></i> Restaurer</a>';
                echo '</div>';
                echo '</div>';
            }
            echo '</div>';
        }
        ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>