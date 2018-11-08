# WebPConvert Cloud Service

***Note: This readme file (and the rest of the repository) is in the process of a major update. It is not stable. To get the last stable release, download the 0.1 release.***

This library allows you to set up your own WebP conversion cloud service. This way you can have a cloud service for free. You won't have to worry about licenses expiring or being stolen and abused. And you will be completely in control of it (and downtime)

## Installation

### 1. Require the library with composer
```text
composer require rosell-dk/webp-convert-cloud-service
```

### 2. Create a script, which calls the library with configuration options

Here is a minimal example to get started with:

```php
require 'vendor/autoload.php';

use \WebPConvertCloudService\WebPConvertCloudService;

$options = [
    // Set dir for storing converted images temporarily:
    'destination-dir' => '../conversions',

    // Set acccess restrictions
    'access' => [
        'whitelist' => [
            [
                'ip' => '*',
                'api-key' => 'my dog is white',
                'require-api-key-to-be-crypted-in-transfer' => false
            ]
        ]
    ],

    // Optionally set webp-convert options
    'webp-convert' => [
        'converters' => ['cwebp', 'gd', 'imagick'],
        'converter-options' => [
            'cwebp' => [
                'try-common-system-paths' => true,
                'try-supplied-binary-for-os' => true,
                'use-nice' => true
            ]
        ]
    ]
];

$wpc = new WebPConvertCloudService();
$wpc->handleRequest($options);

```

## Usage

```php
require __DIR__ . 'vendor/autoload.php';

```

### API
The cloud converter can be used with [webp-convert](https://github.com/rosell-dk/webp-convert/) (and code build upon webp-convert, such as [webp-express](https://github.com/rosell-dk/webp-express/)). In those cases, you don't need to know the API. The API is documented in `docs/api.md`
