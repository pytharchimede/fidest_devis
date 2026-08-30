<?php
declare(strict_types=1);namespace App\Application\User;
use RuntimeException;
final class SignatureUploader
{
    public function __construct(private readonly string $root){}
    public function store(int $userId,array $file,string $drawnData=''):?array
    {
        if($drawnData===''&&($file['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE)return null;$binary='';$mime='';
        if($drawnData!==''){if(!preg_match('#^data:image/png;base64,([A-Za-z0-9+/=]+)$#',$drawnData,$match))throw new RuntimeException('La signature dessinée est invalide.');$binary=base64_decode($match[1],true)?:'';$mime='image/png';}
        else{if(($file['error']??UPLOAD_ERR_OK)!==UPLOAD_ERR_OK)throw new RuntimeException('La signature n’a pas pu être importée.');if((int)($file['size']??0)>2*1024*1024)throw new RuntimeException('La signature dépasse 2 Mo.');$mime=(string)(new \finfo(FILEINFO_MIME_TYPE))->file((string)$file['tmp_name']);if(!in_array($mime,['image/png','image/jpeg','image/webp'],true))throw new RuntimeException('Utilisez une signature PNG, JPG ou WebP.');$binary=(string)file_get_contents((string)$file['tmp_name']);}
        if($binary===''||strlen($binary)>2*1024*1024)throw new RuntimeException('L’image de signature est vide ou trop volumineuse.');$directory=$this->root.'/photo/signatures';if(!is_dir($directory)&&!mkdir($directory,0775,true)&&!is_dir($directory))throw new RuntimeException('Le dossier des signatures est indisponible.');$extension=['image/png'=>'png','image/jpeg'=>'jpg','image/webp'=>'webp'][$mime];$relative='photo/signatures/signature_'.$userId.'_'.bin2hex(random_bytes(8)).'.'.$extension;if(file_put_contents($this->root.'/'.$relative,$binary,LOCK_EX)===false)throw new RuntimeException('La signature n’a pas pu être enregistrée.');return ['path'=>$relative,'mime'=>$mime];
    }
}
