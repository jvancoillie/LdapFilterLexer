<?php

namespace Jvancoillie\LdapFilterLexer\Expression;

class Comparison extends Base
{
    public const EQUALS = '=';
    public const TILDE_EQUALS = '~=';
    public const RANGLE_EQUALS = '>=';
    public const LANGLE_EQUALS = '<=';

    public function __construct(private readonly string $leftExpr, private readonly string $operator, private readonly string $rightExpr)
    {
    }

    public function __toString(): string
    {
        return $this->leftParenthesis.$this->leftExpr.$this->operator.$this->rightExpr.$this->rightParenthesis;
    }
}
