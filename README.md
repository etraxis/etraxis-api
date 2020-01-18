---
### THE PROJECT IS UNDER PROGRESS AND IS NOT READY YET
---

[![PHP](https://img.shields.io/badge/PHP-7.2%2B-blue.svg)](https://php.net/migration72)
[![Build Status](https://travis-ci.org/etraxis/etraxis-api.svg?branch=master)](https://travis-ci.org/etraxis/etraxis-api)
[![Code Coverage](https://scrutinizer-ci.com/g/etraxis/etraxis-api/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/etraxis/etraxis-api/?branch=master)
[![Code Quality](https://scrutinizer-ci.com/g/etraxis/etraxis-api/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/etraxis/etraxis-api/?branch=master)

### Prerequisites

* [PHP](https://php.net/)
* [Composer](https://getcomposer.org/)
* [Symfony](https://symfony.com/download)

### Install

```bash
composer install
./bin/console doctrine:database:create
./bin/console doctrine:schema:create
./bin/console doctrine:fixtures:load -n
symfony serve
```

### Development

```bash
./vendor/bin/php-cs-fixer fix
./bin/phpunit --coverage-html=var/coverage
```
