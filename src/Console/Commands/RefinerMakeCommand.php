<?php

namespace Dowob\Refiner\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'make:refiner')]
class RefinerMakeCommand extends GeneratorCommand
{
    /**
     * @inheritDoc
     */
    protected $name = 'make:refiner';

    /**
     * @inheritDoc
     */
    protected $description = 'Create a new refiner class';

    /**
     * @inheritDoc
     */
    protected $type = 'Refiner';

    /**
     * @inheritDoc
     */
    protected function getStub()
    {
        return __DIR__ . '/../subs/refiner.stub';
    }

    /**
     * @inheritDoc
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\Refiners';
    }
}

