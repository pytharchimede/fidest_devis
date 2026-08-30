<?php
include 'auth_check.php';
require_once __DIR__ . '/bootstrap.php';

$dashboardController=new App\Http\Controller\DashboardController(new App\Application\Analytics\GetDashboardData(new App\Domain\Dashboard\DashboardRepository(app_database()),new App\Domain\Analytics\AnalyticsRepository(app_database()),__DIR__.'/data/bl_registry.json'));
extract($dashboardController->index($_GET),EXTR_SKIP);
$view=new App\Infrastructure\View\ViewRenderer(__DIR__.'/views');
$firstName = trim((string) ($_SESSION['prenom'] ?? ''));
$money=static fn(float $value):string=>number_format($value,0,',',' ').' FCFA';
$analyticsCards=[
 ['color'=>'#4169e1','icon'=>'fa-briefcase','title'=>'AO participés','quantity'=>(int)$analytics['participated_count'],'unit'=>'appels d’offre','secondary_label'=>'Montant','secondary'=>$money((float)$analytics['participated_amount']),'footer'=>'Volume total des participations'],
 ['color'=>'#15935b','icon'=>'fa-trophy','title'=>'AO remportés','quantity'=>(int)$analytics['won_count'],'unit'=>'appels d’offre','secondary_label'=>'Montant gagné','secondary'=>$money((float)$analytics['won_amount']),'footer'=>((int)$analytics['participated_count']?number_format(100*(int)$analytics['won_count']/(int)$analytics['participated_count'],1,',',' '):0).' % de réussite'],
 ['color'=>'#9b59b6','icon'=>'fa-file-signature','title'=>'Commandes reçues','quantity'=>(int)$analytics['order_count'],'unit'=>'commandes','secondary_label'=>'Montant','secondary'=>$money((float)$analytics['order_amount']),'footer'=>(int)$analytics['cancelled_count'].' annulée(s)'],
 ['color'=>'#00a5a8','icon'=>'fa-truck-fast','title'=>'Livraisons','quantity'=>(int)$analytics['delivered_count'],'unit'=>'livraisons','secondary_label'=>'Montant livré','secondary'=>$money((float)$analytics['delivered_amount']),'footer'=>'BL signés intégrés'],
 ['color'=>'#d94254','icon'=>'fa-triangle-exclamation','title'=>'Non honorées','quantity'=>(int)$analytics['unfulfilled_count'],'unit'=>'commandes','secondary_label'=>'Montant exposé','secondary'=>$money((float)$analytics['unfulfilled_amount']),'footer'=>'Échéance dépassée sans BL signé'],
 ['color'=>'#e08a00','icon'=>'fa-clock','title'=>'Retards','quantity'=>(int)$analytics['late_count'],'unit'=>'livraisons','secondary_label'=>'Taux de retard','secondary'=>number_format((float)$analytics['late_rate'],1,',',' ').' %','footer'=>number_format((float)$analytics['on_time_rate'],1,',',' ').' % livrées à temps'],
 ['color'=>'#c23b4a','icon'=>'fa-money-bill-transfer','title'=>'Dépenses réelles','primary'=>$money((float)$analytics['cash_out']),'footer'=>'Décaissements enregistrés'],
 ['color'=>(float)$analytics['profit']>=0?'#15935b':'#c23b4a','icon'=>'fa-chart-line','title'=>'Bénéfice de trésorerie','primary'=>$money((float)$analytics['profit']),'footer'=>'Encaissements − décaissements'],
];
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
    .analytics-kpi__quantity{display:flex;align-items:flex-end;gap:8px;margin:13px 0}.analytics-kpi__quantity strong{margin:0;color:var(--brand-primary);font:900 2.2rem/.9 var(--brand-font-heading)}.analytics-kpi__quantity span{padding-bottom:2px;color:#858899;font-size:.65rem;font-weight:850;text-transform:uppercase}.analytics-kpi__amount{display:flex;align-items:center;justify-content:space-between;gap:8px;padding:9px 11px;color:var(--kpi-color,var(--brand-primary));background:color-mix(in srgb,var(--kpi-color,var(--brand-primary)) 9%,white);border-radius:10px}.analytics-kpi__amount span{font-size:.59rem;font-weight:900;letter-spacing:.06em;text-transform:uppercase}.analytics-kpi__amount strong{margin:0;color:inherit;font-size:.86rem;text-align:right}.analytics-kpi>small{display:block;margin-top:8px}
    </style>
    <link rel="stylesheet" href="css/dashboard-pro.css">
    <style>
    .analytics-title{display:flex;align-items:end;justify-content:space-between;gap:20px;margin:30px 0 14px}.analytics-title h2{margin:0;color:var(--brand-primary);font:850 1.35rem var(--brand-font-heading)}.analytics-title p{margin:5px 0 0;color:var(--brand-text-muted)}.analytics-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:14px}.analytics-kpi{position:relative;overflow:hidden;padding:18px;background:#fff;border:1px solid var(--brand-border);border-radius:17px;box-shadow:0 9px 25px #22254b0d}.analytics-kpi:before{position:absolute;inset:0 auto 0 0;width:4px;content:"";background:var(--kpi-color,var(--brand-accent))}.analytics-kpi__head{display:flex;align-items:center;gap:9px;color:#777a8d;font-size:.67rem;font-weight:850;text-transform:uppercase}.analytics-kpi__icon{display:grid;width:34px;height:34px;place-items:center;color:var(--kpi-color,var(--brand-primary));background:color-mix(in srgb,var(--kpi-color,var(--brand-primary)) 12%,white);border-radius:10px;font-size:.92rem}.analytics-kpi strong{display:block;margin:10px 0 2px;color:var(--brand-primary);font-size:1.35rem}.analytics-kpi small{color:#858899}.analytics-charts{display:grid;grid-template-columns:1.45fr .75fr;gap:18px;margin-top:18px}.analytics-chart-card{padding:21px;background:#fff;border:1px solid var(--brand-border);border-radius:19px;box-shadow:0 10px 30px #22254b0d}.analytics-chart-card h3{margin:0;color:var(--brand-primary);font:850 1rem var(--brand-font-heading)}.analytics-chart-card p{margin:4px 0 12px;color:var(--brand-text-muted);font-size:.75rem}.analytics-finance{grid-column:1/-1;margin-bottom:24px}@media(max-width:1050px){.analytics-kpis{grid-template-columns:1fr 1fr}.analytics-charts{grid-template-columns:1fr}.analytics-finance{grid-column:auto}}@media(max-width:580px){.analytics-kpis{grid-template-columns:1fr}}
    </style>
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
            <a class="dashboard-kpi dashboard-kpi--primary" href="suivi_affaires.php" aria-label="Voir le suivi financier réel"><div class="dashboard-kpi__head"><span class="dashboard-kpi__icon"><i data-lucide="wallet-cards"></i></span><span class="dashboard-kpi__trend">Réel</span></div><div class="dashboard-kpi__value"><?= number_format((float)$metrics['real_balance'], 0, ',', ' ') ?></div><div class="dashboard-kpi__label">Solde réel · entrées − sorties · FCFA</div></a>
            <a class="dashboard-kpi" href="liste_devis.php?period=current_month" aria-label="Voir les devis créés ce mois"><div class="dashboard-kpi__head"><span class="dashboard-kpi__icon"><i data-lucide="file-chart-column-increasing"></i></span><span class="dashboard-kpi__trend">Ce mois</span></div><div class="dashboard-kpi__value"><?= (int)$metrics['current_month'] ?></div><div class="dashboard-kpi__label">Nouveaux devis créés</div></a>
            <a class="dashboard-kpi" href="liste_client.php" aria-label="Voir les clients"><div class="dashboard-kpi__head"><span class="dashboard-kpi__icon"><i data-lucide="users-round"></i></span><span class="dashboard-kpi__trend">CRM</span></div><div class="dashboard-kpi__value"><?= (int)$metrics['clients'] ?></div><div class="dashboard-kpi__label">Clients enregistrés</div></a>
            <a class="dashboard-kpi" href="liste_devis.php?status=pending" aria-label="Voir les validations en attente"><div class="dashboard-kpi__head"><span class="dashboard-kpi__icon"><i data-lucide="scan-search"></i></span><span class="dashboard-kpi__trend">À traiter</span></div><div class="dashboard-kpi__value"><?= (int)$metrics['pending'] ?></div><div class="dashboard-kpi__label">Validations en attente</div></a>
        </section>

        <section aria-label="Tableau de bord analytique"><div class="analytics-title"><div><h2>Performance commerciale et opérationnelle</h2><p>Quantités, montants et qualité d’exécution sur l’exercice actif.</p></div><a class="btn btn-sm btn-outline-primary" href="archives.php"><i class="fa-solid fa-clock-rotate-left"></i> Exercices précédents</a></div><div class="analytics-rangebar"><div class="analytics-range"><small>Période analysée</small><strong><?=htmlspecialchars($analyticsRange['label'])?></strong></div><nav class="period-pills" aria-label="Choisir la période"><?php foreach(['day'=>'Jour','week'=>'Semaine','month'=>'Mois','quarter'=>'Trimestre','semester'=>'Semestre','year'=>'Année'] as $period=>$label):?><a class="<?= $analyticsRange['period']===$period?'active':'' ?>" href="dashboard.php?analytics_period=<?=$period?>"><?=$label?></a><?php endforeach;?><button class="<?=$analyticsRange['period']==='custom'?'active':''?>" type="button" id="customPeriodButton">Libre</button></nav><form class="custom-period" id="customPeriodForm" method="get"><input type="hidden" name="analytics_period" value="custom"><label>Début<input type="date" name="analytics_start" value="<?=htmlspecialchars($analyticsRange['start'])?>" required></label><label>Fin<input type="date" name="analytics_end" value="<?=htmlspecialchars($analyticsRange['end'])?>" required></label><button class="btn btn-sm btn-primary"><i class="fa-solid fa-check"></i></button></form></div><div class="analytics-kpis">
            <?php foreach($analyticsCards as $kpi) echo $view->render('components/analytics-kpi',['kpi'=>$kpi]); ?>
        </div><div class="analytics-charts"><article class="analytics-chart-card"><h3>Entonnoir commercial en quantité</h3><p>AO participés, remportés, commandes reçues et livraisons.</p><div id="performanceChart"></div></article><article class="analytics-chart-card"><h3>Ponctualité des livraisons</h3><p>Comparaison BL signé / date prévue.</p><div id="punctualityChart"></div></article><article class="analytics-chart-card analytics-finance"><h3>Évolution mensuelle des montants</h3><p>Participations, gains, commandes et livraisons sur les douze derniers mois.</p><div id="financialChart"></div></article></div></section>

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
            const customPeriodButton=document.getElementById('customPeriodButton'),customPeriodForm=document.getElementById('customPeriodForm');
            const closeCustomPeriod=()=>customPeriodForm?.classList.remove('open');
            closeCustomPeriod();
            window.addEventListener('pageshow',closeCustomPeriod);
            document.querySelectorAll('.period-pills a').forEach(link=>link.addEventListener('click',closeCustomPeriod));
            customPeriodButton?.addEventListener('click',event=>{event.stopPropagation();customPeriodForm.classList.toggle('open');if(customPeriodForm.classList.contains('open'))customPeriodForm.querySelector('input[type=date]')?.focus()});
            customPeriodForm?.addEventListener('focusout',()=>setTimeout(()=>{if(!customPeriodForm.contains(document.activeElement))customPeriodForm.classList.remove('open')},80));
            document.addEventListener('click',event=>{if(!customPeriodForm?.contains(event.target)&&event.target!==customPeriodButton)closeCustomPeriod()});
            const analyticsMonthly = <?=json_encode($monthlyAnalytics,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)?>;
            new ApexCharts(document.querySelector('#performanceChart'), {chart:{type:'bar',height:300,toolbar:{show:false},fontFamily:'DM Sans'},series:[{name:'Quantité',data:[<?=(int)$analytics['participated_count']?>,<?=(int)$analytics['won_count']?>,<?=(int)$analytics['order_count']?>,<?=(int)$analytics['delivered_count']?>]}],xaxis:{categories:['AO participés','AO remportés','Commandes','Livraisons']},colors:['#22254b'],plotOptions:{bar:{borderRadius:7,columnWidth:'48%',distributed:true}},dataLabels:{enabled:true},legend:{show:false},grid:{borderColor:'#ececf2'}}).render();
            new ApexCharts(document.querySelector('#punctualityChart'), {chart:{type:'donut',height:300,fontFamily:'DM Sans'},series:[<?=(int)$analytics['on_time_count']?>,<?=(int)$analytics['late_count']?>,<?=(int)$analytics['unfulfilled_count']?>],labels:['À temps','En retard','Non honorées'],colors:['#15935b','#e08a00','#d94254'],stroke:{width:4,colors:['#fff']},legend:{position:'bottom'},dataLabels:{enabled:true},noData:{text:'Aucune livraison analysable'}}).render();
            new ApexCharts(document.querySelector('#financialChart'), {chart:{type:'area',height:350,toolbar:{show:false},fontFamily:'DM Sans'},series:[{name:'AO participés',data:analyticsMonthly.ao_amount},{name:'AO remportés',data:analyticsMonthly.won_amount},{name:'Commandes',data:analyticsMonthly.orders_amount},{name:'Livraisons',data:analyticsMonthly.delivered_amount},{name:'Encaissements',data:analyticsMonthly.cash_in},{name:'Décaissements',data:analyticsMonthly.cash_out}],xaxis:{categories:analyticsMonthly.labels},yaxis:{labels:{formatter:v=>new Intl.NumberFormat('fr-FR',{notation:'compact',maximumFractionDigits:1}).format(v)+' F'}},colors:['#4169e1','#15935b','#9b59b6','#00a5a8','#19a974','#d94254'],stroke:{curve:'smooth',width:2.5},fill:{type:'gradient',gradient:{opacityFrom:.24,opacityTo:.02}},dataLabels:{enabled:false},legend:{position:'top'},grid:{borderColor:'#ececf2'},tooltip:{y:{formatter:v=>new Intl.NumberFormat('fr-FR').format(v)+' FCFA'}}}).render();
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
