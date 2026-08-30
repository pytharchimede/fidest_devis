<?php

declare(strict_types=1);
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/bootstrap.php';

use App\Domain\PurchaseOrder\PurchaseOrderRepository;

$pdo = app_database();

function bc_h(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

if (!isset($_SESSION['bc_csrf'])) {
    $_SESSION['bc_csrf'] = bin2hex(random_bytes(24));
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string) ($_POST['csrf_token'] ?? '');
    if (!hash_equals((string) $_SESSION['bc_csrf'], $token)) {
        $error = 'La session du formulaire a expiré. Veuillez réessayer.';
    }

    $referenceClient = trim((string) ($_POST['reference_client'] ?? ''));
    $dateCommande = trim((string) ($_POST['date_commande'] ?? ''));
    $dateReception = trim((string) ($_POST['date_reception'] ?? ''));
    $status = (string) ($_POST['statut'] ?? 'recu');
    $allowedStatuses = ['recu', 'traitement', 'execute', 'annule'];
    if ($error === '' && ($referenceClient === '' || $dateCommande === '' || $dateReception === '')) {
        $error = 'Le numéro du bon client et les dates sont obligatoires.';
    }
    $selectedQuoteId = (int) ($_POST['devis_id'] ?? 0);
    if ($error === '' && $selectedQuoteId <= 0) $error = 'Sélectionnez obligatoirement le devis validé à l’origine du bon.';
    if ($error === '') {
        $eligible = $pdo->prepare("SELECT client_id FROM devis WHERE id=:id AND masque=0 AND (validation_generale=1 OR statut_devis='valide')");
        $eligible->execute(['id'=>$selectedQuoteId]); $eligibleQuote=$eligible->fetch();
        if (!$eligibleQuote) $error='Le bon de commande ne peut être rattaché qu’à un devis validé.';
        else $_POST['client_id']=(int)$eligibleQuote['client_id'];
    }
    if (!in_array($status, $allowedStatuses, true)) {
        $status = 'recu';
    }

    $file = $_FILES['document'] ?? null;
    if ($error === '' && (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK)) {
        $error = 'Veuillez joindre le bon de commande reçu.';
    }

    $storedRelativePath = '';
    $originalName = '';
    $mime = '';
    if ($error === '' && is_array($file)) {
        if ((int) $file['size'] > 10 * 1024 * 1024) {
            $error = 'Le document dépasse la taille maximale de 10 Mo.';
        } else {
            $mime = (string) (new finfo(FILEINFO_MIME_TYPE))->file((string) $file['tmp_name']);
            $allowedTypes = [
                'application/pdf' => 'pdf',
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
            ];
            if (!isset($allowedTypes[$mime])) {
                $error = 'Format non accepté. Utilisez un PDF, JPG ou PNG.';
            } else {
                $uploadDirectory = __DIR__ . '/photo/bons_commande';
                if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0777, true) && !is_dir($uploadDirectory)) {
                    $error = 'Le dossier de stockage du document est indisponible.';
                } else {
                    // L’application peut être exécutée par un compte différent du propriétaire du projet.
                    @chmod($uploadDirectory, 0777);
                    clearstatcache(true, $uploadDirectory);
                    if (!is_writable($uploadDirectory)) {
                        $error = 'Le dossier de stockage du document n’est pas accessible en écriture.';
                    }
                }
                if ($error === '') {
                    $storedName = 'bc_' . bin2hex(random_bytes(12)) . '.' . $allowedTypes[$mime];
                    $storedRelativePath = 'photo/bons_commande/' . $storedName;
                    $originalName = basename((string) $file['name']);
                    if (!move_uploaded_file((string) $file['tmp_name'], __DIR__ . '/' . $storedRelativePath)) {
                        $error = 'Le document n’a pas pu être enregistré.';
                    }
                }
            }
        }
    }

    if ($error === '') {
        try {
            $pdo->beginTransaction();
            $statement = $pdo->prepare('INSERT INTO bons_commande (numero_bc,reference_client,client_id,devis_id,date_commande,date_reception,montant,statut,notes,fichier,fichier_original,mime_type,created_by) VALUES (:numero,:reference,:client,:devis,:date_commande,:date_reception,:montant,:statut,:notes,:fichier,:original,:mime,:user)');
            $amount = str_replace([' ', ','], ['', '.'], trim((string) ($_POST['montant'] ?? '')));
            $temporaryNumber = 'TEMP-' . bin2hex(random_bytes(10));
            $statement->execute([
                'numero' => $temporaryNumber,
                'reference' => $referenceClient,
                'client' => (int) ($_POST['client_id'] ?? 0) ?: null,
                'devis' => (int) ($_POST['devis_id'] ?? 0) ?: null,
                'date_commande' => $dateCommande,
                'date_reception' => $dateReception,
                'montant' => $amount === '' ? null : $amount,
                'statut' => $status,
                'notes' => trim((string) ($_POST['notes'] ?? '')) ?: null,
                'fichier' => $storedRelativePath,
                'original' => $originalName,
                'mime' => $mime,
                'user' => (int) ($_SESSION['user_id'] ?? 0),
            ]);
            $orderId = (int) $pdo->lastInsertId();
            $internalNumber = sprintf('FID-BC-%s-%05d', date('Y'), $orderId);
            $numberStatement = $pdo->prepare('UPDATE bons_commande SET numero_bc=:numero WHERE id=:id');
            $numberStatement->execute(['numero' => $internalNumber, 'id' => $orderId]);
            $pdo->commit();
            $_SESSION['bc_csrf'] = bin2hex(random_bytes(24));
            header('Location: bons_commande.php?created=1');
            exit;
        } catch (PDOException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($storedRelativePath !== '' && is_file(__DIR__ . '/' . $storedRelativePath)) {
                unlink(__DIR__ . '/' . $storedRelativePath);
            }
            $error = 'Le bon de commande n’a pas pu être enregistré.';
        }
    }
}

