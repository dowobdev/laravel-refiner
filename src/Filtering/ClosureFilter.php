<?php

namespace Dowob\Refiner\Filtering;

use BadMethodCallException;
use Illuminate\Contracts\Database\Query\Builder;

class ClosureFilter extends Filter
{
    public function apply(Builder $query): void
    {
        $closure = $this->definition->getSearchFilterParameters()['closure'] ?? null;
        if ($closure === null) {
            throw new BadMethodCallException('The closure must be defined for a custom search action.');
        }

        $closure($query, $this->value);
    }
}
