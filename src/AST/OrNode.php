<?php

namespace Jvancoillie\LdapFilterLexer\AST;

use Jvancoillie\LdapFilterLexer\Visitor\NodeVisitorInterface;
use Jvancoillie\LdapFilterLexer\Visitor\VisitableNodeInterface;

class OrNode extends Node implements VisitableNodeInterface
{
    /** @var array<Node> */
    public array $conditions = [];

    /** @param array<Node> $conditions*/
    public function __construct(array $conditions = [])
    {
        $this->conditions = $conditions;
    }

    public function accept(NodeVisitorInterface $visitor): mixed
    {
        return $visitor->visitOrNode($this);
    }
}
