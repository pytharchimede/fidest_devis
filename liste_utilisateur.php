<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/bootstrap.php';
// Inclure les fichiers nécessaires
require_once 'model/Database.php';
require_once 'model/User.php';

$pdo = Database::getConnection();
$userModel = new User($pdo);

$search = trim((string) ($_GET['q'] ?? ''));
$query = 'SELECT * FROM user_devis';
$params = [];
if ($search !== '') {
    $query .= ' WHERE nom LIKE :search OR prenom LIKE :search OR mail_pro LIKE :search';
    $params['search'] = '%' . $search . '%';
}
$query .= ' ORDER BY nom, prenom';
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Utilisateurs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet"> <!-- Ajout de FontAwesome -->
    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Poppins', sans-serif;
        }

        .navbar {
            background-color: #1d2b57;
        }

        .navbar-brand img {
            height: 50px;
        }

        .nav-link {
            color: #fff !important;
        }

        .nav-link.active {
            color: #ffc107 !important;
        }

        .container {
            margin-top: 40px;
        }

        h1 {
            color: #1d2b57;
            font-weight: 600;
            margin-bottom: 30px;
        }

        .btn-primary {
            background-color: #fabd02;
            border-color: #fabd02;
        }

        .btn-primary:hover {
            background-color: #e0a800;
            border-color: #e0a800;
        }

        .card {
            border: none;
            background-color: #fff;
            border-radius: 15px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }

        .card:hover {
            transform: scale(1.05);
        }

        .card-img-top {
            border-radius: 50%;
            width: 100px;
            height: 100px;
            object-fit: cover;
            margin-top: -50px;
            border: 3px solid #fff;
        }

        .card-body {
            text-align: center;
        }

        .card-title {
            font-weight: 600;
            color: #1d2b57;
        }

        .card-text {
            font-size: 14px;
            color: #888;
        }

        .card-footer {
            background-color: #f7f7f7;
            border-top: none;
        }

        .actions a {
            margin: 5px;
        }

        .footer {
            background-color: #1d2b57;
            color: #fff;
            padding: 15px 0;
            text-align: center;
            position: fixed;
            bottom: 0;
            width: 100%;
        }

        .card-deck .card {
            margin-bottom: 20px;
        }

        .photo-profile {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: -40px;
        }

        .photo-profile img {
            border-radius: 50%;
            width: 100px;
            height: 100px;
            object-fit: cover;
            border: 3px solid #fff;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.15);
        }

        .team-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin: 0 0 24px;
            padding: 16px;
            background: var(--brand-surface);
            border: 1px solid var(--brand-border);
            border-radius: var(--brand-radius-md);
            box-shadow: 0 8px 24px rgba(34, 37, 75, .06);
        }

        .team-search {
            position: relative;
            flex: 1;
            max-width: 560px;
        }

        .team-search i {
            position: absolute;
            top: 15px;
            left: 16px;
            color: var(--brand-text-muted);
        }

        .team-search input {
            padding-left: 44px;
        }

        .team-search .btn {
            position: absolute;
            top: 4px;
            right: 4px;
            min-height: 40px;
            padding: 8px 14px;
        }

        .team-count {
            color: var(--brand-text-muted);
            font-size: .85rem;
            font-weight: 700;
        }

        .action-delete {
            display: inline-grid !important;
            width: 38px;
            min-height: 38px !important;
            padding: 8px !important;
            place-items: center;
            color: #a62c37 !important;
            background: #fbe9eb !important;
            border: 1px solid #f2cbd0 !important;
        }

        .team-page .actions .action-delete {
            grid-column: 1/-1;
            justify-self: center;
            margin-top: 2px !important;
        }

        @media(max-width:640px) {
            .team-toolbar {
                align-items: stretch;
                flex-direction: column
            }

            .team-search {
                max-width: none
            }

            .team-count {
                font-size: .78rem
            }
        }
    </style>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/modules.css">
    <link rel="stylesheet" href="css/social-pro.css">
</head>

