<?php

namespace Dowob\Refiner\Filtering;

use Dowob\Refiner\Definitions\Definition;
use Illuminate\Contracts\Database\Query\Builder;

abstract class Filter
{
    protected Definition $definition;
    protected mixed $value;

    public function __construct(Definition $definition, mixed $value)
    {
        $this->definition = $definition;
        $this->value = $value;
    }

    public abstract function apply(Builder $query): void;

    public function value(): mixed
    {
        return $this->value;
    }
}
