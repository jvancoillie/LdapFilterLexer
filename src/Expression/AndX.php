<?php

namespace Jvancoillie\LdapFilterLexer\Expression;

class AndX extends Base
{
    private string $ampersand = '&';
    private array $expressions;

    public function __construct(Base ...$expressions)
    {
        $this->expressions = $expressions;
    }

    public function __toString(): string
    {
        return $this->leftParenthesis.
            $this->ampersand.
            implode('',
                array_map(
                    fn (Base $expression) => (string) $expression,
                    $this->expressions
                )
            ).$this->rightParenthesis;
    }
}
