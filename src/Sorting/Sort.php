<?php

namespace Dowob\Refiner\Sorting;

use Dowob\Refiner\Definitions\Definition;
use Dowob\Refiner\Enums\Sort as SortEnum;
use Illuminate\Contracts\Database\Query\Builder;

class Sort
{
    private Definition $definition;
    private string $direction;

    public function __construct(Definition $definition, string $direction)
    {
        $this->definition = $definition;
        $this->direction = $direction;
    }

    public function apply(Builder $query): void
    {
        $query->orderBy($this->definition->getColumn(), $this->direction);
    }

    public function direction(): string
    {
        return $this->direction;
    }

    public function inverseDirection(): string
    {
        return $this->direction === SortEnum::ASC ? SortEnum::DESC : SortEnum::ASC;
    }

    public function name(): string
    {
        return $this->definition->name();
    }
}
