<?php

return [

    'oidc_provider' => env('BOOM_OIDC_PROVIDER'),
    'oidc_client_id' => env('BOOM_OIDC_CLIENT_ID'),
    'oidc_client_secret' => env('BOOM_OIDC_CLIENT_SECRET'),

    'oidc' => [
        'provider' => env('BOOM_OIDC_PROVIDER'),
        'client' => [
            'id' => env('BOOM_OIDC_CLIENT_ID'),
            'secret' => env('BOOM_OIDC_CLIENT_SECRET'),
        ],
    ],

    'licenses' => [

        '9789464421149' => [ 'nask1-vmbo', 'nask2-vmbo', ],
        '9789464421187' => [ 'nask1-vmbo', 'nask2-vmbo', ],

        '9789464421125' => [ 'natuurkunde-havo', 'natuurkunde-vwo', ],
        '9789464421163' => [ 'natuurkunde-havo', 'natuurkunde-vwo', ],

        '9789464421132' => [ 'scheikunde-havo', 'scheikunde-vwo', ],
        '9789464421170' => [ 'scheikunde-havo', 'scheikunde-vwo', ],

        '9789493113183' => [ 'docent', ],

        // test
        '9789492862815' => [
            'scheikunde-havo',
            'scheikunde-vwo',
            'docent',
        ],

    ],

];
