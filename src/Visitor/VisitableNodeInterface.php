<?php

namespace Jvancoillie\LdapFilterLexer\Visitor;

use Jvancoillie\LdapFilterLexer\Expression;

interface VisitableNodeInterface
{
    /**
     * @param NodeVisitorInterface<Expression\Base> $visitor
     *
     * @return Expression\Base
     */
    public function accept(NodeVisitorInterface $visitor): mixed;
}
