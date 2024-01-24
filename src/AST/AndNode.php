<?php

namespace Jvancoillie\LdapFilterLexer\AST;

class AndNode extends Node
{
    /** @var array<Node> */
    public array $conditions = [];

    /** @param array<Node> $conditions*/
    public function __construct(array $conditions = [])
    {
        $this->conditions = $conditions;
    }
}
