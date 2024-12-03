<?php
// model/Devis.php
class Devis
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function validerCommerciale($devisId)
    {
        // Préparer la requête pour mettre à jour le champ `validation_commerciale` à 1
        $sql = "UPDATE devis SET validation_commerciale = 1 WHERE id = :devisId";

        // Préparer l'exécution de la requête
        $stmt = $this->pdo->prepare($sql);

        // Lier l'ID du devis à la requête
        $stmt->bindParam(':devisId', $devisId, PDO::PARAM_INT);

        // Exécuter la requête
        if ($stmt->execute()) {
            // Si la mise à jour est réussie, retourner true
            return true;
        } else {
            // Si une erreur survient, retourner false
            return false;
        }
    }

    public function validerGenerale($devisId)
    {
        // Préparer la requête pour mettre à jour le champ `validation_commerciale` à 1
        $sql = "UPDATE devis SET validation_generale = 1 WHERE id = :devisId";

        // Préparer l'exécution de la requête
        $stmt = $this->pdo->prepare($sql);

        // Lier l'ID du devis à la requête
        $stmt->bindParam(':devisId', $devisId, PDO::PARAM_INT);

        // Exécuter la requête
        if ($stmt->execute()) {
            // Si la mise à jour est réussie, retourner true
            return true;
        } else {
            // Si une erreur survient, retourner false
            return false;
        }
    }

    // Méthode pour vérifier si la validation commerciale a été effectuée
    public function isValidCommercial($devisId)
    {
        $sql = "SELECT validation_commerciale FROM devis WHERE id = :devisId";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':devisId', $devisId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['validation_commerciale'] == 1;
    }

    // Méthode pour vérifier si la validation générale a été effectuée
    public function isValidGenerale($devisId)
    {
        $sql = "SELECT validation_generale FROM devis WHERE id = :devisId";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':devisId', $devisId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['validation_generale'] == 1;
    }
}
