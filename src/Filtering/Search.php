<?php

namespace Dowob\Refiner\Filtering;

use Dowob\Refiner\Definitions\Definition;
use Illuminate\Contracts\Database\Query\Builder;

class Search
{
    private Definition $definition;
    private Filter $filter;

    public function __construct(Definition $definition, mixed $value)
    {
        $this->definition = $definition;
        $this->filter = $this->filter($value);
    }

    public function apply(Builder $query): void
    {
        $this->filter->apply($query);
    }

    public function name(): string
    {
        return $this->definition->name();
    }

    public function value(): mixed
    {
        return $this->filter->value();
    }

    private function filter(mixed $value): Filter
    {
        $filterClass = $this->definition->getSearchFilterClass();

        return new $filterClass($this->definition, $value);
    }
}
