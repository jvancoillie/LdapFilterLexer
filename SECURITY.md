# Security Policy

## Supported versions

| Version | Supported                                 |
|---------|-------------------------------------------|
| 2.1.x   | Yes                                       |
| 2.0.x   | Security fixes only (projects on PHP 8.1) |
| < 2.0   | No                                        |

## Reporting a vulnerability

This library parses filters that may come from untrusted input. If you find a way to make it accept a filter
that a directory would interpret differently, to crash it, or to exhaust its resources, please report it privately.

Do not open a public issue. Use [GitHub's private vulnerability reporting](https://github.com/jvancoillie/LdapFilterLexer/security/advisories/new)
and include the filter or the code that reproduces the problem, and the version you use.

You will get an answer within a week. Once the issue is confirmed, a fix is released and a security advisory is
published, with credit to the reporter if they wish.
