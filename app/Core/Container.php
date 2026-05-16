<?php

declare(strict_types=1);

namespace App\Core;

use Closure;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use RuntimeException;

// контейнер зависимостей
class Container
{
    private array $bindings   = [];
    private array $instances  = [];
    private array $singletons = [];

    public function bind(string $abstract, Closure|string|null $concrete = null, bool $singleton = false): void
    {
        $this->bindings[$abstract]   = $concrete ?? $abstract;
        $this->singletons[$abstract] = $singleton;
    }

    public function singleton(string $abstract, Closure|string|null $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    // готовый экземпляр кладём как есть
    public function instance(string $abstract, object $instance): void
    {
        $this->instances[$abstract]  = $instance;
        $this->singletons[$abstract] = true;
    }

    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }

    public function make(string $abstract): object
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        $concrete = $this->bindings[$abstract] ?? $abstract;

        $object = $concrete instanceof Closure
            ? $concrete($this)
            : $this->build($concrete);

        if (!empty($this->singletons[$abstract])) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    // создаём через рефлексию
    public function build(string $concrete): object
    {
        if (!class_exists($concrete)) {
            throw new RuntimeException("Class {$concrete} does not exist.");
        }

        $ref = new ReflectionClass($concrete);

        if (!$ref->isInstantiable()) {
            throw new RuntimeException("Class {$concrete} is not instantiable.");
        }

        $ctor = $ref->getConstructor();
        if ($ctor === null) {
            return new $concrete();
        }

        $args = [];
        foreach ($ctor->getParameters() as $param) {
            $args[] = $this->resolveParam($param, $concrete);
        }

        return $ref->newInstanceArgs($args);
    }

    private function resolveParam(ReflectionParameter $param, string $owner): mixed
    {
        $type = $param->getType();

        // если зависимость это класс — резолвим из контейнера
        if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
            return $this->make($type->getName());
        }

        if ($param->isDefaultValueAvailable()) {
            return $param->getDefaultValue();
        }

        if ($param->allowsNull()) {
            return null;
        }

        throw new RuntimeException(
            "Cannot resolve \${$param->getName()} for {$owner}."
        );
    }
}
