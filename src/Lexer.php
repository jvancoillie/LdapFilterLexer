<?php

namespace Jvancoillie\LdapFilterLexer;

use Doctrine\Common\Lexer\AbstractLexer;

/** @extends AbstractLexer<string, string> */
class Lexer extends AbstractLexer
{
    public const LPAREN = '(';
    public const RPAREN = ')';
    public const AMPERSAND = '&';
    public const VERTBAR = '|';
    public const EXCLAMATION = '!';
    public const ASTERISK = '*';
    public const EQUALS = '=';
    public const TILDE_EQUALS = '~=';
    public const RANGLE_EQUALS = '>=';
    public const LANGLE_EQUALS = '<=';
    public const ATTRIBUTE_OR_ASSERTION_VALUE = 'ATTRIBUTE_OR_ASSERTION_VALUE';

    public function __construct(string $input)
    {
        $this->setInput($input);
    }

    /** {@inheritdoc} */
    protected function getCatchablePatterns(): array
    {
        return [
            '\(|\)', // Parenthesis
            '[&|!]', // logical expression
            '~=|=|>=|<=', // operators
            '[^()&|!=~><]+', // attributes & matches
        ];
    }

    /** {@inheritdoc} */
    protected function getNonCatchablePatterns(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     *
     * @param string $value
     */
    protected function getType(&$value): string
    {
        return match (true) {
            self::LPAREN === $value => self::LPAREN,
            self::RPAREN === $value => self::RPAREN,
            self::AMPERSAND === $value => self::AMPERSAND,
            self::VERTBAR === $value => self::VERTBAR,
            self::EXCLAMATION === $value => self::EXCLAMATION,
            self::RANGLE_EQUALS === $value => self::RANGLE_EQUALS,
            self::LANGLE_EQUALS === $value => self::LANGLE_EQUALS,
            self::EQUALS === $value => self::EQUALS,
            self::TILDE_EQUALS === $value => self::TILDE_EQUALS,
            default => self::ATTRIBUTE_OR_ASSERTION_VALUE,
        };
    }
}
