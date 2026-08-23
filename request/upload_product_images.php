<?php

declare(strict_types=1);
session_start();
require_once __DIR__ . '/../model/Database.php';

$db = new Database();
$pdo = $db->getConnection();

// dossiers
$uploadDir = dirname(__DIR__) . '/photo/produits';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0775, true);
}
if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
    jsonRedirect('../catalogue_media.php?err=upload_dir');
}

function jsonRedirect(string $url)
{
    header('Location: ' . $url);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonRedirect('../catalogue_media.php');
}

$produitId = isset($_POST['produit_id']) ? (int)$_POST['produit_id'] : 0;
if ($produitId <= 0) {
    jsonRedirect('../catalogue_media.php?err=produit');
}

if (!isset($_FILES['images'])) {
    jsonRedirect('../catalogue_media.php?err=files');
}

$allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
$fileInfo = new finfo(FILEINFO_MIME_TYPE);
$uploaded = 0;
$maxSize = 5 * 1024 * 1024; // 5MB

$count = count($_FILES['images']['name']);
for ($i = 0; $i < $count; $i++) {
    $name = $_FILES['images']['name'][$i] ?? '';
    $tmp  = $_FILES['images']['tmp_name'][$i] ?? '';
    $size = $_FILES['images']['size'][$i] ?? 0;
    $err  = $_FILES['images']['error'][$i] ?? UPLOAD_ERR_NO_FILE;

    if ($err !== UPLOAD_ERR_OK) {
        continue;
    }
    $type = $fileInfo->file($tmp);
    if (!isset($allowed[$type])) {
        continue;
    }
    if ($size > $maxSize) {
        continue;
    }

    $ext = $allowed[$type];
    $safeBase = preg_replace('/[^a-zA-Z0-9-_]/', '_', pathinfo($name, PATHINFO_FILENAME));
    $filename = sprintf('%d_%s_%s.%s', $produitId, $safeBase, uniqid('', true), $ext);
    $dest = $uploadDir . '/' . $filename;

    if (move_uploaded_file($tmp, $dest)) {
        $stmt = $pdo->prepare('INSERT INTO produit_image(produit_id, filename) VALUES(:pid, :fn)');
        $stmt->execute([':pid' => $produitId, ':fn' => $filename]);
        $uploaded++;
    }
}

jsonRedirect('../catalogue_media.php?' . ($uploaded > 0 ? 'uploaded=' . $uploaded : 'err=upload'));
