<?php

return [
    /**
     * Defines the location/namespace that is used when refiner-guessing is used
     * and for any refiner generation commands.
     */
    'namespace' => '\\App\\Refiners',

    'parameters' => [
        /**
         * The query parameter that will contain search filters to be checked against the refiner definitions.
         * For example: 'search' means /url?search[name]=David&search[job]=Developer
         * It must have something specified and cannot be empty.
         */
        'search' => 'search',

        /**
         * The query parameter that will contain sort filters to be checked against the refiner definitions.
         * For example: 'sort' means /url?sort[name]=asc&sort[job]=desc
         * It must have something specified and cannot be empty.
         */
        'sort'   => 'sort',
    ],
];
