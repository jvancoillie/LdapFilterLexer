<?php

namespace Jvancoillie\LdapFilterLexer\AST;

class AssertionValueNode
{
    public function __construct(public readonly ?string $value)
    {
    }
}
