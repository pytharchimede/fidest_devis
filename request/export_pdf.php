<?php

session_start();
include('../fpdf186/fpdf.php');
include('../../logi/connex.php');
require_once("../phpqrcode/qrlib.php");

// Vérifiez que le devisId est défini dans la session ou dans l'URL
if (!isset($_SESSION['devisId']) && !isset($_GET['devisId'])) {
    die('ID de devis non défini.');
}

// Prioriser l'ID du devis reçu via $_GET
if (isset($_GET['devisId'])) {
    $devisId = $_GET['devisId'];
    $_SESSION['devisId'] = $devisId; // Mettre à jour la session avec le nouvel ID
} else {
    $devisId = $_SESSION['devisId'];
}

// Déboguer pour vérifier la valeur de $devisId
var_dump($devisId);

// Récupérer les données du devis depuis la base de données
$stmt = $con->prepare("SELECT * FROM devis WHERE id = ?");
$stmt->execute([$devisId]);
$devis = $stmt->fetch(PDO::FETCH_ASSOC);

// Récupérer les lignes du devis
$stmt = $con->prepare("SELECT * FROM ligne_devis WHERE devis_id = ?");
$stmt->execute([$devisId]);
$lignes = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Récupérer le client
$stmt  = $con->prepare("SELECT * FROM client WHERE id_client =:A ");
$stmt->execute(array('A'=>$devis['client_id']));
$client = $stmt->fetch();


// Récupérer le client
$stmt  = $con->prepare("SELECT * FROM offre WHERE id_offre =:A ");
$stmt->execute(array('A'=>$devis['offre_id']));
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
        
        $this->Image('../../img/logo_veritas.jpg', 150,10,30);	

    }
    
    // Méthode pour le pied de page
    function Footer()
    {
        
        // Dessiner une ligne grise
        $this->SetDrawColor(0, 0, 0); // Couleur de la ligne grise
        $this->Line(10, 272, 200, 272); // Position de la ligne (10 mm du bord gauche à 260 mm du bord haut)
        
        
    	// Position at 1.5 cm from bottom
        $this->SetY(-22);
    	
    //    $this->Image('../../img/logo_veritas.jpg', 10,275,30);	
        
        // Arial italic 8
        $this->SetFont('Arial','',7);
    
    	$this->Cell(0,3.5,utf8_decode("FOURNITURES INDUSTRIELLES, DEPANNAGE ET TRAVAUX PUBLIQUES - Au capital de 10 000 000 F CFA - Siège Social : Abidjan, Koumassi, Zone industrielle"),0,1,'C');
    	$this->Cell(0,3.5,utf8_decode("01 BP 1642 Abidjan 01 - Téléphone : (+225) +225 27-21-36-27-27  -  Email : info@fidest.org - RCCM : CI-ABJ-2017-B-20163  -  N° CC : 010274200088"),0,1,'C');

    //	$this->Image('logo.jpg', 172,275,30);	
    	
        // Page number
        $this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
    	
    } 


}

function dateEnToutesLettres($date) {
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
$pdf = new PDF('P','mm','A4');
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);
$pdf->AliasNbPages();

$pdf->AddFont('BookAntiqua', '', 'bookantiqua.php'); // Pour le style normal
$pdf->AddFont('BookAntiqua', 'B', 'bookantiqua_bold.php'); // Pour le style gras, si disponible
$pdf->SetFont('BookAntiqua', '', 12); // Utiliser la police Book Antiqua normale

// Générer le QR code
$qrCodeData = 'https://fidest.ci/devis/request/export_pdf.php?devisId='.$devis['id']; // Remplacez ceci par les données pour le QR code
$qrCodeFile = '../qrCodeFile/qrcode.png'; // Nom du fichier QR code
QRcode::png($qrCodeData, $qrCodeFile, 'L', 4, 2); // Générer le QR code

// Ajouter le logo à gauche
if(isset($devis['logo']) && $devis['logo']!=''){
$pdf->Image('../logo/'.$devis['logo'], 10, 10, 40); // Position (10, 10) avec une largeur de 30
}

// Ajouter le QR code à droite du logo
$pdf->Image($qrCodeFile, 180, 10, 20); // Position (180, 10) avec une largeur de 20



// Positionnement individuel des informations de FIDEST
$pdf->SetFont('Arial', 'B', 10);

