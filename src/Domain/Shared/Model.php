<?php
declare(strict_types=1);

namespace App\Domain\Shared;

abstract class Model
{
    public function __construct(protected array $attributes = []) {}
    public function id(): ?int { $value=$this->attributes[$this->primaryKey()]??null; return $value===null?null:(int)$value; }
    public function get(string $key,mixed $default=null):mixed{return $this->attributes[$key]??$default;}
    public function toArray():array{return $this->attributes;}
    abstract protected function primaryKey():string;
}
