<?php

namespace Jvancoillie\LdapFilterLexer\Visitor;

interface VisitableNodeInterface
{
    /**
     * @template T
     *
     * @param NodeVisitorInterface<T> $visitor
     *
     * @return T
     */
    public function accept(NodeVisitorInterface $visitor): mixed;
}
