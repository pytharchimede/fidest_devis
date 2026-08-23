<?php



if (session_status() !== PHP_SESSION_ACTIVE) session_start();

function pdf_text($s)
{
    if ($s === null) {
        return '';
    }
    $s = (string)$s;
    // FPDF attend typiquement du Windows-1252/ISO-8859-1 (pas UTF-8)
    if (!function_exists('iconv')) {
        return $s;
    }
    $converted = @iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $s);
    return ($converted === false) ? $s : $converted;
}

function public_url(string $file, array $params = []): string
{
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $scheme = $isHttps ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $dir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
    if (preg_match('#/request$#', $dir)) {
        $dir = rtrim(str_replace('\\', '/', dirname($dir)), '/');
    }
    $basePath = ($dir === '' || $dir === '.') ? '' : $dir;

    $qs = $params ? ('?' . http_build_query($params)) : '';
    return $scheme . '://' . $host . $basePath . '/' . ltrim($file, '/') . $qs;
}

if (!defined('FPDF_FONTPATH')) {
    define('FPDF_FONTPATH', __DIR__ . '/../fpdf186/font/');
}

require_once __DIR__ . '/../fpdf186/fpdf.php';
require_once __DIR__ . '/../phpqrcode/qrlib.php';
require_once __DIR__ . '/../model/User.php';
require_once __DIR__ . '/../model/Database.php';
require_once __DIR__ . '/../model/Devis.php';
require_once __DIR__ . '/../bootstrap.php';





$pdo = \Database::getConnection();
$con = $pdo;

$userObj = new User($pdo);

$devisObj = new Devis($pdo);



$directeurCommercial = $userObj->findDirecteurCommercial();

$directeurGeneral = $userObj->findDirecteurGeneral();



// Vérifiez que le devisId est défini dans la session ou dans l'URL
if (!isset($_SESSION['devisId']) && !isset($_GET['devisId'])) {
    // En mode public (QR scan), on s'attend à recevoir devisId en GET
    http_response_code(400);
    echo 'ID de devis non défini.';
    exit;
}



// Prioriser l'ID du devis reçu via $_GET

if (isset($_GET['devisId'])) {

    $devisId = $_GET['devisId'];

    $_SESSION['devisId'] = $devisId; // Mettre à jour la session avec le nouvel ID

} else {

    $devisId = $_SESSION['devisId'];
}



// Déboguer pour vérifier la valeur de $devisId
// var_dump($devisId); // DEBUG uniquement



// Récupérer les données du devis depuis la base de données

$stmt = $con->prepare("SELECT * FROM devis WHERE id = ?");

$stmt->execute([$devisId]);

$devis = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$devis) {
    http_response_code(404);
    echo 'Devis non trouvé.';
    exit;
}

// Mode public: autoriser uniquement les devis publiés
$isAuthenticated = isset($_SESSION['user_id']);
if (!$isAuthenticated) {
    $isPublished = isset($devis['publier_devis']) && (int)$devis['publier_devis'] === 1;
    $isMasked = isset($devis['masque']) && (int)$devis['masque'] === 1;

    if (!$isPublished || $isMasked) {
        http_response_code(403);
        echo 'Devis non publié.';
        exit;
    }
}



// Récupérer les lignes du devis

$stmt = $con->prepare("SELECT * FROM ligne_devis WHERE devis_id = ?");

$stmt->execute([$devisId]);

$lignes = $stmt->fetchAll(PDO::FETCH_ASSOC);





// Récupérer le client

$stmt  = $con->prepare("SELECT * FROM client WHERE id_client =:A ");

$stmt->execute(array('A' => $devis['client_id']));

$client = $stmt->fetch();





# Récupérer l'offre

$stmt  = $con->prepare("SELECT * FROM offre WHERE id_offre =:A ");

$stmt->execute(array('A' => $devis['offre_id']));

$offre = $stmt->fetch();



if (!$client) {

    die('Client non trouvé.');
}



