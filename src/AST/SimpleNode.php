<?php

namespace Jvancoillie\LdapFilterLexer\AST;

use Jvancoillie\LdapFilterLexer\Visitor\NodeVisitorInterface;
use Jvancoillie\LdapFilterLexer\Visitor\VisitableNodeInterface;

class SimpleNode extends Node implements VisitableNodeInterface
{
    public function __construct(
        public readonly AttributeNode $attribute,
        public readonly FilterTypeNode $filterType,
        public readonly AssertionValueNode $assertionValue,
    ) {
    }

    public function accept(NodeVisitorInterface $visitor): mixed
    {
        return $visitor->visitSimpleNode($this);
    }
}
