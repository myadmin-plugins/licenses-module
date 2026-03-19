# MyAdmin Licenses Module

[![Tests](https://github.com/detain/myadmin-licenses-module/actions/workflows/tests.yml/badge.svg)](https://github.com/detain/myadmin-licenses-module/actions/workflows/tests.yml)
[![Latest Stable Version](https://poser.pugx.org/detain/myadmin-licenses-module/version)](https://packagist.org/packages/detain/myadmin-licenses-module)
[![Total Downloads](https://poser.pugx.org/detain/myadmin-licenses-module/downloads)](https://packagist.org/packages/detain/myadmin-licenses-module)
[![License](https://poser.pugx.org/detain/myadmin-licenses-module/license)](https://packagist.org/packages/detain/myadmin-licenses-module)

A plugin module for the MyAdmin administration panel that provides software license management capabilities. It handles the full license lifecycle including purchasing, IP assignment, IP changes, cancellation, and integration with the billing and invoicing system. The module registers as an event-driven plugin through the Symfony EventDispatcher, exposing a SOAP/REST API for programmatic license operations.

## Features

- License purchasing with coupon and prepay support
- IP-based license assignment and IP change management
- License cancellation by IP or by license ID
- Automatic integration with the MyAdmin billing and invoicing system
- Event-driven architecture using Symfony EventDispatcher
- Configurable suspend, deletion, and billing parameters

## Installation

```sh
composer require detain/myadmin-licenses-module
```

## Requirements

- PHP >= 5.0
- ext-soap
- symfony/event-dispatcher ^5.0

## Testing

```sh
composer install
vendor/bin/phpunit
```

## License

This package is licensed under the [LGPL-2.1](https://www.gnu.org/licenses/old-licenses/lgpl-2.1.en.html) license.
