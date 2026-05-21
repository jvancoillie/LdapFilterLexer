<?php

namespace Jvancoillie\LdapFilterLexer\AST;

use Jvancoillie\LdapFilterLexer\Visitor\NodeVisitorInterface;
use Jvancoillie\LdapFilterLexer\Visitor\VisitableNodeInterface;

class OrNode extends Node implements VisitableNodeInterface
{
    /** @param array<Node> $conditions */
    public function __construct(public readonly array $conditions = [])
    {
    }

    public function accept(NodeVisitorInterface $visitor): mixed
    {
        return $visitor->visitOrNode($this);
    }
}
