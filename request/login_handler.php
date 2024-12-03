<?php
// login_handler.php
session_start();
require_once '../model/Database.php';
require_once '../model/User.php';

$response = ['status' => 'error', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mail_pro = $_POST['mail_pro'];
    $password = $_POST['password'];

    // Obtenir la connexion PDO depuis la classe Database
    $pdo = Database::getConnection();

    $userModel = new User($pdo);
    $user = $userModel->verifyPassword($mail_pro, $password);

    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $response['status'] = 'success';
        $response['message'] = 'Connexion réussie.';
    } else {
        $response['message'] = 'Identifiants incorrects.';
    }
}

echo json_encode($response);
