<?php

namespace Dowob\Refiner\Tests\TestClasses\Refiners;

use Dowob\Refiner\Definitions\Definition;
use Dowob\Refiner\Refiner;

class TestModelRefinerWithDefaultSort extends Refiner
{
    public function definitions(): array
    {
        return [
            Definition::make('name')->sort(),
        ];
    }

    public function defaultSorts(): array
    {
        return [
            ['name', 'desc'],
        ];
    }
}
