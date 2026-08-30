<?php
declare(strict_types=1);
namespace App\Infrastructure\Database;
use PDO;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
final class Migrator
{
    public function __construct(private readonly PDO $database,private readonly string $directory){}
    public function run():void
    {
        $this->database->exec('CREATE TABLE IF NOT EXISTS app_migrations (migration VARCHAR(255) NOT NULL PRIMARY KEY,applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        $applied=array_flip($this->database->query('SELECT migration FROM app_migrations')->fetchAll(PDO::FETCH_COLUMN));$files=[];
        $iterator=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->directory,\FilesystemIterator::SKIP_DOTS));
        foreach($iterator as $file){if($file->isFile()&&in_array($file->getExtension(),['sql','php'],true))$files[]=$file->getPathname();}sort($files,SORT_STRING);
        foreach($files as $file){
            $name=str_replace('\\','/',substr($file,strlen($this->directory)+1));$legacy=basename($file);if(isset($applied[$name])||isset($applied[$legacy]))continue;
            if(pathinfo($file,PATHINFO_EXTENSION)==='php'){$migration=require $file;if(is_callable($migration))$migration($this->database);}
            else{$sql=trim((string)file_get_contents($file));foreach(preg_split('/;\s*(?:\R|$)/',$sql)?:[] as $statement){if(trim($statement)!=='')$this->database->exec(trim($statement));}}
            $statement=$this->database->prepare('INSERT INTO app_migrations (migration) VALUES (:migration)');$statement->execute(['migration'=>$name]);
        }
    }
}
