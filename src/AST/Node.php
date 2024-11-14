<?php

namespace Jvancoillie\LdapFilterLexer\AST;

use Jvancoillie\LdapFilterLexer\Visitor\NodeVisitorInterface;
use Jvancoillie\LdapFilterLexer\Visitor\VisitableNodeInterface;

abstract class Node implements VisitableNodeInterface
{
    abstract public function accept(NodeVisitorInterface $visitor): mixed;
}
