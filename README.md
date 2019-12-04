---
### THE PROJECT IS UNDER PROGRESS AND IS NOT READY YET
---

[![PHP](https://img.shields.io/badge/PHP-7.2%2B-blue.svg)](https://php.net/migration72)

eTraxis is an issue tracking system with ability to set up an unlimited number of customizable workflows.
It can be used to track almost anything, though the most popular cases are a *bug tracker* and a *helpdesk system*.

### Features

* Custom workflows
* Fine-tuned permissions
* History of events and changes
* Filters and views
* Attachments
* Project metrics
* Authentication through Bitbucket, GitHub or Google
* Authentication through Active Directory (LDAP)
* MySQL and PostgreSQL support
* Localization and multilingual support
* Mobile-friendly web interface
* and more...

### Prerequisites

* [PHP](https://php.net/)
* [Composer](https://getcomposer.org/)

### Install

```bash
composer install
```

### Development

```bash
./bin/phpunit --coverage-html=var/coverage
```
