# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2026-05-21

### Breaking Changes

- **`NodeVisitorInterface`**: added required method `visitExtensibleNode(ExtensibleNode $node): mixed`.
  Any third-party implementation must add this method or a fatal error will be thrown at runtime.
- **AST nodes** (`SimpleNode`, `AndNode`, `OrNode`, `NotNode`, `AttributeNode`, `AssertionValueNode`,
  `FilterTypeNode`, `ExtensibleNode`): all public properties are now `readonly`.
  Writing to a property after construction throws an `Error`.
- **`Parser`**: AND/OR filters with fewer than 2 conditions now throw `FilterException` (RFC 4515 compliance).
  Previously, `(&(cn=a))` silently produced an invalid AST node.
- **`Filter::isValid()`**: no longer silences non-`FilterException` exceptions.
  Internal parser errors (e.g. `RuntimeException`) now propagate to the caller.

### Added

- Full **RFC 4515 extensible match** support: new `ExtensibleNode` AST node,
  `Expression\ExtensibleMatch`, and `FilterBuilder::extensible()` builder method.
  Supports attribute, matching rule, `:dn` flag, and all RFC-defined combinations
  (e.g. `(cn:caseExactMatch:=Fred)`, `(sn:dn:2.4.6.8.10:=Barney)`, `(:1.2.3:=Wilma)`).

### Fixed

- **`Filter::isValid()`**: changed `catch (\Exception)` to `catch (FilterException)` so internal
  errors surface correctly instead of returning a silent `false`.
- **`Parser`**: attribute names with surrounding whitespace (`(attr =value)`) are now rejected,
  conforming to RFC 4515.
- **`Parser`**: characters `|`, `&`, and `!` are now allowed inside assertion values
  (e.g. `(attr=chainA*|DEC|)`).
- **`FilterBuilder`**: fixed chaining for `andX()`, `orX()`, and `not()` — `$this->expression`
  is now included in the logical composition when chaining builder calls.
- **`Parser`**: recursion depth is now limited to 100 levels. Filters exceeding this depth throw
  a `FilterException` instead of causing a PHP stack overflow.

### Changed

- Psalm: enabled `findUnusedCode="true"` and extended analysis scope to `tests/`.
- PHPUnit: enabled `failOnRisky="true"` and `failOnWarning="true"`.
- `LexerTest`: replaced the single `assertInstanceOf` test with comprehensive tokenisation
  coverage for all token types and filter structures.

## [1.0.1] - previous release

## [1.0.0] - previous release
