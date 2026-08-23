<?php

declare(strict_types=1);
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/model/Database.php';

function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = preg_replace('~[^-a-z0-9]+~', '', $text);
    return trim($text, '-') ?: 'produit';
}


// Garantit l'unicité des slugs en ajoutant un suffixe si nécessaire

function uniqueSlug(PDO $pdo, string $base, int $currentId = 0): string
{
    $slug = $base;
    $i = 1;
    while (true) {
        $stmt = $pdo->prepare('SELECT id_produit FROM produit WHERE slug = :slug AND id_produit <> :id LIMIT 1');
        $stmt->execute([':slug' => $slug, ':id' => $currentId]);
        $exists = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$exists) return $slug;
        $slug = $base . '-' . $i;
        $i++;
    }
}

$db = new Database();
$pdo = $db->getConnection();

// Créer tables si manquantes
$pdo->exec("CREATE TABLE IF NOT EXISTS produit (id_produit INT AUTO_INCREMENT PRIMARY KEY, designation VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL UNIQUE, date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci");
$pdo->exec("CREATE TABLE IF NOT EXISTS produit_image (id_image INT AUTO_INCREMENT PRIMARY KEY, produit_id INT NOT NULL, filename VARCHAR(255) NOT NULL, alt VARCHAR(255) DEFAULT NULL, position INT NOT NULL DEFAULT 0, date_upload DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_produit (produit_id), CONSTRAINT fk_image_produit FOREIGN KEY (produit_id) REFERENCES produit(id_produit) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci");

// Ajuster le schéma produit pour stocker les prix agrégés et clé unique sur designation
try {
    $pdo->exec("ALTER TABLE produit ADD COLUMN prix_min DECIMAL(10,2) NULL");
} catch (Throwable $e) {
}
try {
    $pdo->exec("ALTER TABLE produit ADD COLUMN prix_max DECIMAL(10,2) NULL");
} catch (Throwable $e) {
}
try {
    $pdo->exec("ALTER TABLE produit ADD COLUMN prix_moyen DECIMAL(10,2) NULL");
} catch (Throwable $e) {
}
try {
    $pdo->exec("ALTER TABLE produit ADD COLUMN dernier_prix DECIMAL(10,2) NULL");
} catch (Throwable $e) {
}
try {
    $pdo->exec("ALTER TABLE produit ADD COLUMN stock INT NOT NULL DEFAULT 0");
} catch (Throwable $e) {
}
try {
    $pdo->exec("ALTER TABLE produit ADD UNIQUE KEY uniq_designation (designation)");
} catch (Throwable $e) {
}

// Synchroniser automatiquement les désignations existantes (ligne_devis) vers produit
// Synchroniser base produits depuis le catalogue (ligne_devis) avec agrégats de prix
$pdo->exec("INSERT INTO produit(designation, slug, prix_min, prix_max, prix_moyen, dernier_prix)
SELECT t.designation, '' AS slug, t.prix_min, t.prix_max, t.prix_moyen, t.dernier_prix
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

// Bouton de mise à jour manuelle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'sync') {
    // Réexécuter la synchro
    $pdo->exec("INSERT INTO produit(designation, slug, prix_min, prix_max, prix_moyen, dernier_prix)
        SELECT t.designation, '' AS slug, t.prix_min, t.prix_max, t.prix_moyen, t.dernier_prix
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
    header('Location: catalogue_media.php?synced=1');
    exit;
}
// Mettre à jour les slugs pour ceux sans slug
$rows = $pdo->query('SELECT id_produit, designation, slug FROM produit')->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    $id = (int)$r['id_produit'];
    $designation = (string)$r['designation'];
    $slug = (string)($r['slug'] ?? '');
    if ($slug === '') {
        $base = slugify($designation);
        $final = uniqueSlug($pdo, $base, $id);
        $stmt = $pdo->prepare('UPDATE produit SET slug = :slug WHERE id_produit = :id');
        $stmt->execute([':slug' => $final, ':id' => $id]);
    }
}

