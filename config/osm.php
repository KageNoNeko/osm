<?php

return [

    'default' => env('OSM_CONNECTION', 'overpass'),

    'connections' => [

        'overpass' => [
            'driver' => 'overpass',
            'interpreter' => env('OSM_INTERPRETER', 'localhost'),
        ],
    ],
];
