<?php
declare(strict_types=1);
namespace App\Application\Workflow;
use RuntimeException;
use SimpleXMLElement;
use ZipArchive;
final class BudgetLineImporter
{
    public function import(array $file):array
    {
        if(($file['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE)return [];
        if(($file['error']??UPLOAD_ERR_OK)!==UPLOAD_ERR_OK)throw new RuntimeException('Le fichier de déboursé n’a pas pu être importé.');
        if((int)($file['size']??0)>5*1024*1024)throw new RuntimeException('Le fichier Excel dépasse 5 Mo.');
        $name=strtolower((string)($file['name']??''));$path=(string)$file['tmp_name'];
        if(str_ends_with($name,'.xlsx'))return $this->fromXlsx($path);
        if(str_ends_with($name,'.xls')||str_ends_with($name,'.xml'))return $this->fromSpreadsheetXml($path);
        if(str_ends_with($name,'.csv'))return $this->fromCsv($path);
        throw new RuntimeException('Utilisez le modèle Excel XLS, un fichier XLSX ou CSV.');
    }
    private function normalize(array $rows):array
    {
        $lines=[];foreach($rows as $index=>$row){if($index===0&&preg_match('/cat[eé]gorie/i',(string)($row[0]??'')))continue;$category=strtolower(trim((string)($row[0]??'')));$label=trim((string)($row[1]??''));$qty=(float)str_replace(',','.',(string)($row[2]??1));$price=(float)str_replace([' ',','],['','.'],(string)($row[3]??0));if($label===''&&$price<=0)continue;if(!in_array($category,['fourniture','transport'],true))throw new RuntimeException('Catégorie invalide à la ligne '.($index+1).' : utilisez Fourniture ou Transport.');if($label===''||$qty<=0||$price<0)throw new RuntimeException('Données invalides à la ligne '.($index+1).'.');$lines[]=['category'=>$category,'label'=>$label,'quantity'=>$qty,'unit_price'=>$price,'amount'=>$qty*$price];}return $lines;
    }
    private function fromCsv(string $path):array{$handle=fopen($path,'rb');if(!$handle)throw new RuntimeException('Fichier illisible.');$rows=[];while(($row=fgetcsv($handle,0,';'))!==false){if(count($row)===1)$row=str_getcsv((string)$row[0],',');$rows[]=$row;}fclose($handle);return $this->normalize($rows);}
    private function fromSpreadsheetXml(string $path):array{$xml=simplexml_load_file($path);if(!$xml instanceof SimpleXMLElement)throw new RuntimeException('Fichier Excel XML invalide.');$xml->registerXPathNamespace('ss','urn:schemas-microsoft-com:office:spreadsheet');$rows=[];foreach($xml->xpath('//ss:Worksheet[1]/ss:Table/ss:Row')?:[] as $row){$values=[];foreach($row->xpath('./ss:Cell/ss:Data')?:[] as $cell)$values[]=(string)$cell;$rows[]=$values;}return $this->normalize($rows);}
    private function fromXlsx(string $path):array{$zip=new ZipArchive();if($zip->open($path)!==true)throw new RuntimeException('Fichier XLSX invalide.');$shared=[];$sharedXml=$zip->getFromName('xl/sharedStrings.xml');if($sharedXml){$xml=simplexml_load_string($sharedXml);foreach($xml->si??[] as $item)$shared[]=trim(implode('',array_map('strval',$item->xpath('.//t')?:[])));}$sheet=$zip->getFromName('xl/worksheets/sheet1.xml');$zip->close();if(!$sheet)throw new RuntimeException('La première feuille Excel est introuvable.');$xml=simplexml_load_string($sheet);$rows=[];foreach($xml->sheetData->row??[] as $row){$values=[];foreach($row->c as $cell){$value=(string)$cell->v;$values[]=((string)$cell['t']==='s')?($shared[(int)$value]??''):$value;}$rows[]=$values;}return $this->normalize($rows);}
}
