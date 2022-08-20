<?php

namespace Dowob\Refiner\Tests\TestClasses\Refiners;

use Dowob\Refiner\Definitions\Definition;
use Dowob\Refiner\Refiner;
use Illuminate\Contracts\Database\Query\Builder;

class TestModelValidatedRefiner extends Refiner
{
    public function definitions(): array
    {
        return [
            Definition::make('string1')->search()->validation('string'),
            Definition::make('string2')->search()->validation(['string']),
            Definition::make('integer-min5')->search()->validation(['integer', 'min:5']),
            Definition::make('multiple')->searchCustom(fn(Builder $builder, mixed $value) => null)
                ->validation([
                    'field1' => 'string',
                    'field2' => 'string',
                ]),
            Definition::make('invalid-multiple')->search()
                ->validation([
                    'field1' => 'string',
                    'field2' => 'string',
                ]),
        ];
    }
}
