<?php

namespace Dowob\Refiner\Definitions;

use BadMethodCallException;
use Closure;
use Dowob\Refiner\Enums\Like;
use Dowob\Refiner\Filtering\ClosureFilter;
use Dowob\Refiner\Filtering\Filter;
use Dowob\Refiner\Filtering\WhereFilter;
use Dowob\Refiner\Filtering\WhereInFilter;
use Dowob\Refiner\Filtering\WhereLikeFilter;
use InvalidArgumentException;

/**
 * @phpstan-type SearchParameters array{closure?: Closure, like?: string}
 */
class Definition
{
    private ?string $column = null;

    private string $name;

    private bool $searchAlwaysRun = false;

    /**
     * @var class-string<Filter>|null
     */
    private ?string $searchType = null;

    /**
     * @var SearchParameters
     */
    private array $searchParameters = [];

    private bool $searchTrim = true;

    private bool $sort = false;

    private array $validationRules = [];

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public static function make(string $name): self
    {
        return new self($name);
    }

    /**
     * (Search) Toggle whether this search definition will always be run,
     * even if the search parameter is not present.
     *
     * @param bool $alwaysRun
     * @return $this
     */
    public function alwaysRun(bool $alwaysRun = true): static
    {
        $this->searchAlwaysRun = $alwaysRun;

        return $this;
    }

    /**
     * This overrides the column name, otherwise the column name would be determined
     * from the name provided to the definition. For example, you may name the
     * definition `created` but want it to map to the database column `created_at`.
     *
     * @param string|null $column
     * @return $this
     */
    public function column(?string $column): static
    {
        /*
         * todo: do we want to support different column for search & sort?
         *       It could be done by different definitions.
         */

        $this->column = $column;

        return $this;
    }

    /**
     * Use to enable search & use exact match search: WHERE column = value
     *
     * @return $this
     */
    public function search(): static
    {
        $this->setSearch(WhereFilter::class);

        return $this;
    }

    /**
     * Use to enable search & to provide a custom search callback.
     *
     * @param Closure $closure
     * @return $this
     */
    public function searchCustom(Closure $closure): static
    {
        $this->setSearch(ClosureFilter::class, compact('closure'));

        return $this;
    }

    /**
     * Use to enable search & use an IN search: WHERE column IN (... values ...)
     * Search parameter can contain multiple values, i.e. search[name][]=Alan&search[name][]=Bob
     *
     * @return $this
     */
    public function searchIn(): static
    {
        $this->setSearch(WhereInFilter::class);

        return $this;
    }

    /**
     * Use to enable search & use an IN search: WHERE column LIKE %value%
     * The `$option` parameter can one of Like::BOTH, Like::START, Like::END
     *
     * @param value-of<Like::OPTIONS> $like
     * @return $this
     */
    public function searchLike(string $like = Like::BOTH): static
    {
        if (!in_array($like, Like::OPTIONS)) {
            throw new InvalidArgumentException('The LIKE option must be a valid choice from \\Dowob\\Refiner\\Enums\\Like');
        }

        $this->setSearch(WhereLikeFilter::class, compact('like'));

        return $this;
    }

    /**
     * Toggle whether this definition can be used for sorts
     *
     * @param bool $allow
     * @return $this
     */
    public function sort(bool $allow = true): static
    {
        $this->sort = $allow;

        return $this;
    }

    /**
     * (Search) Toggle whether this search definition will trim the input
     * when passed to the search query. This is enabled by default.
     *
     * @param bool $enable
     * @return $this
     */
    public function trim(bool $enable = true): static
    {
        $this->searchTrim = $enable;

        return $this;
    }

    /**
     * @param string|array $rules
     * @return $this
     */
    public function validation(string|array $rules = []): static
    {
        // todo: auto-add required/nullable to parameters?
        if (is_array($rules)) {
            $key = array_key_first($rules);

            if ($key !== null && !is_string($key)) {
                $rules = [$this->name() => $rules];
            }
        } else {
            $rules = [$this->name() => $rules];
        }

        $this->validationRules = $rules;

        return $this;
    }

    public function canSearch(): bool
    {
        return $this->searchType !== null;
    }

    public function canAlwaysRun(): bool
    {
        return $this->searchAlwaysRun;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function getColumn(): string
    {
        return $this->column ?? $this->name;
    }

    public function isCustomSearch(): bool
    {
        return $this->searchType === ClosureFilter::class;
    }

    public function isMultipleAllowed(): bool
    {
        return $this->searchType === WhereInFilter::class;
    }

    public function canSort(): bool
    {
        return $this->sort;
    }

    public function shouldTrimSearch(): bool
    {
        return $this->searchTrim;
    }

    /**
     * @return class-string<Filter>
     */
    public function getSearchFilterClass(): string
    {
        if ($this->searchType === null) {
            throw new BadMethodCallException('You cannot fetch the search filter on a definition that does not have one defined.');
        }

        return $this->searchType;
    }

    /**
     * @return SearchParameters
     */
    public function getSearchFilterParameters(): array
    {
        return $this->searchParameters;
    }

    /**
     * @return array<string, string>|array<string, array[]>
     */
    public function getValidationRules(): array
    {
        if (!$this->canSearch()) {
            return [];
        }

        return !empty($this->validationRules)
            ? $this->validationRules
            : [
                $this->name() => array_filter([
                    $this->canAlwaysRun() ? 'nullable' : 'required',
                    $this->isMultipleAllowed() ? 'array' : null,
                ]),
            ];
    }

    /**
     * @param class-string<Filter>|null $filter
     * @param array $parameters
     * @return void
     */
    private function setSearch(?string $filter = null, array $parameters = []): void
    {
        $this->searchType = $filter;
        $this->searchParameters = $parameters;
    }
}
