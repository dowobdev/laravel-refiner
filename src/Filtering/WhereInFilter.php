<?php

namespace Dowob\Refiner\Filtering;

use Illuminate\Contracts\Database\Query\Builder;

class WhereInFilter extends Filter
{
    public function apply(Builder $query): void
    {
        $query->whereIn($this->definition->getColumn(), $this->value);
    }
}
