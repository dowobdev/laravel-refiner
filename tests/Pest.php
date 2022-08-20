<?php

use Dowob\Refiner\Tests\TestCase;
use Illuminate\Http\Request;

uses(TestCase::class)->in(__DIR__);

function createBaseRequest(string $key, array|string $field, mixed $value = null): Request
{
    $params = is_array($field) ? $field : [$field => $value];

    return new Request([
        $key => $params,
    ]);
}

function createSearchRequest(array|string $field, mixed $value = null): Request
{
    return createBaseRequest('search', $field, $value);
}

function createSortRequest(array|string $field, ?string $direction = null): Request
{
    return createBaseRequest('sort', $field, $direction);
}