// Récupérer la liste des désignations depuis produit
$prods = $pdo->query('SELECT id_produit, designation, slug FROM produit ORDER BY designation ASC')->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produits — Images</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .dropzone {
            border: 2px dashed rgba(0, 0, 0, .15);
            border-radius: 12px;
            padding: 28px;
            background: #fff;
            text-align: center;
        }

        .dropzone.dragover {
            background: #f8fbff;
            border-color: #0d6efd;
        }

        .thumb {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid rgba(0, 0, 0, .08);
        }
    </style>
    <link rel="stylesheet" href="css/modules.css">
    <link rel="stylesheet" href="css/smart-select.css">
    <script>
        function initDrop(zoneId, inputId) {
            const dz = document.getElementById(zoneId);
            const input = document.getElementById(inputId);
            dz.addEventListener('click', () => input.click());
            dz.addEventListener('dragover', e => {
                e.preventDefault();
                dz.classList.add('dragover');
            });
            dz.addEventListener('dragleave', () => dz.classList.remove('dragover'));
            dz.addEventListener('drop', e => {
                e.preventDefault();
                dz.classList.remove('dragover');
                input.files = e.dataTransfer.files;
            });
        }
        document.addEventListener('DOMContentLoaded', () => {
            // Auto-sélection depuis l'ancre #pid-<id>
            const hash = window.location.hash || '';
            const match = hash.match(/#pid-(\d+)/);
            if (match) {
                const pid = match[1];
                const sel = document.querySelector('select[name="produit_id"]');
                if (sel) {
                    sel.value = pid;
                }
                const dz = document.getElementById('dz');
                if (dz) {
                    dz.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                    dz.classList.add('dragover');
                    setTimeout(() => dz.classList.remove('dragover'), 800);
                }
            }
        });
    </script>
</head>

<body class="module-page media-page">
    <?php include __DIR__ . '/partials/navbar.php'; ?>

    <div class="container py-4">
        <div class="module-heading">
            <div class="module-heading__copy">
                <span class="module-heading__icon"><i class="fa-solid fa-images"></i></span>
                <div>
                    <h1>Bibliothèque produit</h1>
                    <p>Importez, organisez et prévisualisez les visuels de votre catalogue.</p>
                </div>
            </div>
        </div>

        <div class="card p-3 mb-4">
            <form method="post" class="mb-3 d-flex justify-content-end">
                <input type="hidden" name="action" value="sync">
                <button class="btn btn-outline-primary"><i class="fa-solid fa-rotate me-2"></i>Mise à jour base produit (prix & stock)</button>
            </form>
            <div class="row g-3">
                <div class="col-12">
                    <div class="alert alert-info mb-0">Sélectionnez une désignation existante dans la liste pour ajouter des images. Les désignations sont synchronisées depuis vos devis.</div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card p-3 h-100">
                    <h2 class="h6">Uploader des images</h2>
                    <form id="uploadForm" action="request/upload_product_images.php" method="post" enctype="multipart/form-data">
                        <div class="mb-2">
                            <label class="form-label">Produit</label>
                            <select name="produit_id" class="form-select" required data-smart-select data-placeholder="Saisir le nom du produit…">
                                <option value="">— Choisir un produit —</option>
                                <?php foreach ($prods as $p): ?>
                                    <option value="<?= (int)$p['id_produit'] ?>"><?= h($p['designation']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div id="dz" class="dropzone mb-2">
                            <div class="text-muted"><i class="fa-solid fa-cloud-arrow-up me-2"></i>Glissez vos images ici ou cliquez pour sélectionner</div>
                            <small class="text-muted">Formats: JPG, PNG, WEBP — Taille max 5 Mo par fichier</small>
                        </div>
                        <input id="fileInput" type="file" name="images[]" accept="image/*" multiple hidden>
                        <div id="previewGrid" class="row g-2 mb-2"></div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-success"><i class="fa-solid fa-upload me-2"></i>Uploader</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('fileInput').value='';">Vider sélection</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card p-3 h-100">
                    <h2 class="h6">Aperçu rapide (dernier uploads)</h2>
                    <div class="row g-2">
                        <?php
                        $thumbs = $pdo->query('SELECT pi.filename, p.designation FROM produit_image pi JOIN produit p ON p.id_produit = pi.produit_id ORDER BY pi.date_upload DESC LIMIT 12')->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($thumbs as $t):
                            $path = 'photo/produits/' . $t['filename'];
                        ?>
                            <div class="col-4">
                                <div class="border rounded p-1 text-center">
                                    <img class="thumb" src="<?= h($path) ?>" alt="<?= h($t['designation']) ?>">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function renderPreviews(files) {
            const grid = document.getElementById('previewGrid');
            if (!grid) return;
            grid.innerHTML = '';
            const max = Math.min(files.length, 12);
            for (let i = 0; i < max; i++) {
                const f = files[i];
                if (!f.type.startsWith('image/')) continue;
                const url = URL.createObjectURL(f);
                const col = document.createElement('div');
                col.className = 'col-4';
                col.innerHTML = `<div class="border rounded p-1 text-center"><img class="thumb" src="${url}" alt="preview"></div>`;
                grid.appendChild(col);
            }
        }

        (function setup() {
            initDrop('dz', 'fileInput');
            const input = document.getElementById('fileInput');
            input.addEventListener('change', () => renderPreviews(input.files));
            const dz = document.getElementById('dz');
            dz.addEventListener('drop', (e) => {
                // input.files déjà mis dans initDrop; re-render
                const input = document.getElementById('fileInput');
                renderPreviews(input.files);
            });

            const form = document.getElementById('uploadForm');
            form.addEventListener('submit', (e) => {
                const sel = form.querySelector('select[name="produit_id"]');
                const files = document.getElementById('fileInput').files;
                if (!sel.value) {
                    e.preventDefault();
                    alert('Veuillez sélectionner un produit.');
                    sel.focus();
                    return;
                }
                if (!files || files.length === 0) {
                    e.preventDefault();
                    alert('Veuillez sélectionner au moins une image.');
                    return;
                }
            });
        })();
    </script>
    <script src="js/smart-select.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
