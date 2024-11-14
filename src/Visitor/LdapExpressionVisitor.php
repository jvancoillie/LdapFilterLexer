<?php

namespace Jvancoillie\LdapFilterLexer\Visitor;

use Jvancoillie\LdapFilterLexer\AST\AndNode;
use Jvancoillie\LdapFilterLexer\AST\NotNode;
use Jvancoillie\LdapFilterLexer\AST\OrNode;
use Jvancoillie\LdapFilterLexer\AST\SimpleNode;
use Jvancoillie\LdapFilterLexer\Expression;

/**
 * @implements NodeVisitorInterface<Expression\Base>
 */
class LdapExpressionVisitor implements NodeVisitorInterface
{
    public function visitAndNode(AndNode $node): Expression\AndX
    {
        $expressions = array_map(fn ($childNode) => $childNode->accept($this), $node->conditions);

        return new Expression\AndX(...$expressions);
    }

    public function visitOrNode(OrNode $node): Expression\OrX
    {
        $expressions = array_map(fn ($childNode) => $childNode->accept($this), $node->conditions);

        return new Expression\OrX(...$expressions);
    }

    public function visitNotNode(NotNode $node): Expression\Not
    {
        return new Expression\Not($node->condition->accept($this));
    }

    public function visitSimpleNode(SimpleNode $node): Expression\Comparison
    {
        if (null === $value = $node->assertionValue->value) {
            throw new \InvalidArgumentException('Comparison value can not be null');
        }

        return new Expression\Comparison(
            $node->attribute->value,
            $node->filterType->value,
            $value
        );
    }
}
