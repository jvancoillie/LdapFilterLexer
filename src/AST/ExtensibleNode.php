<?php

namespace Jvancoillie\LdapFilterLexer\AST;

use Jvancoillie\LdapFilterLexer\Visitor\NodeVisitorInterface;
use Jvancoillie\LdapFilterLexer\Visitor\VisitableNodeInterface;

class ExtensibleNode extends Node implements VisitableNodeInterface
{
    public function __construct(
        public readonly ?string $attribute,
        public readonly bool $dnAttributes,
        public readonly ?string $matchingRule,
        public readonly AssertionValueNode $assertionValue,
    ) {
    }

    public function accept(NodeVisitorInterface $visitor): mixed
    {
        return $visitor->visitExtensibleNode($this);
    }
}
