# Contributing

Thank you for helping. Bug reports, documentation fixes and pull requests are welcome.

## Reporting a bug

Open an [issue](https://github.com/jvancoillie/LdapFilterLexer/issues) with the filter or the code that shows the
problem, what you expected, what you got, and the versions of PHP and of the library.

For a security issue, follow [SECURITY.md](SECURITY.md) instead.

## Proposing a change

For a new feature, open an issue first to discuss it. Then:

1. Fork the repository and create a branch from `main`.
2. Add tests that cover the change. Filters must follow [RFC 4515](https://www.rfc-editor.org/rfc/rfc4515).
3. Run the checks below; the CI runs them on every supported PHP version.
4. Add an entry under an `Unreleased` section of the [CHANGELOG](CHANGELOG.md).
5. Open a pull request. Commit messages follow [Conventional Commits](https://www.conventionalcommits.org)
   (`fix: ...`, `feat: ...`, `docs: ...`).

## Checks

```shell
composer install
vendor/bin/phpunit
vendor/bin/psalm --no-progress
vendor/bin/php-cs-fixer fix --dry-run --diff
```

Psalm runs at level 1: new code must be fully typed.
