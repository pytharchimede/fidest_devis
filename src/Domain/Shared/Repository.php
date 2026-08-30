<?php
declare(strict_types=1);
namespace App\Domain\Shared;
use PDO;
abstract class Repository
{
    public function __construct(protected readonly PDO $database){}
    abstract protected function table():string;
    public function find(int $id):?array{$statement=$this->database->prepare('SELECT * FROM '.$this->table().' WHERE id=:id');$statement->execute(['id'=>$id]);return $statement->fetch()?:null;}
    public function all(string $order='id DESC'):array{return $this->database->query('SELECT * FROM '.$this->table().' ORDER BY '.$order)->fetchAll();}
}
