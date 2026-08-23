<?php

declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

function shopRedirectError(string $message): never
{
    $_SESSION['public_shop_error'] = $message;
    header('Location: ../boutique_publique.php#commande');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') shopRedirectError('Requête non autorisée.');
if (!hash_equals((string) ($_SESSION['public_shop_csrf'] ?? ''), (string) ($_POST['csrf_token'] ?? ''))) shopRedirectError('Votre session a expiré. Veuillez recommencer.');
if (trim((string) ($_POST['website'] ?? '')) !== '') shopRedirectError('La demande n’a pas pu être envoyée.');

$name = trim((string) ($_POST['nom_contact'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$phone = trim((string) ($_POST['telephone'] ?? ''));
$address = trim((string) ($_POST['adresse_livraison'] ?? ''));
$commune = trim((string) ($_POST['commune'] ?? ''));
$city = trim((string) ($_POST['ville'] ?? ''));
if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $phone === '' || $address === '' || $commune === '' || $city === '') {
    shopRedirectError('Veuillez renseigner correctement vos coordonnées et le lieu de livraison.');
}

$cart = json_decode((string) ($_POST['cart'] ?? ''), true);
if (!is_array($cart) || !$cart) shopRedirectError('Votre panier est vide.');
$quantities = [];
foreach ($cart as $line) {
    $productId = (int) ($line['id'] ?? 0);
    $quantity = min(999, max(0, (int) ($line['quantity'] ?? 0)));
    if ($productId > 0 && $quantity > 0) $quantities[$productId] = ($quantities[$productId] ?? 0) + $quantity;
}
if (!$quantities) shopRedirectError('Votre panier est vide.');

$pdo = app_database();
$placeholders = implode(',', array_fill(0, count($quantities), '?'));
$statement = $pdo->prepare("SELECT id_produit,designation,COALESCE(NULLIF(dernier_prix,0),prix_moyen,0) prix FROM produit WHERE id_produit IN ($placeholders)");
$statement->execute(array_keys($quantities));
$products = $statement->fetchAll(PDO::FETCH_ASSOC);
if (count($products) !== count($quantities)) shopRedirectError('Un produit du panier n’est plus disponible.');

try {
    $pdo->beginTransaction();
    $temporaryReference = 'WEB-TEMP-' . bin2hex(random_bytes(8));
    $total = 0.0;
    foreach ($products as $product) $total += (float) $product['prix'] * $quantities[(int) $product['id_produit']];
    $orderStatement = $pdo->prepare('INSERT INTO commandes_boutique(reference_commande,nom_contact,entreprise,email,telephone,adresse_livraison,commune,ville,indications,total_estime) VALUES(:reference,:nom,:entreprise,:email,:telephone,:adresse,:commune,:ville,:indications,:total)');
    $orderStatement->execute(['reference' => $temporaryReference, 'nom' => $name, 'entreprise' => trim((string) ($_POST['entreprise'] ?? '')) ?: null, 'email' => $email, 'telephone' => $phone, 'adresse' => $address, 'commune' => $commune, 'ville' => $city, 'indications' => trim((string) ($_POST['indications'] ?? '')) ?: null, 'total' => $total]);
    $orderId = (int) $pdo->lastInsertId();
    $reference = sprintf('FID-WEB-%s-%05d', date('Y'), $orderId);
    $pdo->prepare('UPDATE commandes_boutique SET reference_commande=? WHERE id=?')->execute([$reference, $orderId]);
    $lineStatement = $pdo->prepare('INSERT INTO commande_boutique_ligne(commande_id,produit_id,designation,quantite,prix_unitaire,total_ligne) VALUES(?,?,?,?,?,?)');
    foreach ($products as $product) {
        $quantity = $quantities[(int) $product['id_produit']];
        $price = (float) $product['prix'];
        $lineStatement->execute([$orderId, $product['id_produit'], $product['designation'], $quantity, $price, $price * $quantity]);
    }
    $pdo->commit();
    $_SESSION['public_shop_success'] = $reference;
    $_SESSION['public_shop_csrf'] = bin2hex(random_bytes(24));
    header('Location: ../boutique_publique.php?commande=envoyee');
    exit;
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    shopRedirectError('Votre demande n’a pas pu être enregistrée. Veuillez réessayer.');
}
