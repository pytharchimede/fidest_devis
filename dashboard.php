<?php
include 'auth_check.php';
require_once __DIR__ . '/bootstrap.php';

$dashboard = new App\Domain\Dashboard\DashboardRepository(app_database());
$metrics = $dashboard->metrics();
$recentQuotes = $dashboard->recentQuotes();
$firstName = trim((string) ($_SESSION['prenom'] ?? ''));
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rédaction de Devis - BTP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f7f9;
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

        .menu-card {
            background: #ffffff;
            border-radius: 0.75rem;
            box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            text-align: center;
            padding: 1.5rem;
            margin: 1rem;
            position: relative;
        }

        .menu-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
        }

        .menu-card i {
            font-size: 3rem;
            color: #1d2b57;
        }

        .menu-card p {
            margin-top: 1rem;
            font-weight: bold;
            color: #333;
        }

        .footer {
            background-color: #1d2b57;
            color: #fff;
            padding: 1rem 0;
            position: relative;
        }

        .footer p {
            margin-bottom: 0;
        }

        .footer .social-icons a {
            color: #fff;
            font-size: 1.5rem;
            margin: 0 0.5rem;
            transition: color 0.3s ease;
        }

        .footer .social-icons a:hover {
            color: #ffc107;
        }

        .chart-container {
            margin: 2rem 0;
        }
    </style>
    <link rel="stylesheet" href="css/dashboard-pro.css">
</head>

