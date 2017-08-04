<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for all database work. Of course
    | you may use many connections at once using the Database library.
    |
    */

    'default' => env('DB_CONNECTION', 'laputa'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the database connections setup for your application.
    | Of course, examples of configuring each database platform that is
    | supported by Laravel is shown below to make development simple.
    |
    |
    | All database work in Laravel is done through the PHP PDO facilities
    | so make sure you have the driver for your particular database of
    | choice installed on your machine before you begin development.
    |
    */

    'connections' => [

        'laputa' => [
            'driver' => 'mysql',
            'port' => env('DB_PORT', '3306'),
            'database' => 'Laputa',
            'read' => [
                'host' => env('DB_READ_HOST', 'localhost'),
                'username' => env('DB_READ_USERNAME', 'root'),
                'password' => env('DB_READ_PASSWORD', ''),
            ],
            'write' => [
                'host' => env('DB_WRITE_HOST', 'localhost'),
                'username' => env('DB_WRITE_USERNAME', 'root'),
                'password' => env('DB_WRITE_PASSWORD', ''),
            ],
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_general_ci',
            'prefix' => '',
            'strict' => false,
        ],

        'fico' => [
            'driver' => 'mysql',
            'port' => env('DB_FICO_PORT', '3306'),
            'database' => env('DB_FICO_DATABASE', 'forge'),
            'host' => env('DB_FICO_HOST', 'localhost'),
            'username' => env('DB_FICO_USERNAME', 'root'),
            'password' => env('DB_FICO_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_general_ci',
            'prefix' => '',
            'strict' => false,
        ],

        'forecast' => [
            'driver' => 'mysql',
            'port' => env('DB_PORT', '3306'),
            'database' => 'Forecast',
            'read' => [
                'host' => env('DB_READ_HOST', 'localhost'),
                'username' => env('DB_READ_USERNAME', 'root'),
                'password' => env('DB_READ_PASSWORD', ''),
            ],
            'write' => [
                'host' => env('DB_WRITE_HOST', 'localhost'),
                'username' => env('DB_WRITE_USERNAME', 'root'),
                'password' => env('DB_WRITE_PASSWORD', ''),
            ],
            //兼容django,不启用mb4
            'charset' => 'utf8',
            'collation' => 'utf8_general_ci',
            'prefix' => '',
            'strict' => false,
        ],

        'sqlite' => [
            'driver' => 'sqlite',
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run in the database.
    |
    */

    'migrations' => 'migrations',

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer set of commands than a typical key-value systems
    | such as APC or Memcached. Laravel makes it easy to dig right in.
    |
    */

    'redis' => [

        'cluster' => false,

        'default' => [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'port' => 6379,
            'database' => 0,
        ],
        'cache' => [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'port' => 6379,
            'database' => 1,
        ],
        'session' => [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'port' => 6379,
            'database' => 2,
        ],
        'queue' => [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'port' => 6379,
            'database' => 3,
        ],

    ],

];
