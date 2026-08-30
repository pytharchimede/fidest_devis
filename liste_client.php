<?php
include 'auth_check.php';
require_once __DIR__ . '/bootstrap.php';
$db = app_database();
$clients = $db->query("SELECT c.*,(SELECT COUNT(*) FROM offre o WHERE o.client_id=c.id_client AND o.archived_at IS NULL) offres,(SELECT COUNT(*) FROM devis d WHERE d.client_id=c.id_client AND d.archived_at IS NULL) devis,(SELECT COALESCE(SUM(d.total_ttc),0) FROM devis d WHERE d.client_id=c.id_client AND d.archived_at IS NULL) volume FROM client c ORDER BY c.nom_client")->fetchAll();
$error = (string)($_SESSION['client_error'] ?? '');
unset($_SESSION['client_error']);
function ch(mixed $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!doctype html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Clients · FIDEST</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/modules.css">
    <style>
        .clients-hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            padding: 30px;
            color: #fff;
            background: linear-gradient(125deg, var(--brand-primary), var(--brand-primary-soft));
            border-radius: 25px
        }

        .clients-hero h1 {
            margin: 0;
            color: #fff !important
        }

        .clients-hero p {
            margin: 8px 0 0;
            color: #ffffffba
        }

        .client-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(330px, 1fr));
            gap: 18px;
            margin-top: 24px
        }

        .client-card {
            position: relative;
            overflow: hidden;
            padding: 22px;
            background: #fff;
            border: 1px solid var(--brand-border);
            border-radius: 20px;
            box-shadow: 0 10px 30px #22254b0e;
            transition: .22s
        }

        .client-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 45px #22254b1c
        }

        .client-identity {
            display: flex;
            align-items: center;
            gap: 15px
        }

        .client-logo {
            display: grid;
            width: 76px;
            height: 76px;
            flex: 0 0 76px;
            place-items: center;
            overflow: hidden;
            color: var(--brand-primary);
            background: #eff0f5;
            border: 1px solid #e2e3ea;
            border-radius: 18px;
            font: 850 1.4rem var(--brand-font-heading)
        }

        .client-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 7px;
            background: #fff
        }

        .client-identity h2 {
            margin: 0;
            color: var(--brand-primary);
            font: 850 1.12rem var(--brand-font-heading)
        }

        .client-code {
            display: inline-flex;
            margin-top: 6px;
            padding: 4px 8px;
            color: #765900;
            background: #fff1bc;
            border-radius: 99px;
            font-size: .65rem;
            font-weight: 900
        }

        .client-location {
            min-height: 55px;
            margin: 18px 0;
            color: var(--brand-text-muted);
            font-size: .82rem
        }

        .client-location i {
            width: 18px;
            color: #d69d00
        }

        .client-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 7px;
            padding: 12px;
            background: #f6f6f9;
            border-radius: 13px
        }

        .client-stats span {
            text-align: center
        }

        .client-stats strong,
        .client-stats small {
            display: block
        }

        .client-stats strong {
            color: var(--brand-primary)
        }

        .client-stats small {
            color: #858899;
            font-size: .61rem;
            text-transform: uppercase
        }

        .client-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 16px
        }

        .client-actions a {
            text-decoration: none;
            font-weight: 800
        }
    </style>
</head>

<body class="module-page"><?php include __DIR__ . '/partials/navbar.php'; ?><main class="container">
        <section class="clients-hero">
            <div>
                <div class="small text-warning fw-bold">PORTEFEUILLE CLIENTS</div>
                <h1>Clients</h1>
                <p>Identité visuelle, coordonnées et activité commerciale réunies.</p>
            </div><button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#addClientModal"><i class="fa-solid fa-user-plus me-2"></i>Nouveau client</button>
        </section><?php if ($error): ?><div class="alert alert-danger mt-3"><?= ch($error) ?></div><?php endif; ?><section class="client-grid"><?php foreach ($clients as $c): $initials = mb_strtoupper(mb_substr(trim((string)$c['nom_client']), 0, 2)); ?><article class="client-card">
                    <div class="client-identity">
                        <div class="client-logo"><?php if ($c['logo_client']): ?><img src="<?= ch($c['logo_client']) ?>" alt="Logo <?= ch($c['nom_client']) ?>"><?php else: ?><?= ch($initials) ?><?php endif; ?></div>
                        <div>
                            <h2><?= ch($c['nom_client']) ?></h2><span class="client-code"><?= ch($c['code_client']) ?></span>
                        </div>
                    </div>
                    <div class="client-location">
                        <div><i class="fa-solid fa-location-dot"></i><?= ch($c['localisation_client']) ?></div>
                        <div><i class="fa-solid fa-map"></i><?= ch($c['commune_client']) ?> · <?= ch($c['pays_client']) ?></div>
                        <div><i class="fa-solid fa-envelope"></i><?= ch($c['bp_client']) ?></div>
                    </div>
                    <div class="client-stats"><span><strong><?= (int)$c['offres'] ?></strong><small>AO actifs</small></span><span><strong><?= (int)$c['devis'] ?></strong><small>Devis</small></span><span><strong><?= number_format((float)$c['volume'] / 1000000, 1, ',', ' ') ?> M</strong><small>FCFA</small></span></div>
                    <div class="client-actions"><small class="text-muted"><i class="fa-regular fa-calendar"></i> Depuis <?= ch($c['date_creat_client']) ?></small><a href="modifier_client.php?id=<?= (int)$c['id_client'] ?>"><i class="fa-solid fa-pen me-1"></i>Modifier</a></div>
                </article><?php endforeach; ?></section>
    </main>
    <div class="modal fade" id="addClientModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title h5"><i class="fa-solid fa-building-circle-arrow-right me-2 text-warning"></i>Nouveau client</h2><button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="request/ajouter_client.php" method="post" enctype="multipart/form-data">
                        <div class="row g-3">
                            <div class="col-lg-5"><label class="form-label" for="logo_client">Logo du client</label><input class="form-control" type="file" id="logo_client" name="logo_client" accept="image/jpeg,image/png,image/webp,image/svg+xml"></div>
                            <div class="col-lg-7">
                                <div class="row g-3">
                                    <div class="col-md-4"><label class="form-label">Code client *</label><input class="form-control" name="code_client" required></div>
                                    <div class="col-md-8"><label class="form-label">Nom du client *</label><input class="form-control" name="nom_client" required></div>
                                    <div class="col-12"><label class="form-label">Localisation *</label><input class="form-control" name="localisation_client" required></div>
                                    <div class="col-md-6"><label class="form-label">Commune *</label><input class="form-control" name="commune_client" required></div>
                                    <div class="col-md-6"><label class="form-label">Pays *</label><input class="form-control" name="pays_client" required></div>
                                    <div class="col-md-6"><label class="form-label">Boîte postale *</label><input class="form-control" name="bp_client" required></div>
                                    <div class="col-md-6"><label class="form-label">Date de création *</label><input type="date" class="form-control" name="date_creat_client" value="<?= date('Y-m-d') ?>" required></div>
                                </div>
                            </div>
                            <div class="col-12 text-end"><button class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-2"></i>Enregistrer le client</button></div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script><?php if ($error): ?><script>
            new bootstrap.Modal(document.getElementById('addClientModal')).show()
        </script><?php endif; ?>
</body>

</html>