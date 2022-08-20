<?php

namespace Dowob\Refiner\Filtering;

use Dowob\Refiner\Enums\Like;
use Illuminate\Contracts\Database\Query\Builder;

class WhereLikeFilter extends Filter
{
    public function apply(Builder $query): void
    {
        $like = $this->definition->getSearchFilterParameters()['like'] ?? null;

        $value = match ($like) {
            Like::END   => $this->value . '%',
            Like::START => '%' . $this->value,
            default     => '%' . $this->value . '%',
        };

        $query->where($this->definition->getColumn(), 'like', $value);
    }
}
