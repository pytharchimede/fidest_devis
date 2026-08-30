<?php include 'auth_check.php'; ?>
<!DOCTYPE html>

<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Liste des Devis - BTP</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <link rel="stylesheet" href="css/style.css">

    <style>
        body {
            background-color: #f7f9fc;
            font-family: Arial, sans-serif;
        }

        .container {
            margin-top: 2rem;
        }

        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 1.5rem;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            background-color: #ffffff;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.2);
        }

        .card-header {
            background: linear-gradient(135deg, #1d2b57 0%, #2b3f8a 100%);
            color: #ffffff;
            font-weight: 700;
            text-align: left;
            padding: 1rem 1.25rem;
            font-size: 1.05rem;
            display: flex;
            align-items: center;
            gap: .6rem;
        }

        .card-header i {
            margin-right: 8px;
            font-size: 1.3rem;
        }

        .card-body {
            padding: 1.25rem 1.25rem .75rem;
        }

        .info-grid p {
            display: flex;
            justify-content: space-between;
            margin: 0.4rem 0;
            font-size: 0.95rem;
            color: #555;
        }

        .card-footer {
            display: flex;
            justify-content: flex-start;
            /* éviter les boutons centrés/collés */
            align-items: center;
            gap: .6rem;
            /* espace constant entre boutons */
            flex-wrap: wrap;
            /* s'adapte aux petites largeurs */
            background-color: #f8f9fa;
            padding: .8rem 1rem;
            border-top: 1px solid #e0e0e0;
        }

        .card-footer a {
            color: #fff;
            text-decoration: none;
            font-weight: 600;
            padding: .55rem 1rem;
            border-radius: 999px;
            /* pill */
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            border: none;
            box-shadow: 0 4px 10px rgba(0, 0, 0, .12);
            transition: transform .15s ease, box-shadow .2s ease, opacity .2s ease;
            white-space: nowrap;
        }

        .btn-view {
            background: linear-gradient(135deg, #0d6efd, #0b5ed7);
        }

        .btn-view:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 14px rgba(13, 110, 253, .35);
        }

        .btn-edit {
            background: linear-gradient(135deg, #22c55e, #16a34a);
        }

        .btn-edit:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 14px rgba(34, 197, 94, .35);
        }

        .btn-delete {
            background: linear-gradient(135deg, #dc3545, #b02a37);
        }

        .btn-delete:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 14px rgba(220, 53, 69, .35);
        }

        .footer-actions {
            display: flex;
            justify-content: space-around;
            margin-bottom: 1rem;
        }

        .footer-validation {
            display: flex;
            justify-content: center;
            padding: 1rem;
            border-top: 1px solid #e0e0e0;
            gap: 1rem;
        }

        .btn-view,
        .btn-delete,
        .btn-edit,
        .btn-validate {
            color: #ffffff;
            text-decoration: none;
            font-weight: 600;
            padding: .55rem 1rem;
            border-radius: 999px;
        }

        .btn-view {
            background-color: #007bff;
        }

        .btn-view:hover {
            background-color: #0056b3;
        }

        .btn-hide {
            background-color: #6c757d;
        }

        .btn-hide:hover {
            background-color: #5a6268;
        }

        .btn-edit {
            background-color: #28a745;
        }

        .btn-edit:hover {
            background-color: #218838;
        }

        .btn-validate {
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background-color: #ffc107;
        }

        .btn-validate:hover {
            background-color: #e0a800;
        }

        .btn-validate.commerciale {
            background-color: #ff851b;
        }

        .btn-validate.generale {
            background-color: #17a2b8;
        }

        .btn-validate.commerciale:hover {
            background-color: #d66d00;
        }

        .btn-validate.generale:hover {
            background-color: #138496;
        }

        .validated {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
            padding: .5rem 1rem;
            border-radius: 999px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            box-shadow: 0 4px 10px rgba(34, 197, 94, .25);
        }

        .card {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.2);
        }

        .footer-validation a,
        .footer-validation span {
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
    <link rel="stylesheet" href="css/quote-pro.css">
    <link rel="stylesheet" href="css/smart-select.css">

</head>

<body class="quote-list-page">



    <!-- Menu -->

    <nav class="navbar navbar-expand-lg navbar-dark">

        <div class="container">

            <a class="navbar-brand" href="#">

                <img src="img/logo_fidest.png" alt="Logo">

            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">

                <span class="navbar-toggler-icon"></span>

            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <?php include 'menu.php'; ?>
            </div>

        </div>

    </nav>



    <div class="container">





        <?php

        require_once __DIR__ . '/bootstrap.php';
        $quoteSearch = new App\Domain\Quote\QuoteSearchRepository(app_database());
        $filterOptions = $quoteSearch->options();
        $devis = $quoteSearch->search($_GET);
        // Recherche centralisée dans QuoteSearchRepository.
        // Charger registre des BL signés
        $bl_registry = ['items' => []];
        $regPath = __DIR__ . '/data/bl_registry.json';
        if (file_exists($regPath)) {
            $json = file_get_contents($regPath);
            $tmp = json_decode($json, true);
            if (is_array($tmp)) $bl_registry = $tmp;
        }
        $bl_index = [];
        foreach ($bl_registry['items'] as $it) {
            $bl_index[(int)$it['devisId']] = $it;
        }



        // Calcul du montant total TTC des devis affichés

        $total_ttc = 0;

        $nb_devis = 0;

        foreach ($devis as $de) {

            $total_ttc += $de['total_ttc'];

            $nb_devis++;
        }



        ?>



        <div class="page-heading">
            <h1 class="text-center mb-4">Vos devis <span class="count-badge"><?php echo $nb_devis; ?></span></h1>
            <p>Retrouvez, filtrez et gérez vos documents commerciaux.</p>
        </div>





        <!-- Formulaire de recherche -->

        <form method="GET" action="liste_devis.php" class="row g-3 mb-4 quote-filters">
            <?php if (!empty($_GET['status'])): ?><input type="hidden" name="status" value="<?= htmlspecialchars((string) $_GET['status']) ?>"><?php endif; ?>
            <?php if (!empty($_GET['period'])): ?><input type="hidden" name="period" value="<?= htmlspecialchars((string) $_GET['period']) ?>"><?php endif; ?>
            <div class="col-12"><label class="form-label">Recherche globale</label><input class="form-control" name="q" value="<?= htmlspecialchars((string)($_GET['q'] ?? '')) ?>" placeholder="N° de devis, destinataire ou correspondant"></div>
            <div class="col-md-4"><label class="form-label">Clients</label><select name="clients[]" multiple data-smart-select data-placeholder="Rechercher des clients…"><?php foreach($filterOptions['clients'] as $o): ?><option value="<?= (int)$o['id'] ?>" <?= in_array((string)$o['id'],(array)($_GET['clients']??[]),true)?'selected':'' ?>><?= htmlspecialchars($o['label']) ?></option><?php endforeach ?></select></div>
            <div class="col-md-4"><label class="form-label">Utilisateurs éditeurs</label><select name="users[]" multiple data-smart-select data-placeholder="Rechercher des utilisateurs…"><?php foreach($filterOptions['users'] as $o): ?><option value="<?= (int)$o['id'] ?>" <?= in_array((string)$o['id'],(array)($_GET['users']??[]),true)?'selected':'' ?>><?= htmlspecialchars($o['label']) ?></option><?php endforeach ?></select></div>
            <div class="col-md-4"><label class="form-label">Produits</label><select name="products[]" multiple data-smart-select data-placeholder="Rechercher des produits…"><?php foreach($filterOptions['products'] as $o): ?><option value="<?= htmlspecialchars($o['id']) ?>" <?= in_array($o['id'],(array)($_GET['products']??[]),true)?'selected':'' ?>><?= htmlspecialchars($o['label']) ?></option><?php endforeach ?></select></div>
            <div class="col-md-3"><label class="form-label">Montant TTC minimum</label><input type="number" name="montant_min" value="<?= htmlspecialchars((string)($_GET['montant_min']??'')) ?>" class="form-control" placeholder="0"></div>
            <div class="col-md-3"><label class="form-label">Montant TTC maximum</label><input type="number" name="montant_max" value="<?= htmlspecialchars((string)($_GET['montant_max']??'')) ?>" class="form-control" placeholder="Sans limite"></div>

            <div class="col-md-3">

                <label for="date_debut" class="form-label">Date début</label>

                <input type="date" id="date_debut" name="date_debut" value="<?= htmlspecialchars((string) ($_GET['date_debut'] ?? '')) ?>" class="form-control">

            </div>

            <div class="col-md-3">

                <label for="date_fin" class="form-label">Date fin</label>

                <input type="date" id="date_fin" name="date_fin" value="<?= htmlspecialchars((string) ($_GET['date_fin'] ?? '')) ?>" class="form-control">

            </div>

            <div class="col-12 d-flex gap-2 flex-wrap">
                <button type="submit" class="btn btn-primary"><i class="fas fa-sliders"></i> Appliquer les filtres</button>
                <a href="liste_devis.php" class="btn btn-light"><i class="fas fa-rotate-left"></i> Réinitialiser</a>
            </div>

        </form>



        <div class="quote-summary">
            <div>
                <div class="quote-summary__label">Volume total affiché</div>
                <div class="quote-summary__value"><?php echo number_format($total_ttc, 0, ',', ' '); ?> FCFA</div>
            </div>
            <div class="quote-summary__actions">
                <a target="_blank" href="request/export_resultat.php?<?php echo http_build_query($_GET); ?>" class="btn"><i class="fas fa-file-pdf"></i> Exporter PDF</a>
                <a href="request/export_devis_excel.php?<?php echo http_build_query($_GET); ?>" class="btn"><i class="fas fa-file-excel"></i> Exporter Excel</a>
                <a href="generer_devis.php" class="btn btn-accent"><i class="fas fa-plus"></i> Nouveau devis</a>
            </div>
        </div>



        <!-- Grid displaying quotes -->

        <div class="card-grid">
            <!-- PHP code to fetch and display quotes from the database -->
            <?php foreach ($devis as $de) : ?>
                <?php
                    $isValidated = !empty($de['validation_commerciale']) && !empty($de['validation_generale']);
                    $awaitingGeneral = !empty($de['validation_commerciale']) && empty($de['validation_generale']);
                    $statusClass = $isValidated ? 'validated' : ($awaitingGeneral ? 'general' : 'commercial');
                    $statusLabel = $isValidated ? 'Validé' : ($awaitingGeneral ? 'Validation DG' : 'Validation commerciale');
                    if (($de['statut_devis'] ?? '') === 'rejete') { $statusClass='commercial'; $statusLabel='Rejeté'; }
                    elseif (($de['statut_devis'] ?? '') === 'en_attente' && !$isValidated) $statusLabel='En attente';
                ?>
                <article class="card quote-card">
                    <div class="card-header">
                        <div class="quote-card__number"><i class="fas fa-file-invoice"></i><?= htmlspecialchars($de['numero_devis']) ?></div>
                        <span class="quote-status quote-status--<?= $statusClass ?>"><i class="fas fa-circle"></i><?= $statusLabel ?></span>
                    </div>
                    <div class="card-body">
                        <div class="quote-parties">
                            <div class="quote-party"><small>Émetteur</small><strong><?= htmlspecialchars($de['emis_par'] ?: 'Non renseigné') ?></strong></div>
                            <i class="fas fa-arrow-right"></i>
                            <div class="quote-party"><small>Destinataire</small><strong><?= htmlspecialchars($de['destine_a'] ?: 'Non renseigné') ?></strong></div>
                        </div>
                        <div class="quote-amount">
                            <div><small>Montant TTC</small><strong><?= number_format((float)$de['total_ttc'], 0, ',', ' ') ?> FCFA</strong></div>
                            <div class="quote-date">Émis le <?= htmlspecialchars($de['date_emission']) ?><br>Échéance <?= htmlspecialchars($de['date_expiration']) ?></div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <a class="action-pdf" target="_blank" href="request/export_pdf.php?devisId=<?= $de['id'] ?>"><i class="fas fa-file-arrow-down"></i> PDF</a>
                        <a class="action-preview" target="_blank" href="request/preview_pdf.php?devisId=<?= $de['id'] ?>"><i class="fas fa-eye"></i> Aperçu</a>
                        <a class="action-delivery" target="_blank" href="export_bl.php?devisId=<?= $de['id'] ?>"><i class="fas fa-truck-fast"></i> Générer BL</a>
                        <?php if (isset($bl_index[(int)$de['id']])): $bl = $bl_index[(int)$de['id']]; ?>
                            <a class="action-signed" target="_blank" href="<?= htmlspecialchars($bl['file']) ?>"><i class="fas fa-circle-check"></i> BL signé</a>
                            <a class="btn-edit disabled" href="#" tabindex="-1" aria-disabled="true"><i class="fas fa-pen"></i> Modifier (verrouillé)</a>
                        <?php else: ?>
                            <a class="action-upload" href="request/upload_bl.php?devisId=<?= $de['id'] ?>"><i class="fas fa-cloud-arrow-up"></i> Charger BL signé</a>
                            <a class="action-edit" href="modifier_devis.php?devisId=<?= $de['id'] ?>"><i class="fas fa-pen-to-square"></i> Modifier</a>
                        <?php endif; ?>
                        <a class="btn-delete" href="request/masquer_devis.php?devisId=<?= $de['id'] ?>" onclick="return confirm('Mettre ce devis à la corbeille ?')"><i class="fas fa-trash"></i> Mettre à la corbeille</a>
                    </div>
                    <div class="footer-validation">
                        <?php if (($de['statut_devis'] ?? '') === 'rejete') : ?>
                            <a class="btn-validate commerciale" href="request/statut_devis.php?devisId=<?= $de['id'] ?>&statut=en_attente"><i class="fas fa-rotate-left"></i> Remettre en attente</a>
                        <?php elseif (!$de['validation_commerciale']) : ?>
                            <a class="btn-validate commerciale" href="request/valider_commerciale.php?devisId=<?= $de['id'] ?>"><i class="fas fa-check-circle"></i> Valider Commerciale</a>
                        <?php elseif (!$de['validation_generale']) : ?>
                            <a class="btn-validate generale" href="request/valider_generale.php?devisId=<?= $de['id'] ?>"><i class="fas fa-check-circle"></i> Valider Générale</a>
                        <?php else : ?>
                            <span class="validated"><i class="fas fa-check-double"></i> Déjà Validé</span>
                        <?php endif; ?>
                        <?php if (!$de['validation_generale'] && ($de['statut_devis'] ?? '') !== 'rejete') : ?><a class="btn btn-sm btn-outline-danger" href="request/statut_devis.php?devisId=<?= $de['id'] ?>&statut=rejete" onclick="return confirm('Marquer ce devis comme rejeté ?')">Rejeter</a><?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>



    </div>



    <!-- Footer -->

    <footer class="footer text-white text-center py-3">

        <div class="container">

            <p>&copy; <?php echo gmdate('Y'); ?> FIDEST. Tous droits réservés.</p>

            <div class="social-icons">

                <a href="#" class="fab fa-facebook-f"></a>

                <a href="#" class="fab fa-twitter"></a>

                <a href="#" class="fab fa-linkedin-in"></a>

            </div>

        </div>

    </footer>



    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/smart-select.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>
