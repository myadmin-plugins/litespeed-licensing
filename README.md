# MyAdmin LiteSpeed Licensing

[![Tests](https://github.com/detain/myadmin-litespeed-licensing/actions/workflows/tests.yml/badge.svg)](https://github.com/detain/myadmin-litespeed-licensing/actions/workflows/tests.yml)
[![Latest Stable Version](https://poser.pugx.org/detain/myadmin-litespeed-licensing/version)](https://packagist.org/packages/detain/myadmin-litespeed-licensing)
[![Total Downloads](https://poser.pugx.org/detain/myadmin-litespeed-licensing/downloads)](https://packagist.org/packages/detain/myadmin-litespeed-licensing)
[![License](https://poser.pugx.org/detain/myadmin-litespeed-licensing/license)](https://packagist.org/packages/detain/myadmin-litespeed-licensing)

A MyAdmin plugin for provisioning and managing LiteSpeed Web Server and Load Balancer licenses. Integrates with the LiteSpeed licensing API to handle activation, deactivation, IP changes, and balance checks for both standard and VPS license types.

## Features

- Activate and deactivate LiteSpeed Web Server (LSWS) and Load Balancer (LSLB) licenses
- Support for multiple license tiers: 1-CPU, 2-CPU, 4-CPU, 8-CPU, VPS, and Ultra-VPS
- IP address change handling with automatic re-provisioning
- Credit balance monitoring before order placement
- Duplicate license detection to prevent double-provisioning
- Symfony EventDispatcher integration for hook-based architecture
- Admin menu integration for license listing

## Requirements

- PHP >= 5.0
- ext-soap
- symfony/event-dispatcher ^5.0
- detain/litespeed-licensing

## Installation

Install with Composer:

```sh
composer require detain/myadmin-litespeed-licensing
```

## Usage

The plugin registers itself through the MyAdmin hook system. Available hooks:

| Hook                      | Description                          |
|---------------------------|--------------------------------------|
| `licenses.settings`       | Register LiteSpeed configuration     |
| `licenses.activate`       | Provision a new license              |
| `licenses.reactivate`     | Re-provision an existing license     |
| `licenses.deactivate`     | Cancel a license                     |
| `licenses.deactivate_ip`  | Cancel a license by IP               |
| `licenses.change_ip`      | Change the IP on a license           |
| `function.requirements`   | Register function autoload paths     |
| `ui.menu`                 | Add admin menu entries               |

## Running Tests

```sh
composer install
vendor/bin/phpunit
```

## License

Licensed under the LGPL-2.1-only license. See [LICENSE](https://www.gnu.org/licenses/old-licenses/lgpl-2.1.html) for details.
