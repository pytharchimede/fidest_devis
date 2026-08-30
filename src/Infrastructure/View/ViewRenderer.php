<?php
declare(strict_types=1);
namespace App\Infrastructure\View;
use RuntimeException;
final class ViewRenderer
{
    public function __construct(private readonly string $root){}
    public function render(string $view,array $data=[]):string
    {
        $file=$this->root.'/'.str_replace(['..','\\'],['','/'],$view).'.php';if(!is_file($file))throw new RuntimeException('Vue introuvable : '.$view);
        extract($data,EXTR_SKIP);ob_start();require $file;return (string)ob_get_clean();
    }
}