<body class="module-page team-page">
    <!-- Menu de navigation -->
    <?php include __DIR__ . '/partials/navbar.php'; ?>

    <!-- Contenu principal -->
    <div class="container">
        <div class="module-heading">
            <div class="module-heading__copy">
                <span class="module-heading__icon"><i class="fas fa-users"></i></span>
                <div>
                    <h1>L’équipe</h1>
                    <p>Gérez les membres, leurs accès et leur statut.</p>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="request/export_users_pdf.php" class="btn btn-outline-primary" target="_blank"><i class="fas fa-file-pdf me-2"></i>PDF</a>
                <a href="request/export_users_excel.php" class="btn btn-outline-primary"><i class="fas fa-file-excel me-2"></i>Excel</a>
                <a href="ajouter_utilisateur.php" class="btn btn-success"><i class="fa fa-plus me-2"></i>Ajouter un membre</a>
            </div>
        </div>

        <form class="team-toolbar" method="get" action="liste_utilisateur.php">
            <div class="team-search"><i class="fas fa-search" aria-hidden="true"></i><label class="visually-hidden" for="user-search">Rechercher un membre</label><input class="form-control" id="user-search" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Rechercher par nom, prénom ou email" type="search"><button class="btn btn-primary" type="submit" aria-label="Lancer la recherche"><i class="fas fa-arrow-right"></i></button></div>
            <div class="team-count"><?= count($users) ?> membre<?= count($users) > 1 ? 's' : '' ?> affiché<?= count($users) > 1 ? 's' : '' ?><?php if ($search !== ''): ?> pour « <?= htmlspecialchars($search) ?> »<?php endif; ?></div>
        </form>

        <!-- Card Deck -->
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4 team-grid">
            <?php foreach ($users as $user): ?>
                <div class="col">
                    <div class="card">
                        <div class="team-cover"><span>FIDEST</span><i class="fas fa-users"></i></div>
                        <div class="photo-profile">
                            <img src="<?php echo $user['photo'] ?: 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($user['mail_pro']))) . '?d=mm&s=200'; ?>" alt="Photo de profil">
                        </div>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($user['prenom']) . ' ' . htmlspecialchars($user['nom']); ?></h5>
                            <p class="team-role"><?php echo htmlspecialchars($user['fonction'] ?: 'Membre de l’équipe FIDEST'); ?></p>
                            <div class="team-contact"><span><i class="far fa-envelope"></i><?php echo htmlspecialchars($user['mail_pro']); ?></span><?php if (!empty($user['departement'])): ?><span><i class="fas fa-building"></i><?= htmlspecialchars($user['departement']) ?></span><?php endif; ?></div>
                            <p class="card-text">
                                <span class="team-status <?= $user['active'] ? 'is-active' : 'is-inactive' ?>"><?php echo $user['active'] ? 'Actif' : 'Désactivé'; ?></span>
                            </p>
                        </div>
                        <div class="card-footer text-center">
                            <div class="actions">
                                <a href="modifier_utilisateur.php?id=<?php echo $user['id']; ?>" class="btn btn-warning btn-sm"><i class="fa fa-edit"></i> Modifier</a>
                                <a href="request/export_profile_pdf.php?id=<?php echo (int) $user['id']; ?>" class="btn btn-outline-primary btn-sm" target="_blank"><i class="fa fa-file-pdf"></i> Profil PDF</a>
                                <?php if ($user['active']): ?>
                                    <a href="desactiver_utilisateur.php?id=<?php echo $user['id']; ?>" class="btn btn-secondary btn-sm"><i class="fa fa-ban"></i> Désactiver</a>
                                <?php else: ?>
                                    <a href="reactiver_utilisateur.php?id=<?php echo $user['id']; ?>" class="btn btn-success btn-sm"><i class="fa fa-check"></i> Réactiver</a>
                                <?php endif; ?>
                                <a href="supprimer_utilisateur.php?id=<?php echo $user['id']; ?>" class="btn btn-danger btn-sm action-delete" aria-label="Supprimer <?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?>" title="Supprimer ce membre" onclick="return confirm('Supprimer définitivement ce membre ?')"><i class="fa fa-trash" aria-hidden="true"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Pied de page -->
    <div class="footer">
        <p>&copy; 2024 Gestion des Utilisateurs | Tous droits réservés</p>
    </div>

    <!-- Scripts Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
</body>

</html>