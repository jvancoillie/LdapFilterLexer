<?php

namespace Jvancoillie\LdapFilterLexer\AST;

use Jvancoillie\LdapFilterLexer\Visitor\NodeVisitorInterface;
use Jvancoillie\LdapFilterLexer\Visitor\VisitableNodeInterface;

class NotNode extends Node implements VisitableNodeInterface
{
    public Node $condition;

    public function __construct(Node $condition)
    {
        $this->condition = $condition;
    }

    public function accept(NodeVisitorInterface $visitor): mixed
    {
        return $visitor->visitNotNode($this);
    }
}
