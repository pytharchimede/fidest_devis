<?php

declare(strict_types=1);
session_start();

require_once __DIR__ . '/model/Database.php';

function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Connexion PDO
$db = new Database();
$pdo = $db->getConnection();

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
    </style>
</head>

<body>
    <?php //include __DIR__ . '/menu.php'; 
    ?>

    <div class="container py-4">
        <div class="catalogue-hero mb-4">
            <div class="d-flex align-items-center gap-3">
                <i class="fa-solid fa-tags fa-2x"></i>
                <div>
                    <h1 class="h3 mb-1">Catalogue des Prix</h1>
                    <p class="mb-0">Listing des produits et niveaux de prix pratiqués</p>
                </div>
            </div>
        </div>

        <div class="card search-card p-3 mb-4">
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
        </div>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="text-muted"><?= count($items) ?> produit(s) trouvés</div>
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
                ?>
                <div class="col-xl-4 col-lg-6">
                    <div class="product-card p-3 h-100">
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <div class="fw-semibold mb-1"><?= h($designation) ?></div>
                                <span class="badge badge-occ">Observé <?= h((string)$occ) ?> fois</span>
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
                            <a class="btn btn-sm btn-outline-primary" href="liste_devis.php?q=<?= urlencode($designation) ?>">
                                <i class="fa-solid fa-list me-1"></i> Voir devis
                            </a>
                            <a class="btn btn-sm btn-outline-secondary" href="export_pdf.php?q=<?= urlencode($designation) ?>" target="_blank">
                                <i class="fa-solid fa-file-pdf me-1"></i> Extrait PDF
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>