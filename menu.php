<?php
$currentPage = basename($_SERVER['PHP_SELF'] ?? 'dashboard.php');
$menuItems = [
    ['dashboard.php', 'Accueil'], ['generer_devis.php', 'Nouveau devis'],
    ['liste_devis.php', 'Devis'], ['liste_bl.php', 'Livraisons'],
    ['liste_facture.php', 'Factures'], ['liste_client.php', 'Clients'],
    ['liste_offre.php', 'Offres'], ['catalogue.php', 'Catalogue'],
    ['catalogue_media.php', 'Médias'], ['boutique.php', 'Boutique'],
    ['liste_corbeille.php', 'Corbeille'], ['liste_utilisateur.php', 'Équipe'],
    ['profil.php', 'Profil'],
];
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
    <button class="notification-bell" type="button" aria-label="Notifications de facturation"><i class="fas fa-bell"></i><span class="notification-count">0</span></button>
    <div class="notification-panel"><div class="notification-head"><strong>Notifications</strong><a href="liste_facture.php">Voir la facturation</a></div><div class="notification-list"></div></div>
</div>
<link rel="stylesheet" href="css/notifications.css"><script src="js/notifications.js" defer></script>