// Positionnement individuel des informations de SIFCA
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFont('BookAntiqua', 'B', 10);
$pdf->SetXY(10, 50); // Position de la première ligne
$pdf->Cell(0, 5, utf8_decode(strtoupper($client['nom_client'])), 0, 1, 'L');

$pdf->SetFont('Arial', '', 8);
$pdf->SetXY(10, 55); // Position de la deuxième ligne
$pdf->Cell(0, 5, utf8_decode($client['localisation_client']), 0, 1, 'L');

$pdf->SetXY(10, 60); // Position de la troisième ligne
$pdf->Cell(0, 5, utf8_decode($client['commune_client']), 0, 1, 'L');

$pdf->SetXY(10, 65); // Position de la quatrième ligne
$pdf->Cell(0, 5, utf8_decode($client['bp_client']), 0, 1, 'L');

$pdf->SetXY(10, 70); // Position de la cinquième ligne
$pdf->Cell(0, 5, utf8_decode($client['pays_client']), 0, 1, 'L');



// Positionnement individuel des informations du devis
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFont('BookAntiqua', 'B', 10);
$pdf->SetXY(135, 50); // Position de la première ligne
$pdf->Cell(0, 5, utf8_decode('N° d\'offre: '.$offre['num_offre']), 0, 1, 'L');

$pdf->SetFont('Arial', '', 8);
$pdf->AddFont('BookAntiqua', '', 8); // Pour le style normal
$pdf->SetXY(135, 55); // Position de la deuxième ligne
$pdf->Cell(0, 5, utf8_decode('Date: '.dateEnToutesLettres($offre['date_offre'])), 0, 1, 'L');

$pdf->SetXY(135, 60); // Position de la troisième ligne
$pdf->Cell(0, 5, utf8_decode('Référence: '.$offre['reference_offre']), 0, 1, 'L');
/*
$pdf->SetXY(150, 65); // Position de la quatrième ligne
$pdf->Cell(0, 5, utf8_decode('Votre numéro client: 1064'), 0, 1, 'L');
*/
$pdf->SetXY(135, 65); // Position de la cinquième ligne
$pdf->Cell(0, 5, utf8_decode('Votre interlocuteur: '.$offre['commercial_dedie']), 0, 1, 'L');

$pdf->Ln(10); // Ajouter un espace après les informations

// Ajouter les informations concernant le devis juste en dessous des trois colonnes
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetFont('BookAntiqua', 'B', 12);
$pdf->Cell(50, 10, utf8_decode('Devis N° ' . $devis['numero_devis']), 0, 0, 'L');

$pdf->SetFont('Arial', '', 10);
$pdf->SetFont('BookAntiqua', '', 10);
$pdf->Cell(0, 10, utf8_decode(' à l\'attention de ' . $devis['correspondant']), 0, 1, 'L');

$pdf->SetFont('Arial', '', 8);
$pdf->SetFont('BookAntiqua', '', 8);
$pdf->Cell(0, 5, utf8_decode('Pour faire suite a votre demande, '), 0, 1, 'L');
$pdf->Cell(0, 5, utf8_decode('nous vous prions de bien vouloir trouver ci-dessous notre meilleur proposition.'), 0, 1, 'L');
$pdf->Cell(0, 5, utf8_decode('Nous restons à votre  entière disposition pour toute information complémentaire.'), 0, 1, 'L');
$pdf->SetFont('Arial', '', 8);
$pdf->SetFont('BookAntiqua', '', 8);

$pdf->Ln(10); // Espacement avant le tableau

// Tableau des lignes du devis
$pdf->SetFont('Arial', 'B', 8);
$pdf->SetFont('BookAntiqua', 'B', 8);

// Définir la couleur de remplissage en noir, le texte en blanc, et les lignes de bordure en blanc
$pdf->SetFillColor(0, 0, 0); // Couleur de remplissage noire
$pdf->SetTextColor(255, 255, 255); // Couleur du texte blanche
$pdf->SetDrawColor(169, 169, 169); // Couleur des lignes de bordure gris clair (RGB: 169, 169, 169)

// Ajouter les cellules de l'en-tête avec le remplissage, la couleur du texte, et la couleur des bordures
$pdf->Cell(10, 10, utf8_decode('Pos.'), 1, 0, 'C', true);
$pdf->Cell(85, 10, utf8_decode('Description'), 1, 0, 'C', true);
$pdf->Cell(20, 10, utf8_decode('Quantité'), 1, 0, 'C', true);
$pdf->Cell(45, 10, utf8_decode('Prix unitaire'), 1, 0, 'C', true);
//$pdf->Cell(15, 10, utf8_decode('TVA'), 1, 0, 'C', true);
$pdf->Cell(30, 10, utf8_decode('Prix total'), 1, 0, 'C', true);
$pdf->Ln();

