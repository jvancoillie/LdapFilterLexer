<?php

namespace Jvancoillie\LdapFilterLexer\AST;

class AssertionValueNode extends Node
{
    public ?string $value;

    public function __construct(?string $value)
    {
        $this->value = $value;
    }
}