if (!$offre) {

    die('Offre non trouvée.');
}





// Vérifier si des lignes ont été trouvées

if (!$lignes) {

    $lignes = []; // Si aucune ligne n'est trouvée, définissez $lignes comme un tableau vide

}



if (!$devis) {

    die('Devis non trouvé.');
}







// Créez une classe dérivée de FPDF

class PDF extends FPDF

{



    // Méthode pour l'en-tête

    function Header()

    {

        $this->SetFont('Arial', 'B', 12);

        $this->Cell(0, 10, '', 0, 1, 'C');

        $this->Ln(10);

        // Logo Veritas (chemins alternatifs) – utiliser un chemin absolu pour éviter les soucis de répertoire courant
        $candidates = [
            __DIR__ . '/../logo/logo_veritas.jpg',
            __DIR__ . '/../img/logo_veritas.jpg',
            __DIR__ . '/../../img/logo_veritas.jpg',
        ];
        foreach ($candidates as $absPath) {
            if (file_exists($absPath)) {
                $this->Image($absPath, 150, 10, 30);
                break;
            }
        }
    }



    // Méthode pour le pied de page

    function Footer()

    {



        // Dessiner une ligne grise

        $this->SetDrawColor(0, 0, 0); // Couleur de la ligne grise

        // Insérer un logo d'entête si disponible (chemins alternatifs)
        $candidates = [
            __DIR__ . '/../logo/logo_veritas.jpg',
            __DIR__ . '/../../img/logo_veritas.jpg',
        ];
        foreach ($candidates as $absPath) {
            if (file_exists($absPath)) {
                // Utiliser le chemin absolu pour éviter les erreurs de répertoire courant
                $this->Image($absPath, 150, 10, 30);
                break;
            }
        }




        // Position at 1.5 cm from bottom

        $this->SetY(-22);



        //    $this->Image('../../img/logo_veritas.jpg', 10,275,30);



        // Arial italic 8

        $this->SetFont('Arial', '', 7);



        $company = app_branding()->get('company', []);
        $this->Cell(0, 3.5, pdf_text(($company['activity'] ?? '') . ' - Au capital de ' . ($company['capital'] ?? '') . ' - Siège social : ' . ($company['address'] ?? '')), 0, 1, 'C');
        $this->Cell(0, 3.5, pdf_text(($company['postal_box'] ?? '') . ' - Téléphone : ' . ($company['phone'] ?? '') . ' - Email : ' . ($company['email'] ?? '') . ' - RCCM : ' . ($company['rccm'] ?? '') . ' - N° CC : ' . ($company['tax_account'] ?? '')), 0, 1, 'C');



        //	$this->Image('logo.jpg', 172,275,30);



        // Page number

        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    // Permet de supprimer une page et de renuméroter les pages restantes
    function removePage($pageNo)
    {
        // Dans FPDF, $this->pages est 1-based et $this->page contient le nombre courant de pages
        if (!isset($this->pages) || $this->page < 2) {
            return; // Rien à faire si une seule page
        }
        $pageNo = (int)$pageNo;
        if ($pageNo < 1 || $pageNo > $this->page) {
            return; // Numéro de page invalide
        }

        $newPages = [];
        $newIndex = 1;
        for ($i = 1; $i <= $this->page; $i++) {
            if ($i === $pageNo) continue; // sauter la page à supprimer
            $newPages[$newIndex++] = $this->pages[$i];
        }

        // Réassigner les pages et le compteur
        $this->pages = [];
        $this->page = count($newPages);
        for ($i = 1; $i <= $this->page; $i++) {
            $this->pages[$i] = $newPages[$i];
        }
    }
}



function dateEnToutesLettres($date)

{

    // Définir les mois en français

    $mois = [

        1 => 'janvier',

        2 => 'février',

        3 => 'mars',

        4 => 'avril',

        5 => 'mai',

        6 => 'juin',

        7 => 'juillet',

        8 => 'août',

        9 => 'septembre',

        10 => 'octobre',

        11 => 'novembre',

        12 => 'décembre'

    ];


    // Extraire l'année, le mois et le jour de la date

    $annee = date('Y', strtotime($date));

    $mois_num = date('n', strtotime($date));

    $jour = date('j', strtotime($date));



    // Retourner la date en format "jour mois année"

    return $jour . ' ' . $mois[$mois_num] . ' ' . $annee;
}



// Créez un nouvel objet FPDF

$pdf = new PDF('P', 'mm', 'A4');

$pdf->AddPage();

$pdf->SetFont('Arial', 'B', 16);

$pdf->AliasNbPages();



$pdf->AddFont('BookAntiqua', '', 'bookantiqua.php'); // Pour le style normal

$pdf->AddFont('BookAntiqua', 'B', 'bookantiqua_bold.php'); // Pour le style gras, si disponible

$pdf->SetFont('BookAntiqua', '', 12); // Utiliser la police Book Antiqua normale



// Générer le QR code (URL publique stable sur le domaine courant)
$qrCodeData = public_url('export_pdf.php', ['devisId' => $devis['id']]);

// Éviter les soucis de droits en hébergement: fallback vers un fichier temporaire si qrCodeFile/ n'est pas inscriptible
$qrDir = __DIR__ . '/../qrCodeFile';
$qrCleanup = false;
$qrCodeFile = $qrDir . '/qrcode_devis_' . $devis['id'] . '.png';
if (!is_dir($qrDir) || !is_writable($qrDir)) {
    $tmp = tempnam(sys_get_temp_dir(), 'qr_devis_');
    if ($tmp !== false) {
        $qrCodeFile = $tmp . '.png';
        @rename($tmp, $qrCodeFile);
        $qrCleanup = true;
    }
}

QRcode::png($qrCodeData, $qrCodeFile, 'L', 4, 2);



// Ajouter le logo à gauche (si présent et fichier existant)
if (isset($devis['logo']) && $devis['logo'] != '') {
    $logoCandidates = [
        __DIR__ . '/../logo/' . $devis['logo'],
        __DIR__ . '/../../img/' . $devis['logo'],
    ];
    foreach ($logoCandidates as $absLogo) {
        if (file_exists($absLogo)) {
            $pdf->Image($absLogo, 10, 10, 40); // Position (10, 10) avec une largeur de 40
            break;
        }
    }
}



// Ajouter le QR code à droite du logo

$pdf->Image($qrCodeFile, 180, 10, 20); // Position (180, 10) avec une largeur de 20







// Positionnement individuel des informations de FIDEST

$pdf->SetFont('Arial', 'B', 10);



// Positionnement individuel des informations du client

$pdf->SetFont('Arial', 'B', 10);

$pdf->SetFont('BookAntiqua', 'B', 10);

$pdf->SetXY(10, 50); // Position de la première ligne

$pdf->Cell(0, 5, pdf_text(strtoupper($client['nom_client'])), 0, 1, 'L');



$pdf->SetFont('Arial', '', 8);

$pdf->SetXY(10, 55); // Position de la deuxième ligne

$pdf->Cell(0, 5, pdf_text($client['localisation_client']), 0, 1, 'L');



$pdf->SetXY(10, 60); // Position de la troisième ligne

$pdf->Cell(0, 5, pdf_text($client['commune_client']), 0, 1, 'L');



$pdf->SetXY(10, 65); // Position de la quatrième ligne

$pdf->Cell(0, 5, pdf_text($client['bp_client']), 0, 1, 'L');



$pdf->SetXY(10, 70); // Position de la cinquième ligne

$pdf->Cell(0, 5, pdf_text($client['pays_client']), 0, 1, 'L');







// Positionnement individuel des informations du devis

$pdf->SetFont('Arial', 'B', 10);

$pdf->SetFont('BookAntiqua', 'B', 10);



$x = 135;

$y = 50;

$w = 65;

$h = 5;



// Première ligne

$pdf->SetXY($x, $y);

$pdf->MultiCell($w, $h, pdf_text('N° d\'offre: ' . $offre['num_offre']), 0, 'L');

$y += $pdf->GetY() - $y; // Avance de la hauteur utilisée



// Deuxième ligne

$pdf->SetFont('Arial', '', 8);

$pdf->AddFont('BookAntiqua', '', 8);

$pdf->SetXY($x, $y);

$pdf->MultiCell($w, $h, pdf_text('Date: ' . dateEnToutesLettres($offre['date_offre'])), 0, 'L');

$y += $pdf->GetY() - $y;



// Troisième ligne

$pdf->SetXY($x, $y);

$pdf->MultiCell($w, $h, pdf_text('Référence: ' . $offre['reference_offre']), 0, 'L');

$y += $pdf->GetY() - $y;



// Quatrième ligne (commentée)

/*

$pdf->SetXY(150, $y);

$pdf->Cell(0, 5, pdf_text('Votre numéro client: 1064'), 0, 1, 'L');

*/



// Cinquième ligne

$pdf->SetXY($x, $y);

$pdf->MultiCell($w, $h, pdf_text('Votre interlocuteur: ' . strtoupper($offre['commercial_dedie'])), 0, 'L');

$y += $pdf->GetY() - $y;



$pdf->Ln(5); // Ajouter un espace après les informations



// Ajouter les informations concernant le devis juste en dessous des trois colonnes

$pdf->SetFont('Arial', 'B', 12);

$pdf->SetFont('BookAntiqua', 'B', 12);

$pdf->Cell(50, 10, pdf_text('Devis N° ' . $devis['numero_devis']), 0, 0, 'L');



$pdf->SetFont('Arial', '', 10);

$pdf->SetFont('BookAntiqua', '', 10);

$pdf->Cell(0, 10, pdf_text('   à l\'attention de ' . $devis['correspondant']), 0, 1, 'L');



$pdf->SetFont('Arial', '', 8);

$pdf->SetFont('BookAntiqua', '', 8);

$pdf->Cell(0, 5, pdf_text('Pour faire suite a votre demande, '), 0, 1, 'L');

$pdf->Cell(0, 5, pdf_text('nous vous prions de bien vouloir trouver ci-dessous notre meilleur proposition.'), 0, 1, 'L');

$pdf->Cell(0, 5, pdf_text('Nous restons à votre  entière disposition pour toute information complémentaire.'), 0, 1, 'L');

$pdf->SetFont('Arial', '', 8);

$pdf->SetFont('BookAntiqua', '', 8);



$pdf->Ln(5); // Espacement avant le tableau



// Tableau des lignes du devis

$pdf->SetFont('Arial', 'B', 8);

$pdf->SetFont('BookAntiqua', 'B', 8);



// Définir la couleur de remplissage en noir, le texte en blanc, et les lignes de bordure en blanc

$pdf->SetFillColor(0, 0, 0); // Couleur de remplissage noire

$pdf->SetTextColor(255, 255, 255); // Couleur du texte blanche

$pdf->SetDrawColor(169, 169, 169); // Couleur des lignes de bordure gris clair (RGB: 169, 169, 169)



// Ajouter les cellules de l'en-tête avec le remplissage, la couleur du texte, et la couleur des bordures

$pdf->Cell(10, 10, pdf_text('Pos.'), 1, 0, 'C', true);

$pdf->Cell(85, 10, pdf_text('Description'), 1, 0, 'C', true);

$pdf->Cell(20, 10, pdf_text('Quantité'), 1, 0, 'C', true);

$pdf->Cell(30, 10, pdf_text('Prix unitaire'), 1, 0, 'C', true);

$pdf->Cell(20, 10, pdf_text('TVA'), 1, 0, 'C', true);

$pdf->Cell(30, 10, pdf_text('Prix total'), 1, 0, 'C', true);

$pdf->Ln();



// Réinitialiser les couleurs de texte, de remplissage, et des bordures pour le reste du tableau

$pdf->SetTextColor(0, 0, 0); // Texte en noir

$pdf->SetFillColor(255, 255, 255); // Remplissage blanc (ou transparent pour les lignes du tableau)

$pdf->SetDrawColor(220, 220, 220); // Couleur des lignes de bordure gris clair



$tvaFacturable = $devis['tva_facturable'] == 1;



$pdf->SetFont('Arial', '', 8);

$pdf->SetFont('BookAntiqua', '', 8);



// Avant la boucle

$ligneHauteur = 10; // hauteur d'une ligne du tableau

$blocTotalHauteur = 80; // hauteur estimée pour totaux + signatures (ajuste si besoin)

$margeBas = 20; // marge de sécurité avant le pied de page

$pageHauteurMax = 297 - $margeBas; // 297mm pour A4 portrait



$nbLignes = count($lignes);

foreach ($lignes as $i => $ligne) {

    $resteBloc = ($i == $nbLignes - 1) ? $blocTotalHauteur : 0;



    // Estimation de la hauteur de la ligne si besoin

    if ($pdf->GetY() + $ligneHauteur + $resteBloc > $pageHauteurMax) {



        // Afficher le message de poursuite

        $pdf->SetFont('Arial', 'I', 8);

        $pdf->SetTextColor(150, 150, 150); // gris

        $pdf->Cell(0, 8, pdf_text('Le tableau se poursuit à la page suivante...'), 0, 1, 'C');

        $pdf->SetTextColor(0, 0, 0); // noir



        // Aller immédiatement à la page suivante

        $pdf->AddPage();



        // Réafficher l'en-tête du tableau

        $pdf->SetFont('Arial', 'B', 8);

        $pdf->SetFont('BookAntiqua', 'B', 8);

        $pdf->SetFillColor(0, 0, 0);

        $pdf->SetTextColor(255, 255, 255);

        $pdf->SetDrawColor(169, 169, 169);

        $pdf->Cell(10, 10, pdf_text('Pos.'), 1, 0, 'C', true);

        $pdf->Cell(85, 10, pdf_text('Description'), 1, 0, 'C', true);

        $pdf->Cell(20, 10, pdf_text('Quantité'), 1, 0, 'C', true);

        $pdf->Cell(30, 10, pdf_text('Prix unitaire'), 1, 0, 'C', true);

        $pdf->Cell(20, 10, pdf_text('TVA'), 1, 0, 'C', true);

        $pdf->Cell(30, 10, pdf_text('Prix total'), 1, 0, 'C', true);

        $pdf->Ln();



        // Réinitialiser les couleurs

        $pdf->SetTextColor(0, 0, 0);

        $pdf->SetFillColor(255, 255, 255);

        $pdf->SetDrawColor(220, 220, 220);



        $pdf->SetFont('Arial', '', 8);

        $pdf->SetFont('BookAntiqua', '', 8);
    }



    // Sauvegarder la position de départ de la ligne

    $xStart = $pdf->GetX();

    $yStart = $pdf->GetY();



    // Colonne Pos.

    $pdf->Cell(10, 10, $i + 1, 1);



    // Colonne Description (MultiCell pour retour à la ligne SANS soulignement intermédiaire)

    $pdf->SetFont('Arial', 'B', 8);

    $pdf->AddFont('BookAntiqua', 'B', 8);

    $pdf->SetXY($xStart + 10, $yStart);

    $pdf->MultiCell(85, 5, pdf_text($ligne['designation']), 'LR', 'L');



    // Calculer la hauteur utilisée par la description
    $descHeight = $pdf->GetY() - $yStart;
    $rowHeight = max($descHeight, 10); // 10 = hauteur min d'une ligne

    // Bordure du bas de la cellule description (un seul trait de fermeture)
    $pdf->SetXY($xStart + 10, $yStart + $rowHeight);
    $pdf->Cell(85, 0, '', 'T');



    // Repositionner pour les autres colonnes sur la même ligne

    $pdf->SetXY($xStart + 10 + 85, $yStart);



    $pdf->SetFont('Arial', '', 8);

    $pdf->AddFont('BookAntiqua', '', 8);

    $pdf->Cell(20, $rowHeight, $ligne['quantite'], 1, 0, 'C');

    $pdf->Cell(30, $rowHeight, number_format($ligne['prix'], 0, ',', ' ') . ' ', 1, 0, 'R');

    $tvaMontant = ($tvaFacturable) ? $ligne['quantite'] * $ligne['prix'] * 0.18 : 0;

    $pdf->Cell(20, $rowHeight, number_format($tvaMontant, 0, ',', ' ') . ' ', 1, 0, 'R');

    $pdf->Cell(30, $rowHeight, number_format($ligne['total'], 0, ',', ' ') . ' ', 1, 0, 'R');



    // Aller à la ligne suivante, à la bonne hauteur

    $pdf->Ln($rowHeight);
}



// Avant d'imprimer les totaux et signatures, vérifier que le bloc tient sur la page
if ($pdf->GetY() + $blocTotalHauteur > $pageHauteurMax) {
    // Afficher le message de poursuite
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->SetTextColor(150, 150, 150); // gris
    $pdf->Cell(0, 8, pdf_text('Le tableau se poursuit à la page suivante...'), 0, 1, 'C');
    $pdf->SetTextColor(0, 0, 0); // noir

    // Nouvelle page (les totaux ne nécessitent pas la réinsertion de l’en-tête du tableau)
    $pdf->AddPage();
}

// Ajouter un séparateur

$pdf->Ln(5); // Espace vide

$pdf->SetDrawColor(0, 0, 0); // Couleur des lignes de bordure blanc

$pdf->Cell(190, 0, '', 'T'); // Ligne horizontale

$pdf->SetDrawColor(255, 255, 255); // Couleur des lignes de bordure blanc



// Ajouter le total HT, TVA et total TTC

$pdf->Ln(2); // Petit espace après la ligne

$pdf->SetFont('Arial', 'B', 8);

$pdf->AddFont('BookAntiqua', 'B', 8); // Pour le style normal

$pdf->Cell(115, 10, '', 0); // Espace avant le total

$pdf->Cell(45, 10, pdf_text('Montant HT'), 1);

$pdf->Cell(30, 10, number_format($devis['total_ht'], 0, ',', ' ') . ' XOF', 1);

$pdf->Ln();

if ($devis['tva_facturable'] == 1) {

    $pdf->Cell(115, 10, '', 0); // Espace avant le total

    $pdf->Cell(45, 10, pdf_text('TVA 18%'), 1);

    $pdf->Cell(30, 10, number_format($devis['tva'], 0, ',', ' ') . ' XOF', 1);

    $pdf->Ln();
}

$pdf->Cell(115, 10, '', 0);

$pdf->Cell(45, 10, pdf_text('Montant TTC'), 1);

$pdf->Cell(30, 10, number_format($devis['total_ttc'], 0, ',', ' ') . ' XOF', 1);



$pdf->Ln(20);



// Ajouter les conditions

$pdf->SetFont('Arial', 'BU', 8); // Police en gras pour les titres

$pdf->SetFont('BookAntiqua', 'BU', 8);

$pdf->Cell(25, 5, pdf_text('Validité de l\'offre:'), 0, 0, 'L');

$pdf->SetFont('Arial', '', 8); // Police normale pour les valeurs

$pdf->Cell(0, 5, pdf_text('30 jours'), 0, 1, 'L');



$pdf->SetFont('Arial', 'BU', 8); // Police en gras pour les titres

$pdf->SetFont('BookAntiqua', 'BU', 8);

$pdf->Cell(26, 5, pdf_text('Délai de livraison:'), 0, 0, 'L');

$pdf->SetFont('Arial', '', 8); // Police normale pour les valeurs

$pdf->Cell(0, 5, pdf_text($devis['delai_livraison']), 0, 1, 'L');



$pdf->SetFont('Arial', 'BU', 8); // Police en gras pour les titres

$pdf->SetFont('BookAntiqua', 'BU', 8);

$pdf->Cell(35, 5, pdf_text('Conditions de règlement:'), 0, 0, 'L');

$pdf->SetFont('Arial', '', 8); // Police normale pour les valeurs

$pdf->SetFont('BookAntiqua', '', 8);

$pdf->MultiCell(0, 5, pdf_text((string)$devis['termes_conditions']), 0, 'L');

if (!empty($devis['pied_de_page'])) {
    $pdf->Ln(3);
    $pdf->SetFont('BookAntiqua', 'B', 8);
    $pdf->Cell(0, 5, pdf_text('Informations complémentaires'), 0, 1, 'L');
    $pdf->SetFont('BookAntiqua', '', 8);
    $pdf->MultiCell(0, 5, pdf_text((string)$devis['pied_de_page']), 0, 'L');
}



// Espaces pour les signatures

$pdf->Ln(5); // Ajouter un espace vertical



$pdf->SetFont('BookAntiqua', 'BU', 10);



// Intitulé légèrement à gauche pour Directeur Commercial

$pdf->Cell(5); // Réduire l'espace initial à gauche

$pdf->Cell(80, 10, pdf_text('Directeur Commercial (Nom et Signature)'), 0, 0, 'L');



// Intitulé à l'extrême droite pour Directeur Général

$pdf->Cell(100, 10, pdf_text('Directeur Général (Nom et Signature)'), 0, 1, 'R');



$pdf->Ln(1); // Espace sous les titres des signatures



// Récupération des signatures

$signatureCommercial = is_array($directeurCommercial) && !empty($directeurCommercial['signature']) ? __DIR__ . '/../signatures/' . basename((string)$directeurCommercial['signature']) : '';

$signatureGeneral = is_array($directeurGeneral) && !empty($directeurGeneral['signature']) ? __DIR__ . '/../signatures/' . basename((string)$directeurGeneral['signature']) : '';



// Boîtes pour les signatures avec insertion des images

$pdf->Cell(5);



// Signature du Directeur Commercial

if ($devisObj->isValidCommercial($devisId) && is_file($signatureCommercial)) {  // Signature disponible



    list($widthCommercial, $heightCommercial) = getimagesize($signatureCommercial);

    $aspectRatioCommercial = $widthCommercial / $heightCommercial;



    // Augmenter la taille maximale

    $maxWidthCommercial = 150;  // Augmenter la largeur maximale

    $maxHeightCommercial = 50;  // Augmenter la hauteur maximale



    if ($aspectRatioCommercial > 1) {

        $newWidthCommercial = $maxWidthCommercial;

        $newHeightCommercial = $maxWidthCommercial / $aspectRatioCommercial;
    } else {

        $newHeightCommercial = $maxHeightCommercial;

        $newWidthCommercial = $maxHeightCommercial * $aspectRatioCommercial;
    }

    $pdf->Cell(80, 30, $pdf->Image($signatureCommercial, $pdf->GetX() + ($maxWidthCommercial - $newWidthCommercial) / 2 - 30, $pdf->GetY() + ($maxHeightCommercial - $newHeightCommercial) / 2, $newWidthCommercial, $newHeightCommercial), 1, 0, 'C');
}



// Espace entre les boîtes

$pdf->Cell(30);



// Signature du Directeur Général

if ($devisObj->isValidGenerale($devisId) && is_file($signatureGeneral)) {  // Signature disponible



    list($widthGeneral, $heightGeneral) = getimagesize($signatureGeneral);

    $aspectRatioGeneral = $widthGeneral / $heightGeneral;



    // Réduire les dimensions maximales pour la signature

    $maxWidthGeneral = 60;  // Réduire la largeur maximale

    $maxHeightGeneral = 20; // Réduire la hauteur maximale



    if ($aspectRatioGeneral > 1) {

        $newWidthGeneral = $maxWidthGeneral;

        $newHeightGeneral = $maxWidthGeneral / $aspectRatioGeneral;
    } else {

        $newHeightGeneral = $maxHeightGeneral;

        $newWidthGeneral = $maxHeightGeneral * $aspectRatioGeneral;
    }



    // Positionner plus bas : décaler l'axe Y actuel de 10 unités

    $pdf->SetY($pdf->GetY() + 10); // Décalage vertical de 10 unités (ajustez cette valeur si nécessaire)



    // Positionner la signature à droite, dans la deuxième cellule

    $pdf->Cell(125); // Cette cellule vide pousse la signature à droite

    $pdf->Cell(80, 30, $pdf->Image($signatureGeneral, $pdf->GetX() + ($maxWidthGeneral - $newWidthGeneral) / 2, $pdf->GetY() + ($maxHeightGeneral - $newHeightGeneral) / 2, $newWidthGeneral, $newHeightGeneral), 1, 1, 'C');
}



$pdf->SetFont('BookAntiqua', '', 10);



// Intitulé légèrement à gauche pour Directeur Commercial

$pdf->Cell(5); // Réduire l'espace initial à gauche

if ($devisObj->isValidCommercial($devisId) && is_array($directeurCommercial)) {  // Vérifie si la validation commerciale a eu lieu

    $pdf->Cell(80, 10, pdf_text($directeurCommercial['prenom'] . ' ' . $directeurCommercial['nom']), 0, 0, 'L');
} else {

    $pdf->Cell(80, 10, pdf_text('En Attente de validation...'), 0, 0, 'L');
}





// Intitulé à l'extrême droite pour Directeur Général

if ($devisObj->isValidGenerale($devisId) && is_array($directeurGeneral)) {  // Vérifie si la validation générale a eu lieu

    $pdf->Cell(100, 10, pdf_text($directeurGeneral['prenom'] . ' ' . $directeurGeneral['nom']), 0, 1, 'R');
} else {

    $pdf->Cell(80, 10, pdf_text('En Attente de validation...'), 0, 0, 'L');
}





// Boîtes pour les signatures

$pdf->Cell(5); // Ajustement de l'espace à gauche

$pdf->Cell(80, 40, '', 1, 0, 'C'); // Boîte pour la signature du Directeur Commercial

$pdf->Cell(10); // Espace entre les boîtes

$pdf->Cell(80, 40, '', 1, 1, 'C'); // Boîte pour la signature du Directeur Général





// Effacez tout contenu précédent envoyé

if (ob_get_length()) ob_clean();



// Définir les en-têtes pour l'affichage ou le téléchargement du fichier

header('Content-Type: application/pdf');

if (isset($_GET['download']) && $_GET['download'] == '1') {

    header('Content-Disposition: attachment; filename="devis_' . $devis['id'] . '.pdf"');
} else {

    header('Content-Disposition: inline; filename="devis_' . $devis['id'] . '.pdf"');
}



// Générer le PDF et l'afficher dans le navigateur
// Option: supprimer des pages avant l'Output, ex: ?deletePages=2,4
if (isset($_GET['deletePages'])) {
    $list = explode(',', $_GET['deletePages']);
    // supprimer en ordre décroissant pour conserver les index valides
    $numbers = array_map('intval', $list);
    rsort($numbers);
    foreach ($numbers as $pno) {
        if ($pno > 0) {
            $pdf->removePage($pno);
        }
    }
}

// Respecter le mode d'ouverture (inline vs attachment)
if (isset($_GET['download']) && $_GET['download'] == '1') {
    $pdf->Output('D', 'devis_' . $devis['id'] . '.pdf');
} else {
    $pdf->Output('I', 'devis_' . $devis['id'] . '.pdf');
}

if (!empty($qrCleanup) && $qrCleanup && is_string($qrCodeFile) && file_exists($qrCodeFile)) {
    @unlink($qrCodeFile);
}



unset($_SESSION['devisId']);



exit();
