<?php

return [
    'defaults' => [
        'guard' => 'web',
        'passwords' => 'users',
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
        'admin' => [
            'driver' => 'session',
            'provider' => 'admins',
        ],
        'merchant' => [
            'driver' => 'session',
            'provider' => 'merchants',
        ],
        'hotel' => [
            'driver' => 'session',
            'provider' => 'hotels',
        ],
        'franchise' => [
            'driver' => 'session',
            'provider' => 'franchise',
        ],
        'user' => [         // user login from webview to delete account from data base, url will be displayed on playstore
            'driver' => 'session',
            'provider' => 'users',
        ],
        'driver' => [         // driver login from webview to delete account from data base, url will be displayed on playstore
            'driver' => 'session',
            'provider' => 'drivers',
        ],
        'api' => [
            'driver' => 'passport',
            'provider' => 'users',
        ],
        'api-driver' => [
            'driver' => 'passport',
            'provider' => 'drivers',
        ],
        'api_merchant' => [
            'driver' => 'passport',
            'provider' => 'merchants',
        ],
        'vehicle_owner' => [
            'driver' => 'passport',
            'provider' => 'vehicleOwner',
        ],
        'taxicompany' => [
            'driver' => 'session',
            'provider' => 'taxicompany',
        ],
        'corporate' => [
            'driver' => 'session',
            'provider' => 'corporate',
        ],
        'business-segment-user' => [
            'driver' => 'session',
            'provider' => 'business-segment',
        ],
        'business-segment' => [
            'driver' => 'session',
            'provider' => 'business-segment',
        ],
        'business-segment-api' => [
            'driver' => 'passport',
            'provider' => 'business-segment-api',
        ],
        'driver-agency' => [
            'driver' => 'session',
            'provider' => 'driver-agency',
        ],
        'agent' => [
            'driver' => 'session',
            'provider' => 'agent',
        ],
        'laundry_outlet' => [
            'driver' => 'session',
            'provider' => 'laundry_outlet',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],
        'admins' => [
            'driver' => 'eloquent',
            'model' => App\Models\Admin::class,
        ],
        'merchants' => [
            'driver' => 'eloquent',
            'model' => App\Models\Merchant::class,
        ],
        'hotels' => [
            'driver' => 'eloquent',
            'model' => App\Models\Hotel::class,
        ],
        'franchise' => [
            'driver' => 'eloquent',
            'model' => App\Models\Franchisee::class,
        ],
        'drivers' => [
            'driver' => 'eloquent',
            'model' => App\Models\Driver::class,
        ],
        'social' => [
            'driver' => 'eloquent',
            'model' => App\User::class,
        ],
        'demo' => [
            'driver' => 'eloquent',
            'model' => App\Driver::class,
        ],
        'vehicleOwner' => [
            'driver' => 'eloquent',
            'model' => \App\Models\VehicleOwner::class
        ],
        'taxicompany' => [
            'driver' => 'eloquent',
            'model' => App\Models\TaxiCompany::class,
        ],
        'corporate' => [
            'driver' => 'eloquent',
            'model' => App\Models\Corporate::class,
        ],
        'userOtp' => [
            'driver' => 'eloquent',
            'model' => App\User::class,
        ],
        'driverOtp' => [
            'driver' => 'eloquent',
            'model' => App\Driver::class,
        ],
        'business-segment' => [
            'driver' => 'eloquent',
            'model' => App\Models\BusinessSegment\BusinessSegment::class,
        ],
        'driver-agency' => [
            'driver' => 'eloquent',
            'model' => App\Models\DriverAgency\DriverAgency::class,
        ],
        'business-segment-api' => [
            'driver' => 'eloquent',
            'model' => App\Models\BusinessSegment\BusinessSegment::class,
        ],
        'agent' => [
            'driver' => 'eloquent',
            'model' => App\Models\Agent::class,
        ],
        'laundry_outlet' => [
            'driver' => 'eloquent',
            'model' => \App\Models\LaundryOutlet\LaundryOutlet::class,
        ],

    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_resets',
            'expire' => 60,
        ],
    ],

];
