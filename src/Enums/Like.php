<?php

namespace Dowob\Refiner\Enums;

class Like
{
    public const BOTH = 'both';
    public const START = 'start';
    public const END = 'end';

    public const OPTIONS = [
        self::BOTH,
        self::START,
        self::END,
    ];
}
