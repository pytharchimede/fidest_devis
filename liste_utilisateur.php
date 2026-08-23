<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/bootstrap.php';
// Inclure les fichiers nécessaires
require_once 'model/Database.php';
require_once 'model/User.php';

$pdo = Database::getConnection();
$userModel = new User($pdo);

// Récupérer la liste des utilisateurs
$stmt = $pdo->query("SELECT * FROM user_devis");
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
                <div><h1>L’équipe</h1><p>Gérez les membres, leurs accès et leur statut.</p></div>
            </div>
            <a href="ajouter_utilisateur.php" class="btn btn-success"><i class="fa fa-plus"></i> Ajouter un membre</a>
        </div>

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
                            <div class="team-contact"><span><i class="far fa-envelope"></i><?php echo htmlspecialchars($user['mail_pro']); ?></span><?php if(!empty($user['departement'])): ?><span><i class="fas fa-building"></i><?= htmlspecialchars($user['departement']) ?></span><?php endif; ?></div>
                            <p class="card-text">
                                <span class="team-status <?= $user['active'] ? 'is-active' : 'is-inactive' ?>"><?php echo $user['active'] ? 'Actif' : 'Désactivé'; ?></span>
                            </p>
                        </div>
                        <div class="card-footer text-center">
                            <div class="actions">
                                <a href="modifier_utilisateur.php?id=<?php echo $user['id']; ?>" class="btn btn-warning btn-sm"><i class="fa fa-edit"></i> Modifier</a>
                                <?php if ($user['active']): ?>
                                    <a href="desactiver_utilisateur.php?id=<?php echo $user['id']; ?>" class="btn btn-secondary btn-sm"><i class="fa fa-ban"></i> Désactiver</a>
                                <?php else: ?>
                                    <a href="reactiver_utilisateur.php?id=<?php echo $user['id']; ?>" class="btn btn-success btn-sm"><i class="fa fa-check"></i> Réactiver</a>
                                <?php endif; ?>
                                <a href="supprimer_utilisateur.php?id=<?php echo $user['id']; ?>" class="btn btn-danger btn-sm action-delete" onclick="return confirm('Supprimer définitivement ce membre ?')"><i class="fa fa-trash"></i> Supprimer le membre</a>
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
