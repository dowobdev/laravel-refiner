<?php

namespace Dowob\Refiner\Tests\TestClasses\Refiners;

use Dowob\Refiner\Definitions\Definition;
use Dowob\Refiner\Enums\Like;
use Dowob\Refiner\Refiner;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Str;

class TestModelRefiner extends Refiner
{
    public function definitions(): array
    {
        return [
            Definition::make('user')->searchIn()->column('id'),
            Definition::make('name')->search()->sort(),
            Definition::make('created')->sort()->column('created_at'),

            // Custom search options - not legitimate columns in test database
            Definition::make('email-domain')->searchCustom(function (Builder $query, mixed $value) {
                $query->where('email', 'like', '%' . Str::start($value, '@'));
            }),
            Definition::make('name-or-email')->searchCustom(function (Builder $query, mixed $value) {
                $query->whereNameOrEmailLike($value);
            }),

            // Like options - not legitimate columns in test database
            Definition::make('like-both')->searchLike(),
            Definition::make('like-start')->searchLike(Like::START),
            Definition::make('like-end')->searchLike(Like::END),
        ];
    }
}
