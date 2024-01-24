<?php

namespace Jvancoillie\LdapFilterLexer\AST;

class SimpleNode extends Node
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
}