// Réinitialiser les couleurs de texte, de remplissage, et des bordures pour le reste du tableau
$pdf->SetTextColor(0, 0, 0); // Texte en noir
$pdf->SetFillColor(255, 255, 255); // Remplissage blanc (ou transparent pour les lignes du tableau)
$pdf->SetDrawColor(0, 0, 0); // Couleur des lignes de bordure noire
$pdf->SetDrawColor(255, 255, 255); // Couleur des lignes de bordure blanc



$pdf->SetFont('Arial', '', 8);
$pdf->SetFont('BookAntiqua', '', 8);
foreach ($lignes as $i => $ligne) {
    $pdf->Cell(10, 10, $i + 1, 1);
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->AddFont('BookAntiqua', 'B', 8); // Pour le style normal
    $pdf->Cell(85, 10, utf8_decode($ligne['designation']), 1);
    $pdf->SetFont('Arial', '', 8);
    $pdf->AddFont('BookAntiqua', '', 8); // Pour le style normal
    $pdf->Cell(20, 10, $ligne['quantite'], 1);
    $pdf->Cell(45, 10, number_format($ligne['prix'], 0, ',', ' ') . ' XOF', 1);
   // $pdf->Cell(15, 10, number_format($ligne['tva'], 0, ',', ' ') . ' XOF', 1);
    $pdf->Cell(30, 10, number_format($ligne['total'], 0, ',', ' ') . ' XOF', 1);
    $pdf->Ln();
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
$pdf->Cell(45, 10, utf8_decode('Montant HT'), 1);
$pdf->Cell(30, 10, number_format($devis['total_ht'], 0, ',', ' ') . ' XOF', 1);
$pdf->Ln();
if ($devis['tva_facturable'] == 1) {
    $pdf->Cell(115, 10, '', 0); // Espace avant le total
    $pdf->Cell(45, 10, utf8_decode('TVA 18%'), 1);
    $pdf->Cell(30, 10, number_format($devis['tva'], 0, ',', ' ') . ' XOF', 1);
    $pdf->Ln();
}
$pdf->Cell(115, 10, '', 0);
$pdf->Cell(45, 10, utf8_decode('Montant TTC'), 1);
$pdf->Cell(30, 10, number_format($devis['total_ttc'], 0, ',', ' ') . ' XOF', 1);

$pdf->Ln(20);

// Ajouter les conditions
$pdf->SetFont('Arial', 'BU', 8); // Police en gras pour les titres
$pdf->SetFont('BookAntiqua', 'BU', 8);
$pdf->Cell(25, 5, utf8_decode('Validité de l\'offre:'), 0, 0, 'L');
$pdf->SetFont('Arial', '', 8); // Police normale pour les valeurs
$pdf->Cell(0, 5, utf8_decode('30 jours'), 0, 1, 'L');

$pdf->SetFont('Arial', 'BU', 8); // Police en gras pour les titres
$pdf->SetFont('BookAntiqua', 'BU', 8);
$pdf->Cell(26, 5, utf8_decode('Délai de livraison:'), 0, 0, 'L');
$pdf->SetFont('Arial', '', 8); // Police normale pour les valeurs
$pdf->Cell(0, 5, utf8_decode($devis['delai_livraison']), 0, 1, 'L');

$pdf->SetFont('Arial', 'BU', 8); // Police en gras pour les titres
$pdf->SetFont('BookAntiqua', 'BU', 8);
$pdf->Cell(35, 5, utf8_decode('Conditions de règlement:'), 0, 0, 'L');
$pdf->SetFont('Arial', '', 8); // Police normale pour les valeurs
$pdf->SetFont('BookAntiqua', '', 8);
$pdf->Cell(0, 5, utf8_decode('Habituelle entre nous'), 0, 1, 'L');



// Effacez tout contenu précédent envoyé
ob_clean();

// Définir les en-têtes pour le téléchargement du fichier
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="devis_' . $devis['id'] . '.pdf"');

// Générer le PDF et l'afficher dans le navigateur
$pdf->Output('I', 'devis_' . $devis['id'] . '.pdf');

unset($_SESSION['devisId']);

exit();
?>
