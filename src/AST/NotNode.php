<?php

namespace Jvancoillie\LdapFilterLexer\AST;

class NotNode extends Node
{
    public Node $condition;

    public function __construct(Node $condition)
    {
        $this->condition = $condition;
    }
}
