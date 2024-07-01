<?php
return [
    'oidc_api' => env('BOOM_OIDC_API'),
    'oidc_provider' => env('BOOM_OIDC_PROVIDER'),
    'oidc_client_id' => env('BOOM_OIDC_CLIENT_ID'),
    'oidc_client_secret' => env('BOOM_OIDC_CLIENT_SECRET'),
    'licenses' => [
        '9789492862815' => [
            [ 'role' => 'docent', 'stream' => 5 ],
        ],
    ],
];
