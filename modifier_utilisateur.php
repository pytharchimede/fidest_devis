<?php
// Inclure les fichiers nécessaires
require_once 'model/Database.php';
require_once 'model/User.php';

$pdo = Database::getConnection();
$userModel = new User($pdo);

// Vérifier si l'ID de l'utilisateur est fourni dans l'URL
if (isset($_GET['id'])) {
    $userId = $_GET['id'];
    // Récupérer les données de l'utilisateur depuis la base de données
    $stmt = $pdo->prepare("SELECT * FROM user_devis WHERE id = :id");
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch();

    // Vérifier si l'utilisateur existe
    if (!$user) {
        echo "Utilisateur non trouvé.";
        exit;
    }
} else {
    echo "Aucun ID utilisateur spécifié.";
    exit;
}

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mail_pro = $_POST['mail_pro'];
    $password = $_POST['password'];
    $hashedPassword = !empty($password) ? hash("sha512", $password) : $user['password'];
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $modifier_devis = isset($_POST['modifier_devis']) ? 1 : 0;
    $visualiser_devis = isset($_POST['visualiser_devis']) ? 1 : 0;
    $soumettre_devis = isset($_POST['soumettre_devis']) ? 1 : 0;
    $masquer_devis = isset($_POST['masquer_devis']) ? 1 : 0;
    $envoyer_devis = isset($_POST['envoyer_devis']) ? 1 : 0;
    $valider_devis = isset($_POST['valider_devis']) ? 1 : 0;

    // Préparer la requête de mise à jour
    $stmt = $pdo->prepare("UPDATE user_devis SET mail_pro = :mail_pro, password = :password, nom = :nom, prenom = :prenom, modifier_devis = :modifier_devis, visualiser_devis = :visualiser_devis, soumettre_devis = :soumettre_devis, masquer_devis = :masquer_devis, envoyer_devis = :envoyer_devis, valider_devis = :valider_devis WHERE id = :id");
    $stmt->execute([
        'mail_pro' => $mail_pro,
        'password' => $hashedPassword,
        'nom' => $nom,
        'prenom' => $prenom,
        'modifier_devis' => $modifier_devis,
        'visualiser_devis' => $visualiser_devis,
        'soumettre_devis' => $soumettre_devis,
        'masquer_devis' => $masquer_devis,
        'envoyer_devis' => $envoyer_devis,
        'valider_devis' => $valider_devis,
        'id' => $userId
    ]);

    // Redirection après modification
    header('Location: liste_utilisateur.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier un Utilisateur</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        /* Style similaire à celui de la page d'ajout */
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

        .form-control {
            border-radius: 15px;
            padding: 10px;
        }

        .form-label {
            font-weight: 600;
            color: #1d2b57;
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

        .navbar-toggler-icon {
            background-color: #fff;
        }

        .card-body {
            padding: 30px;
        }

        .card-header {
            background-color: #1d2b57;
            color: white;
            font-size: 18px;
            font-weight: bold;
        }

        .form-switch {
            display: flex;
            align-items: center;
            margin-right: 20px;
        }

        .form-switch input {
            width: 40px;
            height: 22px;
            cursor: pointer;
        }

        .form-switch label {
            margin-left: 10px;
            font-weight: 500;
        }

        .form-switch-container {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }
    </style>
</head>

<body>
    <!-- Menu de navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Gestion des Utilisateurs</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <?php include 'menu.php'; ?>
            </div>
        </div>
    </nav>

    <!-- Contenu principal -->
    <div class="container">
        <h1>Modifier un Utilisateur</h1>
        <div class="card">
            <div class="card-header">
                <i class="fas fa-user-edit"></i> Formulaire de modification
            </div>
            <div class="card-body">
                <form action="modifier_utilisateur.php?id=<?php echo $userId; ?>" method="POST">
                    <div class="mb-3">
                        <label for="mail_pro" class="form-label">Email professionnel</label>
                        <input type="email" class="form-control" id="mail_pro" name="mail_pro" value="<?php echo $user['mail_pro']; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Mot de passe</label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Laissez vide pour ne pas modifier">
                    </div>
                    <div class="mb-3">
                        <label for="nom" class="form-label">Nom</label>
                        <input type="text" class="form-control" id="nom" name="nom" value="<?php echo $user['nom']; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="prenom" class="form-label">Prénom</label>
                        <input type="text" class="form-control" id="prenom" name="prenom" value="<?php echo $user['prenom']; ?>" required>
                    </div>

                    <!-- Interrupteurs pour les droits -->
                    <div class="mb-3">
                        <label class="form-label">Droits d'accès</label>
                        <div class="form-switch-container">
                            <div class="form-switch">
                                <input class="form-check-input" type="checkbox" id="modifier_devis" name="modifier_devis" <?php echo $user['modifier_devis'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="modifier_devis">Modifier les devis</label>
                            </div>
                            <div class="form-switch">
                                <input class="form-check-input" type="checkbox" id="visualiser_devis" name="visualiser_devis" <?php echo $user['visualiser_devis'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="visualiser_devis">Visualiser les devis</label>
                            </div>
                            <div class="form-switch">
                                <input class="form-check-input" type="checkbox" id="soumettre_devis" name="soumettre_devis" <?php echo $user['soumettre_devis'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="soumettre_devis">Soumettre les devis</label>
                            </div>
                            <div class="form-switch">
                                <input class="form-check-input" type="checkbox" id="masquer_devis" name="masquer_devis" <?php echo $user['masquer_devis'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="masquer_devis">Masquer les devis</label>
                            </div>
                            <div class="form-switch">
                                <input class="form-check-input" type="checkbox" id="envoyer_devis" name="envoyer_devis" <?php echo $user['envoyer_devis'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="envoyer_devis">Envoyer les devis</label>
                            </div>
                            <div class="form-switch">
                                <input class="form-check-input" type="checkbox" id="valider_devis" name="valider_devis" <?php echo $user['valider_devis'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="valider_devis">Valider les devis</label>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Mettre à jour</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Pied de page -->
    <div class="footer">
        <p>&copy; 2024 Gestion des Utilisateurs. Tous droits réservés.</p>
    </div>

    <!-- Scripts JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>