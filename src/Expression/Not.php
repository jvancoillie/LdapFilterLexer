<?php

namespace Jvancoillie\LdapFilterLexer\Expression;

class Not extends Base
{
    private string $exclamation = '!';

    public function __construct(private readonly Base $expression)
    {
    }

    public function __toString(): string
    {
        return $this->leftParenthesis.$this->exclamation.$this->expression.$this->rightParenthesis;
    }
}
