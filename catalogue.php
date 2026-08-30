<?php

declare(strict_types=1);
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/bootstrap.php';

require_once __DIR__ . '/model/Database.php';

function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Connexion PDO
$db = new Database();
$pdo = $db->getConnection();
$pdo->exec("CREATE TABLE IF NOT EXISTS produit (
    id_produit INT AUTO_INCREMENT PRIMARY KEY,
    designation VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    prix_min DECIMAL(10,2) NULL,
    prix_max DECIMAL(10,2) NULL,
    prix_moyen DECIMAL(10,2) NULL,
    dernier_prix DECIMAL(10,2) NULL,
    stock INT NOT NULL DEFAULT 0,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_designation (designation),
    UNIQUE KEY uniq_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci");

// Synchronisation manuelle depuis ce catalogue (bouton)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'sync_produits') {
    // Insérer toutes les désignations affichées ici avec agrégats de prix
    $pdo->exec("INSERT INTO produit(designation, slug, prix_min, prix_max, prix_moyen, dernier_prix)
        SELECT t.designation, MD5(t.designation) AS slug, t.prix_min, t.prix_max, t.prix_moyen, t.dernier_prix
        FROM (
            SELECT TRIM(ld.designation) AS designation,
                         MIN(ld.prix) AS prix_min,
                         MAX(ld.prix) AS prix_max,
                         ROUND(AVG(ld.prix),2) AS prix_moyen,
                         (
                             SELECT ld2.prix FROM ligne_devis ld2
                             WHERE TRIM(ld2.designation) = TRIM(ld.designation)
                             ORDER BY ld2.id DESC LIMIT 1
                         ) AS dernier_prix
            FROM ligne_devis ld
            WHERE TRIM(ld.designation) <> ''
            GROUP BY TRIM(ld.designation)
        ) AS t
        ON DUPLICATE KEY UPDATE 
            prix_min = VALUES(prix_min),
            prix_max = VALUES(prix_max),
            prix_moyen = VALUES(prix_moyen),
            dernier_prix = VALUES(dernier_prix)");

    // Générer slugs uniques pour ceux qui sont vides
    $rows = $pdo->query('SELECT id_produit, designation, slug FROM produit')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $id = (int)$r['id_produit'];
        $designation = (string)$r['designation'];
        $slug = (string)($r['slug'] ?? '');
        if ($slug === '' || preg_match('/^[a-f0-9]{32}$/', $slug)) { // si slug vide ou hash provisoire
            $base = strtolower(trim(preg_replace('~[^\pL\d]+~u', '-', $designation)));
            $base = preg_replace('~[^-a-z0-9]+~', '', $base);
            $base = trim($base, '-') ?: 'produit';
            $final = $base;
            $i = 1;
            while (true) {
                $stmt = $pdo->prepare('SELECT id_produit FROM produit WHERE slug = :slug AND id_produit <> :id LIMIT 1');
                $stmt->execute([':slug' => $final, ':id' => $id]);
                $exists = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$exists) break;
                $final = $base . '-' . $i;
                $i++;
            }
            $stmt = $pdo->prepare('UPDATE produit SET slug = :slug WHERE id_produit = :id');
            $stmt->execute([':slug' => $final, ':id' => $id]);
        }
    }
    header('Location: catalogue.php?synced=1');
    exit;
}

