<?php

namespace Dowob\Refiner;

use Dowob\Refiner\Facades\Registry as RefinerRegistryFacade;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Http\Request;

trait Refinable
{
    public function scopeRefine(Builder $query, ?Refiner $refiner = null, ?Request $request = null): Builder
    {
        if ($refiner === null) {
            $refiner = new (Refiner::defaultRefinerFor($this));
            RefinerRegistryFacade::push($refiner, static::class);
        }

        $request ??= request();

        return $refiner->apply($query, $request);
    }
}
