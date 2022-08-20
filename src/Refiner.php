<?php

namespace Dowob\Refiner;

use BadMethodCallException;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

abstract class Refiner
{
    private RefinedRequest $refinedRequest;

    /*
     * todo: add query method to retrieve the query parameters (via refinedRequest) that were validated/cleaned.
     *       No need to provide default as they are... default
     */

    public static function defaultRefinerFor(string|Model $model): string
    {
        return Str::finish(config('refiner.namespace', '\\App\\Refiners\\'), '\\')
            . class_basename($model)
            . 'Refiner';
    }

    public function apply(Builder $query, Request $request): Builder
    {
        $this->refinedRequest = new RefinedRequest($request, $this->definitions(), $this->defaultSorts());

        $this->refinedRequest->searches()->each->apply($query);
        $this->refinedRequest->sorts()->each->apply($query);

        return $query;
    }

    public abstract function definitions(): array;

    public function defaultSorts(): array
    {
        return [];
    }

    public function hasSearch(string $name): bool
    {
        return $this->refinedRequest()->getSearch($name) !== null;
    }

    public function getSearchValue(string $name): mixed
    {
        return $this->refinedRequest()->getSearch($name)?->value();
    }

    public function hasSort(string $name): bool
    {
        return $this->refinedRequest()->getSort($name) !== null;
    }

    public function getSortDirection(string $name): ?string
    {
        return $this->refinedRequest()->getSort($name)?->direction();
    }

    public function getInverseSortDirection(string $name): ?string
    {
        return $this->refinedRequest()->getSort($name)?->inverseDirection();
    }

    /**
     * @return array<string,mixed>
     */
    public function query(): array
    {
        return $this->refinedRequest()->query();
    }

    private function refinedRequest(): RefinedRequest
    {
        if (!isset($this->refinedRequest)) {
            throw new BadMethodCallException('The refiner has not been instantiated with request data yet.');
        }

        return $this->refinedRequest;
    }
}
