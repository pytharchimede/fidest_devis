<?php
declare(strict_types=1);
namespace App\Application\NeedRequest;
use App\Domain\NeedRequest\NeedRequestRepository;use App\Infrastructure\Branding\Branding;use RuntimeException;
require_once APP_ROOT.'/fpdf186/fpdf.php';
require_once APP_ROOT.'/phpqrcode/qrlib.php';
final class ExportNeedRequestPdf
{
    public function __construct(private readonly NeedRequestRepository $repository,private readonly Branding $branding){}
    public function execute(int $id,string $verificationUrl):array
    {
        $document=$this->repository->document($id);if(!$document)throw new RuntimeException('FEB introuvable.',404);
        $qr=tempnam(sys_get_temp_dir(),'fidest_feb_qr_');if($qr===false)throw new RuntimeException('QR code indisponible.');$qrPng=$qr.'.png';@unlink($qr);\QRcode::png($verificationUrl,$qrPng,QR_ECLEVEL_M,4,2);
        $pdf=new NeedRequestPdf($this->branding,$document,$qrPng);$pdf->AliasNbPages();$pdf->AddPage();$pdf->renderDocument();$content=$pdf->Output('S');@unlink($qrPng);
        return ['content'=>$content,'filename'=>preg_replace('/[^A-Za-z0-9_-]+/','-',(string)$document['document_uid']).'.pdf'];
    }
}

final class NeedRequestPdf extends \FPDF
{
    public function __construct(private readonly Branding $branding,private readonly array $document,private readonly string $qr){parent::__construct('P','mm','A4');$this->SetMargins(14,42,14);$this->SetAutoPageBreak(true,25);}
    private function t(mixed $value):string{$text=(string)$value;$converted=@iconv('UTF-8','windows-1252//TRANSLIT//IGNORE',$text);return $converted===false?$text:$converted;}
    public function Header()
    {
        $logo=APP_ROOT.'/'.$this->branding->get('logo','img/logo_fidest.png');if(is_file($logo))$this->Image($logo,14,9,31);
        $this->SetXY(49,8);$this->SetFont('Arial','B',12);$this->SetTextColor(34,37,75);$this->Cell(92,8,$this->t('FICHE D’EXPRESSION DE BESOIN'),1,0,'C');
        $this->SetFont('Arial','',7);$this->SetXY(141,8);$this->Cell(55,5,$this->t('ID : '.$this->document['document_uid']),1,2,'L');$this->Cell(55,5,$this->t('Version : '.$this->document['document_version']),1,2,'L');$this->Cell(55,5,$this->t('Émission : '.date('d/m/Y',strtotime((string)$this->document['created_at']))),1,2,'L');$this->Cell(55,5,$this->t('Page : '.$this->PageNo().'/{nb}'),1,0,'L');
        $this->SetXY(49,28);$this->SetFont('Arial','I',6.5);$this->SetTextColor(100,102,116);$this->Cell(147,5,$this->t('Document maîtrisé · Présentation inspirée des exigences documentaires ISO 9001'),0,0,'C');$this->SetDrawColor(250,189,2);$this->SetLineWidth(.8);$this->Line(14,36,196,36);
    }
    public function Footer(){$this->SetY(-20);$this->SetDrawColor(34,37,75);$this->Line(14,$this->GetY(),196,$this->GetY());$this->SetFont('Arial','',6.5);$this->SetTextColor(90,92,105);$this->MultiCell(0,3.4,$this->t($this->branding->footerBlock()),0,'C');}
    private function section(string $title):void{$this->Ln(4);$this->SetFillColor(34,37,75);$this->SetTextColor(255);$this->SetFont('Arial','B',8);$this->Cell(0,7,$this->t($title),0,1,'L',true);$this->SetTextColor(30);}
    private function pair(string $label,mixed $value,float $width=91):void{$this->SetFont('Arial','B',7);$this->SetFillColor(245,246,249);$this->Cell(34,7,$this->t($label),1,0,'L',true);$this->SetFont('Arial','',7);$this->Cell($width-34,7,$this->t($value?:'—'),1,0,'L');}
    public function renderDocument():void
    {
        $d=$this->document;$this->SetXY(14,42);$this->SetFont('Arial','B',15);$this->SetTextColor(34,37,75);$this->Cell(0,10,$this->t($d['numero_feb']),0,1,'L');$this->SetTextColor(30);
        $this->section('1. TRAÇABILITÉ DE LA DEMANDE');$this->pair('Client',$d['nom_client']);$this->pair('Appel d’offre',$d['num_offre']);$this->Ln();$this->pair('Devis',$d['numero_devis']);$this->pair('Bon de commande',$d['numero_bc']);$this->Ln();$this->pair('Date du bon',date('d/m/Y',strtotime((string)$d['date_commande'])));$this->pair('Disponibilité FEB',date('d/m/Y',strtotime((string)$d['date_disponibilite'])));$this->Ln();
        $this->section('2. PLANNING D’EXÉCUTION');$this->pair('Phase',$d['planning_label']);$this->pair('Période',date('d/m/Y',strtotime((string)$d['date_debut'])).' au '.date('d/m/Y',strtotime((string)$d['date_fin'])));$this->Ln();
        $this->section('3. EXPRESSION DÉTAILLÉE DU BESOIN');$this->SetFillColor(250,189,2);$this->SetTextColor(34,37,75);$this->SetFont('Arial','B',7);foreach([['Catégorie',27],['Désignation',78],['Qté',16],['P.U.',28],['Montant',33]] as [$label,$width])$this->Cell($width,7,$this->t($label),1,0,'C',true);$this->Ln();$this->SetFont('Arial','',7);$this->SetTextColor(30);foreach($d['lines'] as $line){$this->Cell(27,7,$this->t(ucfirst((string)$line['categorie'])),1);$this->Cell(78,7,$this->t(mb_strimwidth((string)$line['designation'],0,52,'…')),1);$this->Cell(16,7,number_format((float)$line['quantite'],2,',',' '),1,0,'R');$this->Cell(28,7,number_format((float)$line['prix_unitaire'],0,',',' '),1,0,'R');$this->Cell(33,7,number_format((float)$line['montant'],0,',',' '),1,1,'R');}
        $this->SetFont('Arial','B',8);$this->Cell(149,8,$this->t('TOTAL DU BESOIN'),1,0,'R');$this->Cell(33,8,number_format((float)$d['montant_demande'],0,',',' ').' FCFA',1,1,'R');
        $this->section('4. VISA ET MAÎTRISE DOCUMENTAIRE');$y=$this->GetY();foreach([['Établi par','Nom / Date / Signature'],['Analysé par','Nom / Date / Signature'],['Approuvé par','Nom / Date / Signature']] as $i=>$box){$x=14+$i*52;$this->SetXY($x,$y);$this->SetFont('Arial','B',7);$this->Cell(50,7,$this->t($box[0]),1,2,'C');$this->SetFont('Arial','',6);$this->Cell(50,20,$this->t($box[1]),1,0,'C');}$this->Image($this->qr,174,$y+1,20);$this->SetXY(168,$y+22);$this->SetFont('Arial','',5.5);$this->MultiCell(28,3,$this->t('Scanner pour vérifier le document'),0,'C');
    }
}
