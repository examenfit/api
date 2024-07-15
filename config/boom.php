<?php

return [

    'oidc' => [
        'provider' => env('BOOM_OIDC_PROVIDER'),
        'client' => [
            'id' => env('BOOM_OIDC_CLIENT_ID'),
            'secret' => env('BOOM_OIDC_CLIENT_SECRET'),
        ],
    ],

    'licenses' => [

        '9789464421125' => [ 'until' => '2025-08-01', 'natuurkunde-havo', 'natuurkunde-vwo', ],
        '9789464421132' => [ 'until' => '2025-08-01', 'scheikunde-havo', 'scheikunde-vwo', ],
        '9789464421149' => [ 'until' => '2025-08-01', 'nask1-vmbo', 'nask2-vmbo', ],
        '9789464421163' => [ 'until' => '2025-08-01', 'natuurkunde-havo', 'natuurkunde-vwo', ],
        '9789464421170' => [ 'until' => '2025-08-01', 'scheikunde-havo', 'scheikunde-vwo', ],
        '9789464421187' => [ 'until' => '2025-08-01', 'nask1-vmbo', 'nask2-vmbo', ],

        '9789493113183' => [
            'role' => 'docent',
            'until' => '2025-08-01',
            'nask1-vmbo',
            'nask2-vmbo',
            'natuurkunde-havo',
            'natuurkunde-vwo',
            'scheikunde-havo',
            'scheikunde-vwo',
        ],

// test
//        '9789492862815' => [
//            'role' => 'docent',
//            'until' => '2025-08-01',
//            'scheikunde-havo',
//        ],

    ],

];
