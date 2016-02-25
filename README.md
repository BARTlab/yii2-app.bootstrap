yii2-app.bootstrap
============

Application runner for Yii PHP framework 2.0 

Installation
------------
The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require "maybeworks/yii2-app.bootstrap" "*"
```

or add

```json
"maybeworks/yii2-app.bootstrap" : "*"
```

to the require section of your application's `composer.json` file.

Usage
-----
Alternative project structure
```
.
├── apps
│   ├── backend
│   │    └── ...
│   ├── console
│   │    └── ...
│   └── frontend
│         └── ...
├── common
│   └── ...
├── config
│   ├── backend.php
│   ├── bootstrap.php
│   ├── common.php
│   ├── console.php
│   ├── frontend.php
│   ├── local-example.php
│   └── local.php
├── db
│   ├── fixtures
│   │   └── ...
│   └── migrations
│        └── ...
├── runtime
│   └── ...
├── vendor
│   └── ...
├── web
│   ├── backend
│   │   ├── favicon.ico
│   │   └── index.php
│   ├── frontend
│   │   ├── favicon.ico
│   │   └── index.php
│   └── static
│       └── assets
│           └── ...
└── yii
```

local config example
```php
<?php
/**
 * @var $this AppBootstrap
 */
return [
    'common' => [
        'components' => [
            'db' => [
                'dsn' => 'mysql:host=localhost;dbname=yii2-app',
                'username' => 'root',
                'password' => '',
            ],
            'mailer' => [
                'useFileTransport' => true,
            ],
        ],
    ],
    'backend' => [],
    'frontend' => [],
    'bootstrap' => [
        'debug' => true,
        'env' => $this::ENV_DEV,
        'aliases' => [
            'web_frontend' => 'http://site.local',
            'web_backend' => 'http://admin.site.local',
            'web_static' => 'http://static.site.local'
        ]
    ],
];

```

frontend/web/index.php

```php
<?php
require dirname(dirname(__DIR__)) . '/vendor/maybeworks/yii2-app.bootstrap/AppBootstrap.php';

(new AppBootstrap(
    [
        'name' => 'frontend',
        'type' => AppBootstrap::APP_WEB,
        'baseDir' => dirname(dirname(__DIR__)),
        'vendorDir' => dirname(dirname(__DIR__)) . '/vendor',
        'bootConfig' => dirname(dirname(__DIR__)) . '/config/bootstrap.php'
    ]
))->run();
```

yii.php for cmd
```php
#!/usr/bin/env php
<?php
require __DIR__ . '/vendor/maybeworks/yii2-app.bootstrap/AppBootstrap.php';

exit(
    (new AppBootstrap(
        [
            'name' => 'console',
            'type' => AppBootstrap::APP_CONSOLE,
            'baseDir' => __DIR__,
            'vendorDir' => __DIR__ . '/vendor',
            'bootConfig' => __DIR__ . '/config/bootstrap.php'
        ]
    ))->run()
);
```

> [![MaybeWorks](http://maybe.works/logo/logo_mw.png)](http://maybe.works)  
<i>Nothing is impossible, limit exists only in the minds of...</i>  
[maybe.works](http://maybe.works)