$clients = $pdo->query('SELECT id_client,nom_client,code_client FROM client ORDER BY nom_client')->fetchAll();
$quotes = $pdo->query("SELECT d.id,d.numero_devis,c.nom_client FROM devis d LEFT JOIN client c ON c.id_client=d.client_id WHERE d.masque=0 AND d.archived_at IS NULL AND (d.validation_generale=1 OR d.statut_devis='valide') ORDER BY d.id DESC LIMIT 300")->fetchAll();
$orders = (new PurchaseOrderRepository($pdo))->search($_GET);
$statusLabels = ['recu' => 'Reçu', 'traitement' => 'En traitement', 'execute' => 'Exécuté', 'annule' => 'Annulé'];
$exportQuery = http_build_query(array_filter([
    'q' => $_GET['q'] ?? '', 'date_from' => $_GET['date_from'] ?? '', 'date_to' => $_GET['date_to'] ?? '',
    'client_id' => $_GET['client_id'] ?? '', 'devis_id' => $_GET['devis_id'] ?? '', 'statut' => $_GET['statut'] ?? '',
], static fn (mixed $value): bool => $value !== ''));
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Bons de commande | FIDEST</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/modules.css">
    <link rel="stylesheet" href="css/smart-select.css">
    <style>
        .orders-hero{display:flex;align-items:center;justify-content:space-between;gap:24px;margin-bottom:24px;padding:30px;color:#fff;background:linear-gradient(125deg,var(--brand-primary),var(--brand-primary-soft));border-radius:24px;box-shadow:0 18px 45px rgba(34,37,75,.18)}
        .orders-hero h1{margin:0!important;color:#fff!important}.orders-hero h1:before{content:"SUIVI COMMERCIAL"}.orders-hero p{max-width:650px;margin:9px 0 0;color:#ffffffb8}.orders-hero__icon{display:grid;width:66px;height:66px;flex:0 0 66px;place-items:center;color:var(--brand-primary);background:var(--brand-accent);border-radius:19px;font-size:1.5rem}
        .orders-layout{display:grid;grid-template-columns:minmax(330px,.75fr) minmax(0,1.25fr);gap:22px}.order-panel{padding:25px;background:#fff;border:1px solid var(--brand-border);border-radius:20px;box-shadow:0 10px 30px rgba(34,37,75,.07)}.order-panel h2{display:flex;align-items:center;gap:10px;margin:0 0 20px;color:var(--brand-primary);font:800 1.08rem var(--brand-font-heading)}.order-panel h2 i{color:var(--brand-accent-hover)}
        .upload-zone{display:block;padding:20px;text-align:center;background:#f7f7fa;border:2px dashed #b8bac9;border-radius:14px;cursor:pointer;transition:.2s}.upload-zone:hover,.upload-zone.has-file{background:#fff9e6;border-color:var(--brand-accent)}.upload-zone i{display:block;margin-bottom:8px;color:var(--brand-primary);font-size:1.6rem}.upload-zone strong,.upload-zone small{display:block}.upload-zone small{margin-top:5px;color:var(--brand-text-muted)}
        .order-list{display:grid;gap:12px}.order-item{display:grid;grid-template-columns:44px minmax(0,1fr) auto;gap:13px;align-items:start;padding:16px;border:1px solid var(--brand-border);border-radius:14px}.order-item__icon{display:grid;width:44px;height:44px;place-items:center;color:var(--brand-primary);background:#ececf3;border-radius:12px}.order-item h3{margin:0 0 5px;font:800 .92rem var(--brand-font-heading)}.order-item p{margin:0;color:var(--brand-text-muted);font-size:.76rem}.order-item__meta{display:flex;gap:12px;flex-wrap:wrap;margin-top:8px;color:#777b8d;font-size:.7rem}.order-status{display:inline-flex;padding:6px 9px;border-radius:99px;font-size:.68rem;font-weight:900}.order-status.recu{color:#765900;background:#fff2c1}.order-status.traitement{color:#08707c;background:#dcf3f5}.order-status.execute{color:#14733f;background:#dcf7e8}.order-status.annule{color:#a62c37;background:#fbe9eb}.document-link{display:inline-flex;align-items:center;gap:5px;margin-top:9px;color:var(--brand-primary);font-size:.73rem;font-weight:800;text-decoration:none}
        .order-filters{margin-bottom:22px;padding:20px;background:#fff;border:1px solid var(--brand-border);border-radius:18px;box-shadow:0 10px 30px rgba(34,37,75,.06)}.order-filters__head{display:flex;align-items:center;justify-content:space-between;gap:15px;margin-bottom:15px}.order-filters__head h2{margin:0;color:var(--brand-primary);font:800 1rem var(--brand-font-heading)}.order-export-actions{display:flex;gap:8px;flex-wrap:wrap}.order-export-actions .btn{display:inline-flex;align-items:center;gap:7px}.orders-page .order-filters .smart-options{z-index:100}
        @media(max-width:1000px){.orders-layout{grid-template-columns:1fr}.orders-hero{align-items:flex-start}}@media(max-width:620px){.orders-hero__icon{display:none}.order-panel{padding:19px}.order-item{grid-template-columns:40px 1fr}.order-item>.order-status{grid-column:2;justify-self:start}}
    </style>
</head>
<body class="module-page orders-page">
<?php include __DIR__ . '/partials/navbar.php'; ?>
<main class="container">
    <section class="orders-hero"><div><h1>Bons de commande</h1><p>Centralisez les commandes reçues, rattachez-les aux devis et conservez les documents originaux.</p></div><span class="orders-hero__icon"><i class="fa-solid fa-file-signature"></i></span></section>
    <?php if (isset($_GET['created'])): ?><div class="alert alert-success"><i class="fa-solid fa-circle-check me-2"></i>Le bon de commande a été ajouté.</div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="alert alert-danger"><?= bc_h($error) ?></div><?php endif; ?>
    <form class="order-filters" method="get">
        <div class="order-filters__head"><h2><i class="fa-solid fa-filter me-2"></i>Rechercher et exporter</h2><div class="order-export-actions"><a class="btn btn-sm btn-outline-primary" href="request/export_purchase_orders_excel.php?<?= bc_h($exportQuery) ?>"><i class="fa-solid fa-file-excel"></i> Excel</a><a class="btn btn-sm btn-outline-primary" target="_blank" href="request/export_purchase_orders_pdf.php?<?= bc_h($exportQuery) ?>"><i class="fa-solid fa-file-pdf"></i> PDF</a></div></div>
        <div class="row g-2 align-items-end">
            <div class="col-lg-4"><label class="form-label" for="q">Recherche libre</label><input class="form-control" id="q" name="q" value="<?= bc_h($_GET['q'] ?? '') ?>" placeholder="N° interne, bon client, note…"></div>
            <div class="col-lg-2 col-md-3"><label class="form-label" for="date_from">Reçu du</label><input class="form-control" type="date" id="date_from" name="date_from" value="<?= bc_h($_GET['date_from'] ?? '') ?>"></div>
            <div class="col-lg-2 col-md-3"><label class="form-label" for="date_to">Au</label><input class="form-control" type="date" id="date_to" name="date_to" value="<?= bc_h($_GET['date_to'] ?? '') ?>"></div>
            <div class="col-lg-2 col-md-3"><label class="form-label" for="filter_status">Statut</label><select class="form-select" id="filter_status" name="statut"><option value="">Tous les statuts</option><?php foreach ($statusLabels as $value => $label): ?><option value="<?= $value ?>" <?= ($_GET['statut'] ?? '') === $value ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></div>
            <div class="col-lg-2 col-md-3"><button class="btn btn-primary w-100" type="submit"><i class="fa-solid fa-magnifying-glass me-1"></i> Filtrer</button></div>
            <div class="col-md-6"><label class="form-label" for="filter_client">Client</label><select id="filter_client" name="client_id" data-smart-select data-placeholder="Tous les clients…"><option value="">Tous les clients</option><?php foreach ($clients as $client): ?><option value="<?= (int) $client['id_client'] ?>" <?= (int) ($_GET['client_id'] ?? 0) === (int) $client['id_client'] ? 'selected' : '' ?>><?= bc_h($client['nom_client'] . ($client['code_client'] ? ' — ' . $client['code_client'] : '')) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-6"><label class="form-label" for="filter_devis">Devis</label><select id="filter_devis" name="devis_id" data-smart-select data-placeholder="Tous les devis…"><option value="">Tous les devis</option><?php foreach ($quotes as $quote): ?><option value="<?= (int) $quote['id'] ?>" <?= (int) ($_GET['devis_id'] ?? 0) === (int) $quote['id'] ? 'selected' : '' ?>><?= bc_h($quote['numero_devis'] . ' — ' . ($quote['nom_client'] ?: 'Client non renseigné')) ?></option><?php endforeach; ?></select></div>
        </div>
        <?php if ($exportQuery !== ''): ?><a class="d-inline-block mt-3 small fw-bold text-decoration-none" href="bons_commande.php"><i class="fa-solid fa-xmark me-1"></i>Réinitialiser les filtres</a><?php endif; ?>
    </form>
    <div class="orders-layout">
        <section class="order-panel">
            <h2><i class="fa-solid fa-cloud-arrow-up"></i>Nouveau bon reçu</h2>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= bc_h($_SESSION['bc_csrf']) ?>">
                <div class="row g-2">
                    <div class="col-12"><label class="form-label" for="reference_client">N° du bon de commande client *</label><input class="form-control" id="reference_client" name="reference_client" value="<?= bc_h($_POST['reference_client'] ?? '') ?>" placeholder="Ex. BC-CLIENT-2026-045" required><small class="d-block mt-1 text-muted"><i class="fa-solid fa-wand-magic-sparkles me-1"></i>Le numéro interne FIDEST sera généré automatiquement.</small></div>
                    <div class="col-12"><label class="form-label" for="client_id">Client</label><select class="form-select" id="client_id" name="client_id" data-smart-select data-placeholder="Rechercher un client par nom ou code…"><option value="">Sélectionner un client</option><?php foreach ($clients as $client): ?><option value="<?= (int) $client['id_client'] ?>" <?= (int) ($_POST['client_id'] ?? 0) === (int) $client['id_client'] ? 'selected' : '' ?>><?= bc_h($client['nom_client'] . ($client['code_client'] ? ' — ' . $client['code_client'] : '')) ?></option><?php endforeach; ?></select></div>
                    <div class="col-12"><label class="form-label" for="devis_id">Devis associé</label><select class="form-select" id="devis_id" name="devis_id" data-smart-select data-placeholder="Rechercher un devis ou un client…"><option value="">Aucun devis associé</option><?php foreach ($quotes as $quote): ?><option value="<?= (int) $quote['id'] ?>" <?= (int) ($_POST['devis_id'] ?? 0) === (int) $quote['id'] ? 'selected' : '' ?>><?= bc_h($quote['numero_devis'] . ' — ' . ($quote['nom_client'] ?: 'Client non renseigné')) ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-6"><label class="form-label" for="date_commande">Date du bon *</label><input class="form-control" type="date" id="date_commande" name="date_commande" value="<?= bc_h($_POST['date_commande'] ?? date('Y-m-d')) ?>" required></div>
                    <div class="col-md-6"><label class="form-label" for="date_reception">Reçu le *</label><input class="form-control" type="date" id="date_reception" name="date_reception" value="<?= bc_h($_POST['date_reception'] ?? date('Y-m-d')) ?>" required></div>
                    <div class="col-md-6"><label class="form-label" for="montant">Montant (FCFA)</label><input class="form-control" type="number" min="0" step="0.01" id="montant" name="montant" value="<?= bc_h($_POST['montant'] ?? '') ?>"></div>
                    <div class="col-md-6"><label class="form-label" for="statut">Statut</label><select class="form-select" id="statut" name="statut"><?php foreach ($statusLabels as $value => $label): ?><option value="<?= $value ?>" <?= ($_POST['statut'] ?? 'recu') === $value ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></div>
                    <div class="col-12"><label class="form-label" for="notes">Informations complémentaires</label><textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Objet, délai souhaité, interlocuteur…"><?= bc_h($_POST['notes'] ?? '') ?></textarea></div>
                    <div class="col-12"><label class="upload-zone" id="uploadZone" for="document"><i class="fa-solid fa-file-arrow-up"></i><strong id="uploadTitle">Ajouter le document reçu *</strong><small id="uploadHelp">PDF, JPG ou PNG · 10 Mo maximum</small></label><input hidden type="file" id="document" name="document" accept="application/pdf,image/jpeg,image/png" required></div>
                </div>
                <button class="btn btn-primary w-100 mt-2" type="submit"><i class="fa-solid fa-floppy-disk me-2"></i>Enregistrer le bon de commande</button>
            </form>
        </section>
        <section class="order-panel">
            <h2><i class="fa-solid fa-list-check"></i>Commandes enregistrées <span class="badge badge-soft ms-auto"><?= count($orders) ?></span></h2>
            <div class="order-list">
                <?php if (!$orders): ?><div class="py-5 text-center text-muted"><i class="fa-regular fa-folder-open fa-2x mb-3 d-block"></i>Aucun bon de commande enregistré.</div><?php endif; ?>
                <?php foreach ($orders as $order): ?><article class="order-item"><span class="order-item__icon"><i class="fa-solid fa-file-lines"></i></span><div><h3><?= bc_h($order['numero_bc']) ?></h3><p><strong>Bon client : <?= bc_h($order['reference_client'] ?: 'Non renseigné') ?></strong><br><?= bc_h($order['nom_client'] ?: 'Client non renseigné') ?><?= $order['numero_devis'] ? ' · Devis ' . bc_h($order['numero_devis']) : '' ?></p><div class="order-item__meta"><span><i class="fa-regular fa-calendar"></i> Reçu le <?= bc_h(date('d/m/Y', strtotime($order['date_reception']))) ?></span><?php if ($order['montant'] !== null): ?><span><i class="fa-solid fa-coins"></i> <?= number_format((float) $order['montant'], 0, ',', ' ') ?> FCFA</span><?php endif; ?></div><a class="document-link" href="<?= bc_h($order['fichier']) ?>" target="_blank" rel="noopener"><i class="fa-regular fa-eye"></i> Voir <?= bc_h($order['fichier_original']) ?></a></div><span class="order-status <?= bc_h($order['statut']) ?>"><?= bc_h($statusLabels[$order['statut']] ?? $order['statut']) ?></span></article><?php endforeach; ?>
            </div>
        </section>
    </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/smart-select.js"></script>
</body>
</html>
