<?php
declare(strict_types=1);
namespace App\Application\Client;
use InvalidArgumentException;
final class ClientLogoUploader
{
    public function __construct(private readonly string $directory){}
    public function upload(?array $file,?string $current=null):?string
    {
        if(!$file||($file['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE)return $current;
        if(($file['error']??UPLOAD_ERR_OK)!==UPLOAD_ERR_OK)throw new InvalidArgumentException('Le logo n’a pas pu être transféré.');
        if((int)($file['size']??0)>5*1024*1024)throw new InvalidArgumentException('Le logo dépasse 5 Mo.');
        $mime=(string)(new \finfo(FILEINFO_MIME_TYPE))->file((string)$file['tmp_name']);$types=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/svg+xml'=>'svg'];
        if(!isset($types[$mime]))throw new InvalidArgumentException('Logo invalide. Utilisez JPG, PNG, WEBP ou SVG.');
        if(!is_dir($this->directory)&&!mkdir($this->directory,0775,true)&&!is_dir($this->directory))throw new InvalidArgumentException('Le dossier des logos est indisponible.');
        $name='client_'.bin2hex(random_bytes(12)).'.'.$types[$mime];$target=$this->directory.'/'.$name;
        if(!move_uploaded_file((string)$file['tmp_name'],$target))throw new InvalidArgumentException('Impossible d’enregistrer le logo.');
        if($current&&str_starts_with($current,'photo/clients/')&&is_file(dirname($this->directory,2).'/'.$current))@unlink(dirname($this->directory,2).'/'.$current);
        return 'photo/clients/'.$name;
    }
}
