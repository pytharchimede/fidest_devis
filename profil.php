<?php
// Inclure les fichiers nécessaires
require_once 'model/Database.php';
require_once 'model/User.php';

$pdo = Database::getConnection();
$userModel = new User($pdo);

// Vérifier si l'utilisateur est connecté et récupérer ses données
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Récupérer les données de l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM user_devis WHERE id = :id");
$stmt->execute(['id' => $userId]);
$user = $stmt->fetch();

if (!$user) {
    echo "Utilisateur non trouvé.";
    exit;
}

// Traitement du formulaire de mise à jour du profil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $password = $_POST['password'];
    $hashedPassword = !empty($password) ? hash("sha512", $password) : $user['password'];

    // Traitement de la photo de profil
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $photo = $_FILES['photo'];
        $targetDir = "photo/";
        $targetFile = $targetDir . basename($photo['name']);
        move_uploaded_file($photo['tmp_name'], $targetFile);
    } else {
        $targetFile = $user['photo'] ?: ''; // Conserver la photo existante si aucune nouvelle photo n'est envoyée
    }

    // Mise à jour des données de l'utilisateur
    $stmt = $pdo->prepare("UPDATE user_devis SET nom = :nom, prenom = :prenom, password = :password, photo = :photo WHERE id = :id");
    $stmt->execute([
        'nom' => $nom,
        'prenom' => $prenom,
        'password' => $hashedPassword,
        'photo' => $targetFile,
        'id' => $userId
    ]);

    // Redirection après modification
    header('Location: profil.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gérer mon Profil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }

        .navbar {
            background-color: #1d2b57;
        }

        .navbar-brand img {
            height: 50px;
        }

        .navbar-nav .nav-link {
            color: #fff !important;
        }

        .navbar-nav .nav-link.active {
            color: #fabd02 !important;
        }

        .container {
            margin-top: 50px;
        }

        .card {
            border-radius: 10px;
        }

        .card-header {
            background-color: #1d2b57;
            color: white;
            font-size: 20px;
            font-weight: bold;
        }

        .form-label {
            color: #1d2b57;
        }

        .form-control {
            border-radius: 10px;
            margin-bottom: 10px;
        }

        .form-control-file {
            padding: 5px;
        }

        .btn-primary {
            background-color: #fabd02;
            border-color: #fabd02;
        }

        .btn-primary:hover {
            background-color: #e0a800;
            border-color: #e0a800;
        }

        .photo-profile img {
            border-radius: 50%;
            width: 150px;
            height: 150px;
            object-fit: cover;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .photo-preview {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .footer {
            background-color: #1d2b57;
            color: white;
            text-align: center;
            padding: 20px;
            position: fixed;
            width: 100%;
            bottom: 0;
        }
    </style>
</head>

<body>
    <!-- Menu de navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Mon Profil</a>
            <div class="collapse navbar-collapse" id="navbarNav">
                <?php include 'menu.php'; ?>
            </div>
        </div>
    </nav>

    <!-- Contenu principal -->
    <div class="container">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-user-circle"></i> Gérer mon Profil
            </div>
            <div class="card-body">
                <form action="profil.php" method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-4 text-center photo-preview">
                            <div class="photo-profile">
                                <!-- Affiche la photo actuelle ou Gravatar si pas de photo -->
                                <img id="previewImage" src="<?php echo $user['photo'] ?: 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($user['mail_pro']))) . '?d=mm&s=200'; ?>" alt="Photo de profil">
                            </div>
                            <div class="mt-3">
                                <input type="file" class="form-control-file" name="photo" accept="image/*" onchange="previewImage(event)">
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="nom" class="form-label">Nom</label>
                                <input type="text" class="form-control" id="nom" name="nom" value="<?php echo htmlspecialchars($user['nom']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="prenom" class="form-label">Prénom</label>
                                <input type="text" class="form-control" id="prenom" name="prenom" value="<?php echo htmlspecialchars($user['prenom']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Mot de passe</label>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Laissez vide pour ne pas modifier">
                            </div>
                            <button type="submit" class="btn btn-primary">Mettre à jour</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Pied de page -->
    <div class="footer">
        <p>&copy; 2024 Mon Application. Tous droits réservés.</p>
    </div>

    <!-- Scripts JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Aperçu de l'image sélectionnée
        function previewImage(event) {
            const preview = document.getElementById('previewImage');
            preview.src = URL.createObjectURL(event.target.files[0]);
            preview.onload = () => URL.revokeObjectURL(preview.src); // Libérer la mémoire
        }
    </script>
</body>

</html>