<body class="dashboard-page">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <img src="img/logo_fidest.png" alt="Logo">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <?php include 'menu.php'; ?>
            </div>
        </div>
    </nav>

    <main class="container">
        <header class="dashboard-welcome">
            <div>
                <h1>Bonjour<?= $firstName !== '' ? ' ' . htmlspecialchars($firstName) : '' ?>, où en est votre activité ?</h1>
                <p>Les indicateurs essentiels, les documents à surveiller et les prochaines actions recommandées.</p>
            </div>
            <div class="dashboard-date"><?= (new DateTimeImmutable())->format('d / m / Y') ?></div>
        </header>

        <section class="dashboard-kpis" aria-label="Indicateurs clés">
            <a class="dashboard-kpi dashboard-kpi--primary" href="liste_devis.php" aria-label="Voir tous les devis"><div class="dashboard-kpi__head"><span class="dashboard-kpi__icon"><i data-lucide="wallet-cards"></i></span><span class="dashboard-kpi__trend">Global</span></div><div class="dashboard-kpi__value"><?= number_format((float)$metrics['revenue'], 0, ',', ' ') ?></div><div class="dashboard-kpi__label">Volume de devis · FCFA TTC</div></a>
            <a class="dashboard-kpi" href="liste_devis.php?period=current_month" aria-label="Voir les devis créés ce mois"><div class="dashboard-kpi__head"><span class="dashboard-kpi__icon"><i data-lucide="file-chart-column-increasing"></i></span><span class="dashboard-kpi__trend">Ce mois</span></div><div class="dashboard-kpi__value"><?= (int)$metrics['current_month'] ?></div><div class="dashboard-kpi__label">Nouveaux devis créés</div></a>
            <a class="dashboard-kpi" href="liste_client.php" aria-label="Voir les clients"><div class="dashboard-kpi__head"><span class="dashboard-kpi__icon"><i data-lucide="users-round"></i></span><span class="dashboard-kpi__trend">CRM</span></div><div class="dashboard-kpi__value"><?= (int)$metrics['clients'] ?></div><div class="dashboard-kpi__label">Clients enregistrés</div></a>
            <a class="dashboard-kpi" href="liste_devis.php?status=pending" aria-label="Voir les validations en attente"><div class="dashboard-kpi__head"><span class="dashboard-kpi__icon"><i data-lucide="scan-search"></i></span><span class="dashboard-kpi__trend">À traiter</span></div><div class="dashboard-kpi__value"><?= (int)$metrics['pending'] ?></div><div class="dashboard-kpi__label">Validations en attente</div></a>
        </section>

        <section class="dashboard-layout">
            <article class="dashboard-panel"><div class="dashboard-panel__head"><div><h2>Évolution de l’activité</h2><small class="text-muted">Nombre de devis émis par jour</small></div><a href="liste_devis.php">Voir tous les devis →</a></div><div id="chart" class="dashboard-chart"></div></article>
            <aside class="dashboard-panel"><div class="dashboard-panel__head"><div><h2>Suggestions intelligentes</h2><small class="text-muted">Priorités détectées aujourd’hui</small></div><i data-lucide="sparkles"></i></div><div class="dashboard-suggestions">
                <?php if ((int)$metrics['expired'] > 0): ?><a class="smart-suggestion smart-suggestion--accent" href="liste_devis.php?status=expired"><span class="smart-suggestion__icon"><i data-lucide="clock-alert"></i></span><span><strong><?= (int)$metrics['expired'] ?> devis expirés à relancer</strong><small>Recontactez les clients ou ajustez l’échéance.</small></span><i data-lucide="chevron-right"></i></a><?php endif; ?>
                <?php if ((int)$metrics['pending'] > 0): ?><a class="smart-suggestion" href="liste_devis.php?status=pending"><span class="smart-suggestion__icon"><i data-lucide="badge-check"></i></span><span><strong><?= (int)$metrics['pending'] ?> validations en attente</strong><small>Finalisez les documents prêts à partir.</small></span><i data-lucide="chevron-right"></i></a><?php endif; ?>
                <?php if ((int)$metrics['incomplete'] > 0): ?><a class="smart-suggestion" href="liste_devis.php?status=incomplete"><span class="smart-suggestion__icon"><i data-lucide="file-warning"></i></span><span><strong><?= (int)$metrics['incomplete'] ?> devis à compléter</strong><small>Conditions ou pied de page non renseignés.</small></span><i data-lucide="chevron-right"></i></a><?php endif; ?>
                <a class="smart-suggestion" href="generer_devis.php"><span class="smart-suggestion__icon"><i data-lucide="wand-sparkles"></i></span><span><strong>Créer une nouvelle proposition</strong><small>Utilisez l’atelier et son aperçu en direct.</small></span><i data-lucide="chevron-right"></i></a>
            </div></aside>
        </section>

        <section class="dashboard-bottom">
            <article class="dashboard-panel"><div class="dashboard-panel__head"><div><h2>Devis récents</h2><small class="text-muted">Les dernières propositions enregistrées</small></div><a href="liste_devis.php">Tout afficher →</a></div><div class="recent-quotes">
                <?php foreach ($recentQuotes as $quote): ?><a class="recent-quote" href="modifier_devis.php?devisId=<?= (int)$quote['id'] ?>"><span class="recent-quote__icon"><i data-lucide="file-text"></i></span><span><strong><?= htmlspecialchars($quote['numero_devis']) ?></strong><small><?= htmlspecialchars($quote['destine_a'] ?: 'Destinataire non renseigné') ?> · <?= htmlspecialchars($quote['date_emission']) ?></small></span><span class="recent-quote__amount"><?= number_format((float)$quote['total_ttc'],0,',',' ') ?><br><small>FCFA</small></span></a><?php endforeach; ?>
            </div></article>
            <article class="dashboard-panel"><div class="dashboard-panel__head"><div><h2>Accès rapides</h2><small class="text-muted">Gagnez du temps</small></div></div><div class="quick-actions"><a class="quick-action quick-action--accent" href="generer_devis.php"><i data-lucide="file-plus-2"></i><span>Nouveau devis</span></a><a class="quick-action" href="liste_client.php"><i data-lucide="contact-round"></i><span>Nouveau client</span></a><a class="quick-action" href="catalogue.php"><i data-lucide="library-big"></i><span>Catalogue</span></a><a class="quick-action" href="liste_bl.php"><i data-lucide="package-check"></i><span>Livraisons</span></a></div></article>
        </section>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container text-center">
            <p>&copy; <?php echo gmdate('Y'); ?> FIDEST. Tous droits réservés.</p>
            <div class="social-icons">
                <a href="#" class="fab fa-facebook-f"></a>
                <a href="#" class="fab fa-twitter"></a>
                <a href="#" class="fab fa-linkedin-in"></a>
            </div>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (window.lucide) lucide.createIcons({ strokeWidth: 1.8 });
            fetch('request/get_devis_data.php') // Remplacez par le chemin réel
                .then(response => response.json())
                .then(data => {
                    var options = {
                        chart: { type: 'area', height: 320, toolbar: { show: false }, fontFamily: 'DM Sans' },
                        series: [{
                            name: 'Devis Édités',
                            data: data.data
                        }],
                        xaxis: {
                            categories: data.labels
                        },
                        colors: [getComputedStyle(document.documentElement).getPropertyValue('--brand-accent').trim()],
                        stroke: {
                            curve: 'smooth', width: 3
                        },
                        fill: { type: 'gradient', gradient: { shadeIntensity: .2, opacityFrom: .32, opacityTo: .03, stops: [0, 100] } },
                        dataLabels: { enabled: false },
                        markers: { size: 0, hover: { size: 5 } },
                        grid: {
                            borderColor: getComputedStyle(document.documentElement).getPropertyValue('--brand-border').trim()
                        }
                    };

                    var chart = new ApexCharts(document.querySelector("#chart"), options);
                    chart.render();
                });
        });
    </script>

</body>

</html>
