<?php

declare(strict_types=1);
session_start();
require_once __DIR__ . '/model/Database.php';

function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$db = new Database();
$pdo = $db->getConnection();

// Catégories factices par préfixe de désignation (améliorable via table dédiée)
$categories = [
    'EPI' => ['motifs' => ['CHAUSSURE', 'GANT', 'LUNETTE', 'BOTTE']],
    'Électrique' => ['motifs' => ['SENSOR', 'LAMPE', 'MODULE', 'VOYANT', 'BATTERIE']],
    'Hydraulique' => ['motifs' => ['VALVE', 'FLEXIBLE', 'TUYAU']],
    'Mobilier' => ['motifs' => ['CHAISE', 'TABLE', 'TABOURET']],
    'Divers' => ['motifs' => []],
];

// Produits + 1 image principale (la plus récente)
$items = $pdo->query("SELECT p.id_produit, p.designation,
    (SELECT filename FROM produit_image pi WHERE pi.produit_id = p.id_produit ORDER BY pi.position ASC, pi.date_upload DESC LIMIT 1) AS image
    FROM produit p ORDER BY p.designation ASC")->fetchAll(PDO::FETCH_ASSOC);

function guessCategory(array $cats, string $designation): string
{
    foreach ($cats as $name => $data) {
        foreach ($data['motifs'] as $m) {
            if (stripos($designation, $m) !== false) {
                return $name;
            }
        }
    }
    return 'Divers';
}

?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boutique FIDEST</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .product-card {
            border: 1px solid rgba(0, 0, 0, .08);
            border-radius: 12px;
            background: #fff;
        }

        .product-card .thumb {
            width: 100%;
            height: 170px;
            object-fit: cover;
            border-bottom: 1px solid rgba(0, 0, 0, .06);
        }

        .rating {
            color: #ffc107;
        }

        .category-pill {
            font-size: .8rem;
            border: 1px solid rgba(0, 0, 0, .08);
            border-radius: 999px;
            padding: .2rem .5rem;
        }
    </style>
</head>

<body>
    <?php //include __DIR__ . '/menu.php'; 
    ?>

    <div class="container py-4">
        <div class="d-flex align-items-center gap-3 mb-3">
            <i class="fa-solid fa-store fa-2x text-primary"></i>
            <div>
                <h1 class="h4 mb-1">Boutique FIDEST</h1>
                <p class="text-muted mb-0">Produits avec images, catégories, et notation</p>
            </div>
        </div>

        <div class="row g-3">
            <?php foreach ($items as $it): ?>
                <?php
                $designation = (string)$it['designation'];
                $cat = guessCategory($categories, $designation);
                $img = $it['image'] ? ('photo/produits/' . $it['image']) : 'logo/fidest.png';
                $rating = rand(3, 5); // Placeholder de note
                ?>
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="product-card h-100">
                        <img class="thumb" src="<?= h($img) ?>" alt="<?= h($designation) ?>">
                        <div class="p-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="category-pill"><?= h($cat) ?></span>
                                <span class="rating">
                                    <?php for ($i = 0; $i < 5; $i++): ?>
                                        <i class="fa-solid fa-star<?= $i < $rating ? '' : '-o' ?>"></i>
                                    <?php endfor; ?>
                                </span>
                            </div>
                            <div class="fw-semibold mb-2" title="<?= h($designation) ?>"><?= h($designation) ?></div>
                            <div class="d-flex gap-2">
                                <a class="btn btn-sm btn-outline-primary" href="catalogue_media.php"><i class="fa-solid fa-images me-1"></i>Médias</a>
                                <a class="btn btn-sm btn-outline-secondary" href="catalogue.php?q=<?= urlencode($designation) ?>"><i class="fa-solid fa-tag me-1"></i>Prix</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>