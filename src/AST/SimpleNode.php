<?php

namespace Jvancoillie\LdapFilterLexer\AST;

use Jvancoillie\LdapFilterLexer\Visitor\NodeVisitorInterface;
use Jvancoillie\LdapFilterLexer\Visitor\VisitableNodeInterface;

class SimpleNode extends Node implements VisitableNodeInterface
{
    public AttributeNode $attribute;
    public FilterTypeNode $filterType;
    public AssertionValueNode $assertionValue;

    public function __construct(AttributeNode $attribute, FilterTypeNode $filterType, AssertionValueNode $assertionValue)
    {
        $this->attribute = $attribute;
        $this->filterType = $filterType;
        $this->assertionValue = $assertionValue;
    }

    public function accept(NodeVisitorInterface $visitor): mixed
    {
        return $visitor->visitSimpleNode($this);
    }
}
