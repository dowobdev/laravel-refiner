<?php

namespace Dowob\Refiner;

use Illuminate\Support\Collection;

/**
 * @phpstan-type RegisteredRefiner array{refiner: Refiner, refinerClass: string|null, model: string|null}
 */
class Registry
{
    /**
     * @var Collection<RegisteredRefiner>
     */
    private Collection $refiners;

    public function __construct()
    {
        $this->refiners = collect();
    }

    public function all(): Collection
    {
        return $this->refiners;
    }

    public function pop(?string $refiner = null, ?string $model = null): ?Refiner
    {
        return $this->take($refiner, $model, false);
    }

    public function push(Refiner $refiner, ?string $model = null): static
    {
        $this->refiners->push([
            'refiner'      => $refiner,
            'refinerClass' => get_class($refiner),
            'model'        => $model,
        ]);

        return $this;
    }

    public function shift(?string $refiner = null, ?string $model = null): ?Refiner
    {
        return $this->take($refiner, $model, true);
    }

    private function take(?string $refiner, ?string $model, bool $first): ?Refiner
    {
        $key = $this->refiners
            ->when($refiner, function ($collection, $value) {
                return $collection->where('refinerClass', $value);
            })
            ->when($model, function ($collection, $value) {
                return $collection->where('model', $value);
            })
            ->keys();

        if ($key->isEmpty()) {
            return null;
        }

        $key = $first ? $key->first() : $key->last();

        /** @var RegisteredRefiner $match */
        $match = $this->refiners->get($key);
        $this->refiners = $this->refiners->except($key);

        return $match['refiner'];
    }
}
