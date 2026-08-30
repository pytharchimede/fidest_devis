<?php
$currentPage = basename($_SERVER['PHP_SELF'] ?? 'dashboard.php');
$canManageAnnouncements = false;
$menuUser = [];
try {
    $permissionStatement = app_database()->prepare('SELECT id,nom,prenom,mail_pro,fonction,photo,gestion_utilisateur FROM user_devis WHERE id = :id AND active = 1');
    $permissionStatement->execute(['id' => (int) ($_SESSION['user_id'] ?? 0)]);
    $menuUser = $permissionStatement->fetch(PDO::FETCH_ASSOC) ?: [];
    $canManageAnnouncements = (bool) ($menuUser['gestion_utilisateur'] ?? false);
} catch (Throwable $exception) {
}
$menuItems = [
    ['dashboard.php', 'Accueil'],
    ['liste_offre.php', 'Appels d’Offre'],
    ['generer_devis.php', 'Nouveau devis'],
    ['liste_devis.php', 'Devis'],
    ['bons_commande.php', 'Commandes'],
    ['suivi_affaires.php', 'Suivi des affaires'],
    ['archives.php', 'Exercices & Archives'],
    ['liste_bl.php', 'BL signés'],
    ['liste_facture.php', 'Factures & Encaissements'],
    ['commandes_boutique.php', 'Commandes web'],
    ['messagerie_boutique.php', 'Messages web'],
    ['liste_client.php', 'Clients'],
    ['catalogue.php', 'Catalogue'],
    ['catalogue_media.php', 'Médias'],
    ['fiches_produits.php', 'Fiches produits'],
    ['boutique.php', 'Boutique'],
    ['liste_corbeille.php', 'Corbeille'],
    ['liste_utilisateur.php', 'Équipe'],
];
if ($canManageAnnouncements) {
    $menuItems[] = ['admin_notifications.php', 'Annonces'];
}
?>
<ul class="navbar-nav navbar-menu-items">
    <?php foreach ($menuItems as [$href, $label]): ?>
        <li class="nav-item">
            <a class="nav-link<?= $currentPage === $href ? ' active' : '' ?>" href="<?= htmlspecialchars($href) ?>"><?= htmlspecialchars($label) ?></a>
        </li>
    <?php endforeach; ?>
</ul>
<div class="navbar-fixed-actions"><div class="notification-center">
    <button class="notification-bell" type="button" aria-label="Notifications"><i class="fas fa-bell"></i><span class="notification-count">0</span></button>
    <div class="notification-panel">
        <div class="notification-head"><strong>Notifications</strong><span>Alertes FIDEST</span></div>
        <div class="notification-list"></div>
    </div>
</div><?php $menuUserName=trim((string)(($menuUser['prenom']??'').' '.($menuUser['nom']??'')));$menuUserPhoto=(string)($menuUser['photo']??'');if($menuUserPhoto==='')$menuUserPhoto='https://www.gravatar.com/avatar/'.md5(strtolower(trim((string)($menuUser['mail_pro']??'')))).'?d=mp&s=120';?><div class="connected-user"><button class="connected-user__trigger" type="button" aria-expanded="false" aria-label="Ouvrir le menu utilisateur"><img src="<?=htmlspecialchars($menuUserPhoto,ENT_QUOTES)?>" alt=""><span><strong><?=htmlspecialchars($menuUserName?:'Utilisateur')?></strong><small><?=htmlspecialchars((string)($menuUser['fonction']?:'Collaborateur FIDEST'))?></small></span><i class="fa-solid fa-chevron-down"></i></button><div class="connected-user__dropdown"><div class="connected-user__identity"><img src="<?=htmlspecialchars($menuUserPhoto,ENT_QUOTES)?>" alt=""><div><strong><?=htmlspecialchars($menuUserName?:'Utilisateur')?></strong><small><?=htmlspecialchars((string)($menuUser['mail_pro']??''))?></small></div></div><a href="profil.php"><i class="fa-regular fa-user"></i><span>Mon profil<small>Informations et sécurité</small></span></a><a href="commandes_boutique.php"><i class="fa-solid fa-cart-shopping"></i><span>Commandes web<small>Suivre les demandes</small></span></a><div class="connected-user__separator"></div><a class="connected-user__logout" href="deconnex.php"><i class="fa-solid fa-arrow-right-from-bracket"></i><span>Se déconnecter</span></a></div></div></div>
<link rel="stylesheet" href="css/navbar-pro.css">
<link rel="stylesheet" href="css/smart-select.css">
<link rel="stylesheet" href="css/modern-ui.css">
<link rel="stylesheet" href="css/data-density.css">
<link rel="stylesheet" href="css/notifications.css">
<link rel="stylesheet" href="css/first-use-guide.css">
<script src="js/notifications.js" defer></script>
<script src="js/first-use-guide.js" defer></script>
<script src="js/navbar-pro.js" defer></script>
<script src="js/smart-select.js" defer></script>
<script src="js/modern-ui.js" defer></script>
