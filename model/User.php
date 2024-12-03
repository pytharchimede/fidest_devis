<?php
// model/User.php
class User
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function findUserByEmail($mail_pro)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM user_devis WHERE mail_pro = :mail_pro AND active = 1 ");
        $stmt->execute(['mail_pro' => $mail_pro]);
        return $stmt->fetch();
    }

    public function verifyPassword($mail_pro, $password)
    {
        $user = $this->findUserByEmail($mail_pro);

        // Hachage du mot de passe en SHA-512 pour comparer avec la base de données
        $hashedPassword = hash('sha512', $password);

        // Comparaison du mot de passe haché avec celui stocké en base de données
        if ($user && $hashedPassword === $user['password']) {
            return $user;
        }
        return false;
    }


    // Vérifier les droits spécifiques de l'utilisateur
    public function hasPermission($user, $permission)
    {
        return isset($user[$permission]) && $user[$permission] == 1;
    }
}