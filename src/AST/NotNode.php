<?php

namespace Jvancoillie\LdapFilterLexer\AST;

use Jvancoillie\LdapFilterLexer\Visitor\NodeVisitorInterface;
use Jvancoillie\LdapFilterLexer\Visitor\VisitableNodeInterface;

class NotNode extends Node implements VisitableNodeInterface
{
    public function __construct(public readonly Node $condition)
    {
    }

    public function accept(NodeVisitorInterface $visitor): mixed
    {
        return $visitor->visitNotNode($this);
    }
}
