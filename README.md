# Refine Laravel Eloquent queries

The aim of the package is to provide simple yet flexible filtering to your Eloquent models from query parameters in the
request. You create a refining class that extends `\Dowob\Refiner\Refiner` and specify the filter definitions that are
applicable to that refiner. This allows refiners to be re-used throughout your application.

Please read through the [Caveats](#caveats) section to ensure this package is suitable for you. This was built to meet
my needs which means it won't fit all use cases.

## Installation

Run the following to install the package with composer:

```bash
composer require dowob/laravel-refiner
```

Some minimal configuration options are available if you wish to publish the configuration file. The service provide
should be registered automatically within Laravel.

```bash
php artisan vendor:publish --provider="Dowob\Refiner\RefinerServiceProvider" --tag="refiner-config"
```

## Refinable Models

A model must use the trait `\Dowob\Refiner\Refinable` to enable refinement on the model.

```php
use \Dowob\Refiner\Refinable;
use \Illuminate\Database\Eloquent\Model;

class Post extends Model {
    use Refinable;
    ...
}
```

### Refining a model

You can then refine the query by calling `refine()`:

```php
Post::refine()->get();
```

As `refine()` is a scope, it can be combined with any other calls on the model (scopes, query builder methods,
pagination etc.).

The `refine()` method has two optional arguments:

1. `?\Dowob\Refiner\Refiner $refiner = null` - if specified, this is the refiner instance you want to use for this
   refinement. If not specified, it will be automatically determined from the model name and the configured refiner
   namespace (defaults to `\App\Refiners`). For example, the guessed refiner `Post` would be `\App\Refiners\PostRefiner`
   .
2. `?\Illuminate\Http\Request $request = null` - if specified, this will be used as the request object to retrieve
   query parameters from. Otherwise, we'll use the current request to retrieve the parameters.

## Refiners

A refiner defines what refinement is allowed (filters & sorting). This is achieved by specifying
**[Definitions](#definitions)** in a refiner. The refiner will be automatically used if it matches the naming
convention of
`ModelNameRefiner`, i.e. `PostRefiner`, unless you have specifically passed an instance of a refiner to the `refine()
` method.

A refiner is typically for a specific model, but you may want to create multiple refiners for a model too. For example,
you may want to use a `UserPostRefiner` for when a user is viewing/filtering their specific posts, and you may have
a `PostRefiner` that is for anyone to search any posts.

### Creating a Refiner

Generate a refiner using the artisan command `php artisan make:refiner NameOfYourRefiner`.

## Definitions

To make use of a refiner, you'll want to add one or more Definitions to it in the `definitions()` method. Each
definition must have a `name` (passed in the `make` method), but everything else is optional. The `name` is how the
definition matches up to the request, for example a definition with a name of `email` would be triggered by a
request like: `?search[email]=value`. The `name` will also be used for the query column, unless you define a
different column through `column()`.

You must opt the Definition into search and/or sorting, which is done by calling one of the `search*` methods or the
`sort` method.

You can specify [validation](#validation) rules for each definition which allows further complexity in how your
definitions work.

Examples of definitions:

```php
use \Dowob\Refiner\Definitions\Definition;
use \Dowob\Refiner\Enums\Like;
use \Illuminate\Support\Facades\DB;

...

public function definitions(): array
{
    return [
        // This allows exact (column = value) matching for `name` and enables sorting
        Definition::make('name')->search()->sort(),
        // This allows LIKE matching without sorting. By default, LIKE sorting will use Like::BOTH however,
        // you can override this by passing a Like::* value as the first parameter as shown in 2nd example.
        Definition::make('email')->searchLike(), // LIKE %value%
        Definition::make('email-match-after')->searchLike(Like::END), // LIKE value%
        // You can specify a different column name to use rather than the name used in the request parameter.
        // This also demonstrates support for WHERE column IN (...) type searches
        Definition::make('type')->column('account_type')->searchIn(),
        // If a pre-defined search option just doesn't cut it for you, you can specify a custom callback
        // that will be used for the search.
        Definition::make('full-name')->searchCustom(function (Builder $query, mixed $value) {
            $query->where(DB::raw('CONCAT(first_name, last_name)'), 'like', '%' . $value . '%');
        }),
        // It can also access any scopes etc. as you normally would be able to on the model
        Definition::make('model-scope')->searchCustom(function (Builder $query, mixed $value) {
            $query->aScopeOnTheModel($value);
        }),
        // You may want a search to always be applied, even if the search value isn't present in the query.
        // You can do this by specifying `alwaysRun` like so. Without the `alwaysRun`, this query would
        // not apply the `where('active', 1)` if there's no search for `active` present in the request!
        Definition::make('active')->alwaysRun()->searchCustom(function (Builder $query, mixed $value) {
            switch ($value) {
                case 'inactive':
                    $query->where('active', 0);
                    break;
                case 'all':
                    // No action, show both active & inactive
                    break;
                default:
                    // Reached either by no value being specified, or it being 'active'
                    // or anything that does not match the above
                    $query->where('active', 1);
            }
        }),
    ];
}
```

### Validation

You can add validation rules to a definition by calling `validation($rules)`. By default, basic validation rules
will be added automatically depending on how the definition is configured:

- `required` unless the definition is set to always run, in which case it would have `nullable`
- `array` if using a `searchIn()` search filter.

Validation rules use the power of Laravel's validation system:

```php
use \Dowob\Refiner\Definitions\Definition;

// Three ways of specifying the validation rules that result in same validation
Definition::make('email')->search()->validation(['required', 'string', 'email']);
Definition::make('email')->search()->validation('required|string|email');
Definition::make('email')->search()->validation(['email' => ['required', 'string', 'email']);
```

If specifying a custom search handler via `searchCustom($closure)`, you can unlock more powerful use cases for
search filters. For example, if you had a date filter that needed to rely on **two** fields (`start` and `end`) within
one definition, you can do:

```php

use \Dowob\Refiner\Definitions\Definition;
use \Illuminate\Contracts\Database\Query\Builder;

// A definition using a custom search that can handle multiple query values due to its validation rules.
Definition::make('date')
    ->validation([
        'start' => [
            'required',
            'date_format:Y-m-d',
        ],
        'end' => [
            'required',
            'date_format:Y-m-d',
        ],
    ])
    ->searchCustom(function (Builder $builder, mixed $value) {
        // $value will contain at least one of `start` or `end` if they passed validation
        if (empty($value['start'])) {
            return $query->where('created_at', '=<', $value['end']);        
        }
        
        if (empty($value['end'])) {
            return $query->where('created_at', '>=', $value['start']);        
        }
        
        // Value contains both 'start' and 'end'
        $query->whereBetween('created_at', $value);
    });
```

> Note: It's important to know that multiple fields will not work unless you're using `searchCustom` or `searchIn`,
> as array values are rejected for other search types to prevent array values being passed to methods expecting
> singular values (i.e. exact match search).

### Default sorts

You may want to apply default sorting to your query when there are no sorts specified in the request. This can be
done by specifying an array within the `defaultSorts` method, containing one or more sorts to apply if no sorts are
present.

```php
use \Dowob\Refiner\Enums\Sort;

...

public function defaultSorts(): array
{
    return [
        // The first value in the array must match the relevant sort-enabled definition
        // The second value is the sort direction to use as default.
        ['last_name', Sort::ASC],
        ['first_name', SORT::ASC],
    ];
}
```

## Refiner Registry

When a refiner is created due to no refiner being passed to `refine()`, the package registers it in the registry for
you to retrieve (if needed). If you don't need the refiner instance, you can ignore the registry. You may need the
refiner instance if you wish to check what sorts are active in the current request (i.e. for inverting sort links
based on current sort), or what searches are active & the values that were used (i.e. for pre-filling forms), or
using the `query()` method to retrieve the validated query parameters (i.e. for pagination `->appends()` method).

> Note: The refiner is a singleton, which means you may need to reset its state in long-running applications serving
> multiple requests like those running on Laravel Octane.

Refiners are added to the registry in the order they are registered via `refine()`, and when retrieving a refiner it
is removed from the registry.

You can use the Facade to access the singleton: `\Dowob\Refiner\Facades\Registry`.

The below methods are the ones you'll need to know if using the registry.

```php
use \Dowob\Refiner\Facades\Registry;

// Take the refiner from the end of the registry (last in)
Registry::pop();
// Take the refiner from front of the registry (first in)
Registry::shift();

// Example
User::refine();
Post::refine();
Registry::shift(); // returns the UserRefiner
Registry::shift(); // returns the PostRefiner, as we've already shifted the UserRefiner out of registry.

```

Both `pop` and `shift` accept two optional parameters, the class-name of a `Refiner` and the class-name of a `Model`.
Passing either of these parameters will filter the `pop` or `shift` action to only refiners registered for that
match those parameters specified.

Example of using optional parameters for registry methods:

```php
// Example of registry setup in this order,
// each 'we can retrieve it by' assumes no prior models are retrieved in the example.
// 1st registered, 1st UserRefiner/User registered
User::refine();
// We can retrieve it by...
// - Registry::shift()
// - or Registry::shift(UserRefiner::class);
// - or Registry::shift(model: User::class);
// 
// 2nd registered, 1st PostRefiner/Post registered
Post::refine();
// We can retrieve it by...
// - Registry::shift(PostRefiner::class)
// - or Registry::shift(model: Post::class);
//
// 3rd registered, 2nd UserRefiner/User registered
User::fine();
// We can retrieve it by...
// - Registry::pop(UserRefiner::class)
// - or Registry::pop(model: User::class);
// 
// 4th registered, 2nd PostRefiner/Post registered
Post::fine();
// We can retrieve it by...
// - Registry::pop()
// - or Registry::pop(PostRefiner::class)
// - or Registry::pop(model: Post::class);
```

## Caveats

1. This package does **NOT** support filter combinations other than AND, unless they are within a custom search filter
   (but that would be AND'd with any other active filters).
2. Whilst multiple refiners in a single request is supported, if the definitions have conflicting names then you may
   get unexpected results (as both refiners would, if the data validates to their definitions, use them).
    1. Definition names within a refiner must be unique, this will likely be enforced in a later version.
3. If validating multiple fields in one definition, you must pass the definition name to `getSearchValue($name)`
   which will return an array of values for any fields that passed validation in that definition. You cannot call
   the fields by their name in `getSearchValue($name)` but could do something
   like `getSearchValue($name)[$field] ?? null`

## TODO

- [ ] Refining
    - [ ] search via basic relationship `whereHas` to reduce need for `searchCustom` callbacks that achieve the same
      result
        - [ ] potential grouping of same relationship queries for better performance
