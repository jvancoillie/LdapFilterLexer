<?php

namespace Jvancoillie\LdapFilterLexer\Visitor;

use Jvancoillie\LdapFilterLexer\AST\AndNode;
use Jvancoillie\LdapFilterLexer\AST\NotNode;
use Jvancoillie\LdapFilterLexer\AST\OrNode;
use Jvancoillie\LdapFilterLexer\AST\SimpleNode;

/**
 * @template T
 */
interface NodeVisitorInterface
{
    /**
     * @return T
     */
    public function visitAndNode(AndNode $node): mixed;

    /**
     * @return T
     */
    public function visitOrNode(OrNode $node): mixed;

    /**
     * @return T
     */
    public function visitNotNode(NotNode $node): mixed;

    /**
     * @return T
     */
    public function visitSimpleNode(SimpleNode $node): mixed;
}
