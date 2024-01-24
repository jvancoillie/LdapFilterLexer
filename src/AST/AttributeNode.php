<?php

namespace Jvancoillie\LdapFilterLexer\AST;

class AttributeNode extends Node
{
    public string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }
}
