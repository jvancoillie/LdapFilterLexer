<?php

namespace Jvancoillie\LdapFilterLexer\AST;

class AttributeNode
{
    public string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }
}
