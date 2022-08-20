<?php

namespace Dowob\Refiner\Facades;

use Dowob\Refiner\Registry as RegistryClass;
use Illuminate\Support\Facades\Facade;

class Registry extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return RegistryClass::class;
    }
}
