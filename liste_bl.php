<?php
include 'auth_check.php';
require_once __DIR__ . '/model/Database.php';

$regPath = __DIR__ . '/data/bl_registry.json';
$signedItems = [];
if (file_exists($regPath)) {
    $json = file_get_contents($regPath);
    $data = json_decode($json, true);
    if (is_array($data) && isset($data['items'])) $signedItems = $data['items'];
}
$signedTotal = count($signedItems);

// Fetch devis list to compute unsigned
$pdo = (new Database())->getConnection();
$stmt = $pdo->query("SELECT d.id, d.numero_devis, d.date_emission, c.nom_client AS client_nom FROM devis d LEFT JOIN client c ON d.client_id = c.id_client ORDER BY d.id DESC");
$allDevis = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

$signedMap = [];
foreach ($signedItems as $it) {
    $signedMap[(int)$it['devisId']] = $it;
}
$unsignedDevis = array_values(array_filter($allDevis, function ($d) use ($signedMap) {
    return !isset($signedMap[(int)$d['id']]);
}));
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Bons de Livraison</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <style>
        body {
            background: #f6f8fb;
        }

        .navbar {
            background: linear-gradient(90deg, #0d6efd, #6610f2);
        }

        .navbar-brand img {
            height: 40px;
            border-radius: 4px;
        }

        .page-title {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .badge-soft {
            background: #eef2ff;
            color: #3b82f6;
            border: 1px solid #c7d2fe;
        }

        .card {
            border: none;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
            border-radius: 12px;
        }

        .table thead th {
            background: #f3f4f6;
            font-weight: 600;
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
        <div class="page-title mb-4">
            <h1 class="m-0">Bons de Livraison</h1>
            <span class="badge badge-soft pill">Signés: <?= $signedTotal ?> • Non signés: <?= count($unsignedDevis) ?></span>
            <a class="btn btn-outline-primary pill" href="request/export_bl.php" target="_blank">Générer un BL</a>
        </div>

        <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active pill" id="pills-signed-tab" data-bs-toggle="pill" data-bs-target="#pills-signed" type="button" role="tab">Signés</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link pill" id="pills-unsigned-tab" data-bs-toggle="pill" data-bs-target="#pills-unsigned" type="button" role="tab">Non signés</button>
            </li>
        </ul>
        <div class="tab-content" id="pills-tabContent">
            <div class="tab-pane fade show active" id="pills-signed" role="tabpanel" aria-labelledby="pills-signed-tab">
                <div class="card">
                    <div class="card-body">
                        <?php if ($signedTotal === 0): ?>
                            <div class="alert alert-info">Aucun BL signé pour le moment.</div>
                        <?php else: ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Devis</th>
                                        <th>Client</th>
                                        <th>Fichier</th>
                                        <th>Signé le</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($signedItems as $it): ?>
                                        <?php $devisId = (int)$it['devisId'];
                                        $assoc = null;
                                        foreach ($allDevis as $d) {
                                            if ((int)$d['id'] === $devisId) {
                                                $assoc = $d;
                                                break;
                                            }
                                        }
                                        ?>
                                        <tr>
                                            <td>#<?= $devisId ?> <?= $assoc ? '— ' . htmlspecialchars($assoc['numero_devis']) : '' ?></td>
                                            <td><?= $assoc ? htmlspecialchars($assoc['client_nom']) : '—' ?></td>
                                            <td><?= htmlspecialchars($it['file']) ?></td>
                                            <td><?= htmlspecialchars($it['signedAt']) ?></td>
                                            <td class="d-flex gap-2">
                                                <a class="btn btn-primary btn-sm pill" target="_blank" href="<?= htmlspecialchars($it['file']) ?>">Voir</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="pills-unsigned" role="tabpanel" aria-labelledby="pills-unsigned-tab">
                <div class="card">
                    <div class="card-body">
                        <?php if (count($unsignedDevis) === 0): ?>
                            <div class="alert alert-success">Tous les BL sont signés.</div>
                        <?php else: ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Devis</th>
                                        <th>Client</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($unsignedDevis as $d): ?>
                                        <tr>
                                            <td>#<?= (int)$d['id'] ?> — <?= htmlspecialchars($d['numero_devis']) ?></td>
                                            <td><?= htmlspecialchars($d['client_nom'] ?? '—') ?></td>
                                            <td><?= htmlspecialchars($d['date_emission']) ?></td>
                                            <td class="d-flex gap-2">
                                                <a class="btn btn-outline-primary btn-sm pill" target="_blank" href="request/export_bl.php?id=<?= (int)$d['id'] ?>">Générer BL</a>
                                                <a class="btn btn-outline-secondary btn-sm pill" target="_blank" href="request/export_pdf.php?devisId=<?= (int)$d['id'] ?>">Aperçu Devis</a>
                                                <a class="btn btn-success btn-sm pill" href="request/upload_bl.php?devisId=<?= (int)$d['id'] ?>">Uploader BL signé</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>