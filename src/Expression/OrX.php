<?php

namespace Jvancoillie\LdapFilterLexer\Expression;

class OrX extends Base
{
    private string $vertbar = '|';
    private array $expressions = [];

    public function __construct(Base ...$expressions)
    {
        $this->expressions = $expressions;
    }

    public function __toString(): string
    {
        return $this->leftParenthesis.
            $this->vertbar.
            implode('',
                array_map(
                    fn (Base $expression) => (string) $expression,
                    $this->expressions
                )
            ).
            $this->rightParenthesis;
    }
}
