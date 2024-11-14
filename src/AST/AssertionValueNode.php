<?php

namespace Jvancoillie\LdapFilterLexer\AST;

class AssertionValueNode
{
    public ?string $value;

    public function __construct(?string $value)
    {
        $this->value = $value;
    }
}
