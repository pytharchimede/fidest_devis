<?php

declare(strict_types=1);
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/bootstrap.php';
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
$items = $pdo->query("SELECT p.id_produit, p.designation, p.dernier_prix, p.prix_moyen, p.stock,
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
    <link rel="stylesheet" href="css/modules.css">
    <link rel="stylesheet" href="css/smart-select.css">
</head>

<body class="module-page shop-page">
    <?php include __DIR__ . '/partials/navbar.php'; ?>

    <div class="container py-4">
        <section class="shop-hero">
            <div class="shop-hero__content">
                <div class="eyebrow">ÉQUIPEMENTS PROFESSIONNELS</div>
                <h1>La sélection FIDEST, pensée pour vos exigences.</h1>
                <p>Découvrez nos équipements et fournitures pour professionnels, sélectionnés pour leur fiabilité et leur performance.</p>
                <a class="btn btn-warning mt-4" href="boutique_publique.php" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square me-2"></i>Ouvrir la boutique publique</a>
            </div>
        </section>

        <div class="shop-toolbar">
            <div class="shop-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input id="shopSearch" class="form-control" type="search" placeholder="Rechercher un produit ou une catégorie…" aria-label="Rechercher dans la boutique">
            </div>
            <select id="shopCategory" data-smart-select data-placeholder="Toutes les catégories">
                <option value="">Toutes les catégories</option>
                <?php foreach (array_keys($categories) as $category): ?><option value="<?= h(strtolower($category)) ?>"><?= h($category) ?></option><?php endforeach; ?>
            </select>
            <select id="shopAvailability" data-smart-select data-placeholder="Toute disponibilité">
                <option value="">Toute disponibilité</option><option value="stock">En stock</option><option value="order">Sur commande</option>
            </select>
            <div class="shop-count"><span id="visibleProducts"><?= count($items) ?></span> produits disponibles</div>
        </div>

        <div class="row g-3 shop-grid" id="shopGrid">
            <?php foreach ($items as $it): ?>
                <?php
                $designation = (string)$it['designation'];
                $cat = guessCategory($categories, $designation);
                $img = $it['image'] ? ('photo/produits/' . $it['image']) : '';
                $price = (float) ($it['dernier_prix'] ?: $it['prix_moyen']);
                $stock = (int) $it['stock'];
                $rating = 5;
                ?>
                <div class="col-xl-3 col-lg-4 col-md-6 shop-item" data-search="<?= h(strtolower($designation . ' ' . $cat)) ?>" data-category="<?= h(strtolower($cat)) ?>" data-availability="<?= $stock > 0 ? 'stock' : 'order' ?>">
                    <div class="product-card h-100">
                        <div class="product-visual">
                            <?php if ($img): ?>
                                <img class="thumb" src="<?= h($img) ?>" alt="<?= h($designation) ?>" loading="lazy">
                            <?php else: ?>
                                <div class="product-placeholder"><i class="fa-solid fa-box-open"></i></div>
                            <?php endif; ?>
                            <button class="product-favorite" type="button" aria-label="Ajouter aux favoris"><i class="fa-regular fa-heart"></i></button>
                        </div>
                        <div class="product-content">
                            <div class="product-meta">
                                <span class="category-pill"><?= h($cat) ?></span>
                                <span class="rating" aria-label="5 étoiles"><i class="fa-solid fa-star"></i> 5.0</span>
                            </div>
                            <div class="product-title" title="<?= h($designation) ?>"><?= h($designation) ?></div>
                            <div class="product-meta"><span><?= $stock > 0 ? h($stock . ' en stock') : 'Sur commande' ?></span><span>Réf. <?= (int) $it['id_produit'] ?></span></div>
                            <div class="product-price"><?= $price > 0 ? number_format($price, 0, ',', ' ') . ' FCFA' : 'Prix sur demande' ?> <small>HT</small></div>
                            <div class="product-actions">
                                <a class="btn btn-primary" href="catalogue.php?q=<?= urlencode($designation) ?>"><i class="fa-solid fa-arrow-right"></i> Voir le produit</a>
                                <a class="btn btn-outline-primary" href="catalogue_media.php#pid-<?= (int) $it['id_produit'] ?>" aria-label="Voir les médias"><i class="fa-regular fa-images"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        const search = document.getElementById('shopSearch');
        const items = [...document.querySelectorAll('.shop-item')];
        const count = document.getElementById('visibleProducts');
        const category = document.getElementById('shopCategory');
        const availability = document.getElementById('shopAvailability');
        const grid = document.getElementById('shopGrid');
        const sentinel = document.createElement('div');
        sentinel.className = 'infinite-shop-sentinel';
        sentinel.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i><span>Chargement des produits…</span>';
        grid.after(sentinel);
        const batchSize = 12;
        let matchingItems = items;
        let displayed = 0;

        function revealNextBatch() {
            displayed = Math.min(displayed + batchSize, matchingItems.length);
            matchingItems.forEach((item, index) => item.hidden = index >= displayed);
            sentinel.hidden = displayed >= matchingItems.length;
        }

        function filterShop() {
            const query = search.value.trim().toLocaleLowerCase('fr');
            matchingItems = items.filter(item => item.dataset.search.includes(query)
                && (!category.value || item.dataset.category === category.value)
                && (!availability.value || item.dataset.availability === availability.value));
            items.forEach(item => item.hidden = true);
            displayed = 0;
            count.textContent = matchingItems.length;
            revealNextBatch();
        }

        new IntersectionObserver(entries => {
            if (entries[0].isIntersecting && displayed < matchingItems.length) revealNextBatch();
        }, { rootMargin: '500px 0px' }).observe(sentinel);
        search.addEventListener('input', filterShop);
        category.addEventListener('change', filterShop);
        availability.addEventListener('change', filterShop);
        filterShop();
    </script>
    <script src="js/smart-select.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
