<?php
declare(strict_types=1);

namespace App\Infrastructure\Container;

use ReflectionClass;
use RuntimeException;

final class Container
{
    private array $bindings = [];
    private array $instances = [];

    public function singleton(string $id, callable|object $factory): self { $this->bindings[$id] = $factory; return $this; }

    public function get(string $id): object
    {
        if (isset($this->instances[$id])) { return $this->instances[$id]; }
        if (isset($this->bindings[$id])) {
            $binding = $this->bindings[$id];
            return $this->instances[$id] = is_callable($binding) ? $binding($this) : $binding;
        }
        if (!class_exists($id)) { throw new RuntimeException('Service introuvable : ' . $id); }
        $reflection = new ReflectionClass($id);
        $constructor = $reflection->getConstructor();
        if ($constructor === null) { return $this->instances[$id] = $reflection->newInstance(); }
        $arguments = [];
        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();
            if (!$type instanceof \ReflectionNamedType || $type->isBuiltin()) {
                throw new RuntimeException('Dépendance non résolue : ' . $id . '::$' . $parameter->getName());
            }
            $arguments[] = $this->get($type->getName());
        }
        return $this->instances[$id] = $reflection->newInstanceArgs($arguments);
    }
}
