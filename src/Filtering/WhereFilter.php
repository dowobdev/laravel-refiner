<?php

namespace Dowob\Refiner\Filtering;

use Illuminate\Contracts\Database\Query\Builder;

class WhereFilter extends Filter
{
    public function apply(Builder $query): void
    {
        $query->where($this->definition->getColumn(), $this->value);
    }
}
