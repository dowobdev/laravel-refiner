<?php

namespace Dowob\Refiner;

use Dowob\Refiner\Definitions\Definition;
use Dowob\Refiner\Filtering\Search;
use Dowob\Refiner\Sorting\Sort;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

class RefinedRequest
{
    private array $keys;

    /**
     * @var Collection<Search>
     */
    private Collection $searches;

    /**
     * @var Collection<Sort>
     */
    private Collection $sorts;

    public function __construct(Request $request, array $definitions, array $defaultSorts = [])
    {
        $definitions = collect($definitions)
            ->keyBy(function (Definition $definition) {
                return $definition->name();
            });

        $this->keys = [
            'search' => config('refiner.parameters.search'),
            'sort'   => config('refiner.parameters.sort'),
        ];

        $this->searches = $this->identifySearches($request, $definitions);
        $this->sorts = $this->identifySorts($request, $definitions, $defaultSorts);
    }

    public function getSearch(string $name): ?Search
    {
        return $this->searches->get($name);
    }

    public function getSort(string $name): ?Sort
    {
        return $this->sorts->get($name);
    }

    public function query(): array
    {
        return array_filter([
            $this->keys['search'] => $this->searches
                ->mapWithKeys(function (Search $search) {
                    return [$search->name() => $search->value()];
                })
                ->all(),
            $this->keys['sort']   => $this->sorts
                ->mapWithKeys(function (Sort $sort) {
                    return [$sort->name() => $sort->direction()];
                })
                ->all(),
        ]);
    }

    /**
     * @return Collection<Search>
     */
    public function searches(): Collection
    {
        return $this->searches;
    }

    /**
     * @return Collection<Sort>
     */
    public function sorts(): Collection
    {
        return $this->sorts;
    }

    private function identifySearches(Request $request, Collection $definitions): Collection
    {
        $validator = Validator::make(
            $request->input($this->keys['search'], []),
            $definitions->flatMap->getValidationRules()->all()
        );

        $valid = $validator->valid();

        return $definitions
            ->map(function (Definition $definition) use ($valid) {
                if (!$definition->canSearch()) {
                    return false;
                }

                $value = array_intersect_key($valid, $rules = $definition->getValidationRules());
                if (empty($value)) {
                    return $this->invalidSearch($definition);
                }

                $value = count($rules) > 1 ? $value : array_shift($value);
                if (is_array($value) && !($definition->isMultipleAllowed() || $definition->isCustomSearch())) {
                    return $this->invalidSearch($definition);
                }

                return $this->validSearch($definition, $value);
            })
            ->filter()
            ->keyBy->name();
    }

    private function invalidSearch(Definition $definition): bool|Search
    {
        if ($definition->canAlwaysRun()) {
            return $this->validSearch($definition, null);
        }

        return false;
    }

    private function validSearch(Definition $definition, mixed $value): Search
    {
        return new Search($definition, $value);
    }

    private function identifySorts(Request $request, Collection $definitions, array $defaultSorts): Collection
    {
        $sorts = collect($request->input($this->keys['sort'], []))
            ->map(function ($direction, $name) use ($definitions) {
                return $this->identifySort($definitions, $name, $direction);
            })
            ->filter();

        if ($sorts->isEmpty()) {
            $sorts = collect($defaultSorts)
                ->map(function ($sort) use ($definitions) {
                    return $this->identifySort($definitions, $sort[0], $sort[1] ?? Enums\Sort::ASC);
                })
                ->filter();
        }

        return $sorts->keyBy->name();
    }

    private function identifySort(Collection $definitions, mixed $name, mixed $direction): bool|Sort
    {
        $direction = strtolower($direction);
        $name = strtolower($name);

        /** @var null|Definition $definition */
        $definition = $definitions->get($name);

        if (!$definition) {
            return false;
        }

        if (!$definition->canSort()) {
            return false;
        }

        // todo: should we raise exception here, or silently discard?
        if ($direction !== Enums\Sort::ASC && $direction !== Enums\Sort::DESC) {
            return false;
        }

        return new Sort($definition, $direction);
    }
}