// Liste des produits synchronisés (désignations existantes)
$products = [];
try {
    $products = $pdo->query('SELECT id_produit, designation FROM produit ORDER BY designation ASC')->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $products = [];
}
// Si la liste semble incomplète, synchroniser depuis ligne_devis
if (!$products || count($products) < 10) {
    try {
        $pdo->exec("INSERT IGNORE INTO produit(designation, slug)
        SELECT DISTINCT TRIM(ld.designation) AS designation, '' AS slug
        FROM ligne_devis ld
        WHERE TRIM(ld.designation) <> ''");
        $products = $pdo->query('SELECT id_produit, designation FROM produit ORDER BY designation ASC')->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
    }
}

// Recherche & filtres
$q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$min = isset($_GET['min']) ? trim((string)$_GET['min']) : '';
$max = isset($_GET['max']) ? trim((string)$_GET['max']) : '';

$where = [];
$params = [];
if ($q !== '') {
    $where[] = 'ld.designation LIKE :q';
    $params[':q'] = "%" . $q . "%";
}
if ($min !== '' && is_numeric($min)) {
    $where[] = 'ld.prix >= :min';
    $params[':min'] = (float)$min;
}
if ($max !== '' && is_numeric($max)) {
    $where[] = 'ld.prix <= :max';
    $params[':max'] = (float)$max;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Agrégation des produits à partir des lignes de devis
$sql = "
SELECT 
  TRIM(ld.designation) AS designation,
  (SELECT p.id_produit FROM produit p WHERE p.designation = TRIM(ld.designation) LIMIT 1) AS product_id,
  (SELECT pi.filename FROM produit p JOIN produit_image pi ON pi.produit_id = p.id_produit WHERE p.designation = TRIM(ld.designation) ORDER BY pi.position ASC, pi.date_upload DESC LIMIT 1) AS image,
  (SELECT COUNT(*) FROM produit p JOIN produit_image pi ON pi.produit_id = p.id_produit WHERE p.designation = TRIM(ld.designation)) AS image_count,
  COUNT(*) AS occurences,
  MIN(ld.prix) AS prix_min,
  MAX(ld.prix) AS prix_max,
  ROUND(AVG(ld.prix), 2) AS prix_moyen,
  (
    SELECT ld2.prix FROM ligne_devis ld2 
    WHERE TRIM(ld2.designation) = TRIM(ld.designation)
    ORDER BY ld2.id DESC LIMIT 1
  ) AS dernier_prix
FROM ligne_devis ld
$whereSql
GROUP BY TRIM(ld.designation)
HAVING designation <> ''
ORDER BY designation ASC
";

$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->execute();
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$mediaStats = $pdo->query("SELECT COUNT(*) total_products,SUM(EXISTS(SELECT 1 FROM produit_image pi WHERE pi.produit_id=p.id_produit)) products_with_images,SUM(NOT EXISTS(SELECT 1 FROM produit_image pi WHERE pi.produit_id=p.id_produit)) products_without_images,(SELECT COUNT(*) FROM produit_image) total_images FROM produit p")->fetch(PDO::FETCH_ASSOC) ?: [];
$totalProducts = (int)($mediaStats['total_products'] ?? 0);
$productsWithImages = (int)($mediaStats['products_with_images'] ?? 0);
$productsWithoutImages = (int)($mediaStats['products_without_images'] ?? 0);
$totalImages = (int)($mediaStats['total_images'] ?? 0);
$coverage = $totalProducts > 0 ? (int)round($productsWithImages * 100 / $totalProducts) : 0;
$filteredWithImages = count(array_filter($items, static fn(array $item): bool => (int)($item['image_count'] ?? 0) > 0));
$filteredWithoutImages = count($items) - $filteredWithImages;

// Palette et styles (respect charte actuelle: blanc, bleu, gris fin)
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catalogue des Prix</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        :root {
            --brand-primary: #0d6efd;
            /* Bootstrap primary, proche de bleu actuel */
            --brand-dark: #0b2f5c;
            --brand-gray: #f4f6f8;
            --border-soft: rgba(0, 0, 0, 0.08);
            --text-muted: #6c757d;
        }

        body {
            background: var(--brand-gray);
        }

        .catalogue-hero {
            background: linear-gradient(135deg, var(--brand-primary), var(--brand-dark));
            color: #fff;
            border-radius: 20px;
            padding: 32px;
            box-shadow: 0 10px 25px rgba(13, 110, 253, 0.25);
        }

        .search-card {
            border: 1px solid var(--border-soft);
            border-radius: 16px;
        }

        .product-card {
            border: 1px solid var(--border-soft);
            border-radius: 16px;
            background: #fff;
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .product-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.06);
        }

        .price-chip {
            border: 1px solid var(--border-soft);
            border-radius: 999px;
            padding: 6px 12px;
            font-weight: 600;
        }

        .metric-label {
            color: var(--text-muted);
            font-size: .85rem;
        }

        .metric-value {
            font-size: 1.05rem;
            font-weight: 600;
        }

        .divider {
            height: 1px;
            background: var(--border-soft);
        }

        .badge-occ {
            background: rgba(13, 110, 253, 0.12);
            color: var(--brand-primary);
        }

        .catalogue-media-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:24px; }
        .catalogue-media-stat { display:flex; gap:12px; align-items:center; padding:16px 18px; background:#fff; border:1px solid var(--border-soft); border-radius:15px; box-shadow:0 8px 24px rgba(34,37,75,.06); }
        .catalogue-media-stat i { display:grid; width:42px; height:42px; flex:0 0 42px; place-items:center; color:var(--brand-primary); background:#eeeef5; border-radius:12px; }
        .catalogue-media-stat strong,.catalogue-media-stat small { display:block; }
        .catalogue-media-stat strong { color:var(--brand-primary); font-size:1.25rem; }
        .catalogue-media-stat small { color:var(--text-muted); font-size:.69rem; }
        .catalogue-product-head { display:grid; grid-template-columns:72px minmax(0,1fr) auto; gap:12px; align-items:start; }
        .catalogue-product-image { display:grid; width:72px; height:72px; overflow:hidden; place-items:center; color:#a0a2af; background:#f0f1f5; border-radius:12px; text-decoration:none; }
        .catalogue-product-image img { width:100%; height:100%; object-fit:cover; }
        .catalogue-product-image i { font-size:1.3rem; }
        .catalogue-image-count { display:inline-block; margin-top:5px; color:var(--text-muted); font-size:.64rem; }
        @media(max-width:900px){.catalogue-media-stats{grid-template-columns:1fr 1fr}}
        @media(max-width:520px){.catalogue-media-stats{grid-template-columns:1fr}.catalogue-product-head{grid-template-columns:62px minmax(0,1fr)}.catalogue-product-image{width:62px;height:62px}.catalogue-product-head .price-chip{grid-column:1/-1;width:max-content}}
    </style>
    <link rel="stylesheet" href="css/modules.css">
    <link rel="stylesheet" href="css/smart-select.css">
</head>

<body class="module-page catalogue-page">
    <?php include __DIR__ . '/partials/navbar.php'; ?>

    <div class="container py-4">
        <div class="catalogue-hero mb-4">
            <div class="d-flex align-items-center gap-3">
                <i class="fa-solid fa-tags fa-2x"></i>
                <div>
                    <h1 class="h3 mb-1">Catalogue des prix</h1>
                    <p class="mb-0">Analysez les références et les niveaux de prix observés dans vos devis.</p>
                </div>
            </div>
        </div>

        <section class="catalogue-media-stats" aria-label="Couverture des images produits">
            <div class="catalogue-media-stat"><i class="fa-solid fa-boxes-stacked"></i><div><strong><?= $totalProducts ?></strong><small>Produits au catalogue</small></div></div>
            <div class="catalogue-media-stat"><i class="fa-solid fa-image"></i><div><strong><?= $productsWithImages ?></strong><small>Produits avec image</small></div></div>
            <div class="catalogue-media-stat"><i class="fa-regular fa-image"></i><div><strong><?= $productsWithoutImages ?></strong><small>Produits sans image</small></div></div>
            <div class="catalogue-media-stat"><i class="fa-solid fa-chart-pie"></i><div><strong><?= $coverage ?>%</strong><small>Couverture · <?= $totalImages ?> image(s)</small></div></div>
        </section>

        <div class="card search-card p-3 mb-4">
            <form method="post" class="mb-3 d-flex justify-content-end">
                <input type="hidden" name="action" value="sync_produits">
                <button class="btn btn-outline-primary"><i class="fa-solid fa-rotate me-2"></i>Mise à jour de la base produits (depuis ce catalogue)</button>
            </form>
            <form class="row g-3" method="get">
                <div class="col-md-6">
                    <label class="form-label">Recherche produit</label>
                    <input type="text" name="q" value="<?= h($q) ?>" class="form-control" placeholder="Désignation (ex: Chaussure de sécurité)">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Prix min (FCFA)</label>
                    <input type="number" step="0.01" name="min" value="<?= h($min) ?>" class="form-control" placeholder="0">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Prix max (FCFA)</label>
                    <input type="number" step="0.01" name="max" value="<?= h($max) ?>" class="form-control" placeholder="">
                </div>
                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-primary"><i class="fa-solid fa-magnifying-glass me-2"></i>Filtrer</button>
                    <a href="catalogue.php" class="btn btn-outline-secondary">Réinitialiser</a>
                </div>
            </form>
            <hr class="my-3" />
            <div class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label class="form-label">Ajout rapide d'images (select-search)</label>
                    <input id="mediaSearch" list="productList" class="form-control" placeholder="Tapez pour rechercher une désignation">
                    <datalist id="productList">
                        <?php foreach ($products as $p): ?>
                            <option data-id="<?= (int)$p['id_produit'] ?>" value="<?= h($p['designation']) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="col-md-2">
                    <label class="form-label">ou sélection</label>
                    <select id="mediaSelect" class="form-select" data-smart-select data-placeholder="Rechercher une désignation…">
                        <option value="">— Choisir —</option>
                        <?php foreach ($products as $p): ?>
                            <option value="<?= (int)$p['id_produit'] ?>"><?= h($p['designation']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-success w-100" onclick="goUpload()"><i class="fa-solid fa-images me-2"></i>Gérer les images</button>
                </div>
            </div>
            <script>
                const mapProducts = {
                    <?php
                    $entries = [];
                    foreach ($products as $p) {
                        $key = addslashes($p['designation']);
                        $val = (int)$p['id_produit'];
                        $entries[] = '"' . $key . '"' . ': ' . $val;
                    }
                    echo implode(",\n                    ", $entries);
                    ?>
                };

                function goUpload() {
                    var pid = document.getElementById('mediaSelect').value;
                    if (!pid) {
                        const name = document.getElementById('mediaSearch').value;
                        pid = mapProducts[name] || '';
                    }
                    if (!pid) {
                        alert('Veuillez choisir ou rechercher un produit');
                        return;
                    }
                    window.location.href = 'catalogue_media.php#pid-' + pid;
                }
            </script>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="text-muted"><?= count($items) ?> produit(s) trouvé(s) · <strong><?= $filteredWithImages ?></strong> avec image · <strong><?= $filteredWithoutImages ?></strong> sans image</div>
            <div class="text-muted">Données issues des lignes de devis</div>
        </div>

        <div class="row g-3">
            <?php foreach ($items as $it): ?>
                <?php
                $designation = (string)($it['designation'] ?? '');
                $occ = (int)($it['occurences'] ?? 0);
                $minP = (float)($it['prix_min'] ?? 0);
                $maxP = (float)($it['prix_max'] ?? 0);
                $avgP = (float)($it['prix_moyen'] ?? 0);
                $lastP = (float)($it['dernier_prix'] ?? 0);
                $productId = (int)($it['product_id'] ?? 0);
                $image = (string)($it['image'] ?? '');
                $imageCount = (int)($it['image_count'] ?? 0);
                ?>
                <div class="col-xl-4 col-lg-6">
                    <div class="product-card p-3 h-100">
                        <div class="catalogue-product-head">
                            <a class="catalogue-product-image" href="<?= $productId > 0 ? 'catalogue_media.php#pid-'.$productId : '#' ?>" title="<?= $image ? 'Voir les images du produit' : 'Ajouter une image' ?>">
                                <?php if ($image): ?><img src="photo/produits/<?= h($image) ?>" alt="<?= h($designation) ?>" loading="lazy"><?php else: ?><i class="fa-solid fa-camera"></i><?php endif; ?>
                            </a>
                            <div>
                                <div class="fw-semibold mb-1"><?= h($designation) ?></div>
                                <span class="badge badge-occ">Observé <?= h((string)$occ) ?> fois</span>
                                <span class="catalogue-image-count"><i class="fa-regular fa-images me-1"></i><?= $imageCount > 0 ? $imageCount.' image(s)' : 'Aucune image' ?></span>
                            </div>
                            <div class="price-chip">
                                <i class="fa-solid fa-money-bill-wave me-2"></i><?= number_format($lastP, 0, '.', ' ') ?> FCFA
                            </div>
                        </div>
                        <div class="divider my-3"></div>
                        <div class="row text-center g-2">
                            <div class="col-3">
                                <div class="metric-label">Min</div>
                                <div class="metric-value"><?= number_format($minP, 0, '.', ' ') ?></div>
                            </div>
                            <div class="col-3">
                                <div class="metric-label">Moyen</div>
                                <div class="metric-value"><?= number_format($avgP, 0, '.', ' ') ?></div>
                            </div>
                            <div class="col-3">
                                <div class="metric-label">Max</div>
                                <div class="metric-value"><?= number_format($maxP, 0, '.', ' ') ?></div>
                            </div>
                            <div class="col-3">
                                <div class="metric-label">Dernier</div>
                                <div class="metric-value"><?= number_format($lastP, 0, '.', ' ') ?></div>
                            </div>
                        </div>

                        <div class="mt-3 d-flex justify-content-end gap-2">
                            <a class="btn btn-sm btn-outline-primary" href="liste_devis.php?<?= http_build_query(['products' => [$designation]]) ?>">
                                <i class="fa-solid fa-list me-1"></i> Voir devis
                            </a>
                            <a class="btn btn-sm btn-outline-secondary" href="request/export_resultat.php?<?= http_build_query(['products' => [$designation]]) ?>" target="_blank">
                                <i class="fa-solid fa-file-pdf me-1"></i> Extrait PDF
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="js/smart-select.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
