<?php

namespace Jvancoillie\LdapFilterLexer\AST;

class AttributeNode
{
    public function __construct(public readonly string $value)
    {
    }
}
