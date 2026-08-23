<?php
$currentPage = basename($_SERVER['PHP_SELF'] ?? 'dashboard.php');
$canManageAnnouncements = false;
try {
    $permissionStatement = app_database()->prepare('SELECT gestion_utilisateur FROM user_devis WHERE id = :id AND active = 1');
    $permissionStatement->execute(['id' => (int) ($_SESSION['user_id'] ?? 0)]);
    $canManageAnnouncements = (bool) $permissionStatement->fetchColumn();
} catch (Throwable $exception) {
}
$menuItems = [
    ['dashboard.php', 'Accueil'],
    ['generer_devis.php', 'Nouveau devis'],
    ['liste_devis.php', 'Devis'],
    ['liste_bl.php', 'Livraisons'],
    ['bons_commande.php', 'Commandes'],
    ['commandes_boutique.php', 'Commandes web'],
    ['liste_facture.php', 'Factures'],
    ['liste_client.php', 'Clients'],
    ['liste_offre.php', 'Offres'],
    ['catalogue.php', 'Catalogue'],
    ['catalogue_media.php', 'Médias'],
    ['boutique.php', 'Boutique'],
    ['liste_corbeille.php', 'Corbeille'],
    ['liste_utilisateur.php', 'Équipe'],
    ['profil.php', 'Profil'],
];
if ($canManageAnnouncements) {
    $menuItems[] = ['admin_notifications.php', 'Annonces'];
}
?>
<ul class="navbar-nav ms-auto">
    <?php foreach ($menuItems as [$href, $label]): ?>
        <li class="nav-item">
            <a class="nav-link<?= $currentPage === $href ? ' active' : '' ?>" href="<?= htmlspecialchars($href) ?>"><?= htmlspecialchars($label) ?></a>
        </li>
    <?php endforeach; ?>
    <li class="nav-item nav-logout">
        <a href="deconnex.php" class="btn btn-logout" title="Se déconnecter" aria-label="Se déconnecter">
            <i class="fas fa-arrow-right-from-bracket" aria-hidden="true"></i><span>Quitter</span>
        </a>
    </li>
</ul>
<div class="notification-center">
    <button class="notification-bell" type="button" aria-label="Notifications"><i class="fas fa-bell"></i><span class="notification-count">0</span></button>
    <div class="notification-panel">
        <div class="notification-head"><strong>Notifications</strong><span>Alertes FIDEST</span></div>
        <div class="notification-list"></div>
    </div>
</div>
<link rel="stylesheet" href="css/notifications.css">
<link rel="stylesheet" href="css/first-use-guide.css">
<script src="js/notifications.js" defer></script>
<script src="js/first-use-guide.js" defer></script>
