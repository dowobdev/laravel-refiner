<?php

namespace Dowob\Refiner\Tests\TestClasses\Refiners;

use Dowob\Refiner\Definitions\Definition;
use Dowob\Refiner\Refiner;
use Illuminate\Contracts\Database\Eloquent\Builder;

class TestModelRefinerWithSearchAlwaysRan extends Refiner
{
    public function definitions(): array
    {
        return [
            Definition::make('name')
                ->alwaysRun()
                ->searchCustom(function (Builder $query, mixed $values) {
                    if (empty($values)) {
                        $query->where('name', 'Default');

                        return;
                    }

                    $query->where('name', $values);
                }),
        ];
    }
}
