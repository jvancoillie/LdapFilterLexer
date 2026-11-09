<?php

namespace Jvancoillie\LdapFilterLexer;

final class Value
{
    /**
     * Characters that must be escaped in an assertion value (RFC 4515, section 3).
     */
    private const ESCAPES = [
        '\\' => '\5c',
        '*' => '\2a',
        '(' => '\28',
        ')' => '\29',
        "\0" => '\00',
    ];

    /**
     * Escapes a value so that it is matched literally, like ldap_escape($value, '', LDAP_ESCAPE_FILTER).
     * With $keepWildcards, "*" is left as is and still acts as a wildcard: never use it on user input.
     */
    public static function escape(string $value, bool $keepWildcards = false): string
    {
        $escapes = self::ESCAPES;

        if ($keepWildcards) {
            unset($escapes['*']);
        }

        return strtr($value, $escapes);
    }
}
