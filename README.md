# LDAP Filter Lexer

**RFC 4515 LDAP filter parser, builder and AST for PHP.**

[![Latest Version](https://img.shields.io/packagist/v/jvancoillie/ldap-filter-lexer.svg)](https://packagist.org/packages/jvancoillie/ldap-filter-lexer)
[![PHP Version](https://img.shields.io/packagist/dependency-v/jvancoillie/ldap-filter-lexer/php.svg)](https://packagist.org/packages/jvancoillie/ldap-filter-lexer)
[![Tests](https://github.com/jvancoillie/LdapFilterLexer/actions/workflows/test.yml/badge.svg?branch=main)](https://github.com/jvancoillie/LdapFilterLexer/actions/workflows/test.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/jvancoillie/ldap-filter-lexer.svg)](https://packagist.org/packages/jvancoillie/ldap-filter-lexer)
[![License](https://img.shields.io/packagist/l/jvancoillie/ldap-filter-lexer.svg)](LICENSE)

Validate LDAP search filters before sending them to a directory, parse them into a typed syntax tree,
build them programmatically, and walk them with visitors. Pure PHP: `ext-ldap` is not required.

- **Validate** a filter written by a user or stored in a configuration.
- **Parse** a filter into an AST (`AndNode`, `OrNode`, `NotNode`, `SimpleNode`, `ExtensibleNode`).
- **Build** filters with a fluent, immutable builder, or start from an existing filter and extend it.
- **Escape** user input in pure PHP, or let the builder do it, to prevent LDAP injection.
- **Translate** filters to anything else (SQL, Doctrine criteria, a list of attributes…) with a visitor.
- **Follow RFC 4515**, including extensible matches (`(cn:caseExactMatch:=Fred)`, `(sn:dn:2.4.6.8.10:=Barney)`).

## Installation

```shell
composer require jvancoillie/ldap-filter-lexer
```

Requires PHP 8.1 or later. The only dependency is [`doctrine/lexer`](https://github.com/doctrine/lexer).

## Validate a filter

```php
use Jvancoillie\LdapFilterLexer\Filter;

(new Filter('(&(objectClass=person)(|(cn=Babs Jensen)(cn=Tim*)))'))->isValid(); // true
(new Filter('(cn=Babs (Jensen))'))->isValid();                                // false: "(" must be escaped as \28
```

To know *why* a filter is invalid, parse it and catch the `FilterException`:

```php
use Jvancoillie\LdapFilterLexer\Filter;
use Jvancoillie\LdapFilterLexer\FilterException;

try {
    (new Filter('(cn=Babs (Jensen))'))->getParser()->getAST();
} catch (FilterException $e) {
    echo $e->getMessage();
    // [Syntax Error] line 0, col 9: Error: Expected assertion value, "(" must be escaped as \28,
    // got '(' on query '(cn=Babs (Jensen))'
}
```

The message gives the position of the offending character. Nesting is limited to 100 levels, so a hostile
filter cannot exhaust the stack.

## Parse a filter into an AST

```php
use Jvancoillie\LdapFilterLexer\Filter;

$ast = (new Filter('(&(objectClass=person)(!(mail=*)))'))->getParser()->getAST();

$ast;                                     // AST\AndNode
$ast->conditions[0];                      // AST\SimpleNode
$ast->conditions[0]->attribute->value;    // 'objectClass'
$ast->conditions[0]->filterType->value;   // '='
$ast->conditions[0]->assertionValue->value; // 'person'
$ast->conditions[1];                      // AST\NotNode
```

| Node             | Filter                          | Properties                                                        |
|------------------|---------------------------------|-------------------------------------------------------------------|
| `AndNode`        | `(&(a=1)(b=2))`                 | `conditions`                                                      |
| `OrNode`         | `(\|(a=1)(b=2))`                | `conditions`                                                      |
| `NotNode`        | `(!(a=1))`                      | `condition`                                                       |
| `SimpleNode`     | `(a=1)`, `(a~=1)`, `(a>=1)`, `(a<=1)`, `(a=*)`, `(a=b*c)` | `attribute`, `filterType`, `assertionValue`  |
| `ExtensibleNode` | `(cn:dn:caseExactMatch:=Fred)`  | `attribute`, `dnAttributes`, `matchingRule`, `assertionValue`     |

All nodes are immutable (`readonly` properties).

## Build a filter

`FilterBuilder` is immutable: every method returns a new builder.

```php
use Jvancoillie\LdapFilterLexer\FilterBuilder;

$qb = FilterBuilder::create();

echo $qb->andX(
    $qb->equals('objectClass', 'person'),
    $qb->orX($qb->equals('cn', 'Babs Jensen'), $qb->equals('cn', 'Tim*')),
    $qb->not($qb->equals('mail', '*')),
)->getFilter();
// (&(objectClass=person)(|(cn=Babs Jensen)(cn=Tim*))(!(mail=*)))
```

Start from an existing filter and extend it:

```php
echo FilterBuilder::create('(objectClass=person)')
    ->andX(FilterBuilder::create()->greaterThan('uidNumber', '1000'))
    ->getFilter();
// (&(objectClass=person)(uidNumber>=1000))
```

| Method                                                          | Produces                     |
|-----------------------------------------------------------------|------------------------------|
| `equals('cn', 'Babs')`                                          | `(cn=Babs)`                  |
| `approximate('cn', 'Babs')`                                     | `(cn~=Babs)`                 |
| `greaterThan('uidNumber', '1000')`                              | `(uidNumber>=1000)`          |
| `lowerThan('uidNumber', '1000')`                                | `(uidNumber<=1000)`          |
| `extensible('Fred', 'cn', 'caseExactMatch')`                    | `(cn:caseExactMatch:=Fred)`  |
| `extensible('Barney', 'sn', '2.4.6.8.10', dnAttributes: true)`  | `(sn:dn:2.4.6.8.10:=Barney)` |
| `andX(...)`, `orX(...)`                                         | `(&...)`, `(\|...)`          |
| `not(...)`, or `not()` to negate the current filter             | `(!...)`                     |

`andX()` and `orX()` with a single expression return that expression, since `(&(a=b))` is not a valid filter.
`getFilter()` returns a `Filter`, so the result can be validated with `isValid()`.

### Escape user input

A value coming from a user must be escaped, otherwise `*`, `(`, `)` or `\` change the meaning of the filter
(LDAP injection): `(uid=*)(uid=*)` instead of a search for a strange login.

`present()`, `startsWith()`, `endsWith()` and `contains()` escape the value for you:

```php
$input = 'Babs (Jensen)*';
$qb = FilterBuilder::create();

echo $qb->andX($qb->present('mail'), $qb->contains('cn', $input))->getFilter();
// (&(mail=*)(cn=*Babs \28Jensen\29\2a*))
```

| Method                     | Produces            |
|----------------------------|---------------------|
| `present('mail')`          | `(mail=*)`          |
| `startsWith('cn', 'Ba(')`  | `(cn=Ba\28*)`       |
| `endsWith('cn', 'sen')`    | `(cn=*sen)`         |
| `contains('cn', 'ab*')`    | `(cn=*ab\2a*)`      |

`equals()`, `approximate()`, `greaterThan()` and `lowerThan()` write the value as it is given, so that wildcards
such as `Tim*` keep working. Escape user input with `Value::escape()`, in pure PHP (same result as
`ldap_escape($value, '', LDAP_ESCAPE_FILTER)`, without `ext-ldap`):

```php
use Jvancoillie\LdapFilterLexer\Value;

echo FilterBuilder::create()->equals('uid', Value::escape('*)(uid=*'))->getFilter();
// (uid=\2a\29\28uid=\2a)

Value::escape('Tim*(x)', keepWildcards: true); // 'Tim*\28x\29': "*" stays a wildcard, never use it on user input
```

## Write a visitor

Implement `NodeVisitorInterface` to turn a parsed filter into anything else. This visitor lists the attributes
a filter uses:

```php
use Jvancoillie\LdapFilterLexer\AST;
use Jvancoillie\LdapFilterLexer\Filter;
use Jvancoillie\LdapFilterLexer\Visitor\NodeVisitorInterface;

/** @implements NodeVisitorInterface<list<string>> */
final class AttributeCollector implements NodeVisitorInterface
{
    public function visitAndNode(AST\AndNode $node): array
    {
        return array_merge(...array_map(fn ($child) => $child->accept($this), $node->conditions));
    }

    public function visitOrNode(AST\OrNode $node): array
    {
        return array_merge(...array_map(fn ($child) => $child->accept($this), $node->conditions));
    }

    public function visitNotNode(AST\NotNode $node): array
    {
        return $node->condition->accept($this);
    }

    public function visitSimpleNode(AST\SimpleNode $node): array
    {
        return [$node->attribute->value];
    }

    public function visitExtensibleNode(AST\ExtensibleNode $node): array
    {
        return null === $node->attribute ? [] : [$node->attribute];
    }
}

$ast = (new Filter('(&(objectClass=person)(|(cn=Babs*)(sn:dn:=Jensen)))'))->getParser()->getAST();
$ast->accept(new AttributeCollector()); // ['objectClass', 'cn', 'sn']
```

The library ships `LdapExpressionVisitor`, which turns an AST back into builder expressions: this is how
`FilterBuilder::create('(…)')` works.

## RFC 4515 compliance

- `AND` and `OR` require at least two conditions, `NOT` exactly one.
- `(`, `)` and `\` must be escaped in values (`\28`, `\29`, `\5c`); a `\` must start a two hex digits escape.
- Attribute names cannot have leading or trailing whitespace.
- Empty values are allowed: `(cn=)`.
- Extensible matches support every form of the RFC: attribute, matching rule, `:dn` flag and their combinations.

## Versioning

The library follows [Semantic Versioning](https://semver.org). See the [CHANGELOG](CHANGELOG.md) for the
changes of each release and the upgrade notes of 2.0.

## Contributing

Bug reports and pull requests are welcome: see [CONTRIBUTING.md](CONTRIBUTING.md).
To report a security issue, follow [SECURITY.md](SECURITY.md).

## License

MIT. See [LICENSE](LICENSE).
