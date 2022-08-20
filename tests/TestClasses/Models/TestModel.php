<?php

namespace Dowob\Refiner\Tests\TestClasses\Models;

use Dowob\Refiner\Refinable;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Model;

class TestModel extends Model
{
    use Refinable;

    protected $guarded = [];

    public function scopeWhereNameOrEmailLike(Builder $query, mixed $value): Builder
    {
        return $query->where('name', 'like', '%' . $value . '%')
            ->orWhere('email', 'like', '%' . $value . '%');
    }
}
