# LdapFilterLexer

[![Tests Status](https://github.com/jvancoillie/LdapFilterLexer/workflows/tests/badge.svg?branch=main)](https://github.com/jvancoillie/LdapFilterLexer/actions)

A library for validating ldap filters.

## Requirements

* PHP 8.1
* [Composer](https://getcomposer.org) is required for installation

## Installation

```shell
composer require jvancoillie/ldap-filter-lexer
```

## Getting Started

```php
<?php

use Jvancoillie\LdapFilterLexer\Filter;

$filter = new Filter('(&(objectClass=person)(|(cn=Babs Jensen)(cn=Tim*)))');
$filter->isValid(); //true
```
