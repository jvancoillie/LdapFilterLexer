<?php

namespace Jvancoillie\LdapFilterLexer\AST;

class FilterTypeNode
{
    public string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }
}
