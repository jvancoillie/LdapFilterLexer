<?php

namespace Jvancoillie\LdapFilterLexer\AST;

class FilterTypeNode
{
    public function __construct(public readonly string $value)
    {
    }
}
