<?php

namespace Jvancoillie\LdapFilterLexer\Tests\Visitor;

use Jvancoillie\LdapFilterLexer\AST\AndNode;
use Jvancoillie\LdapFilterLexer\AST\AssertionValueNode;
use Jvancoillie\LdapFilterLexer\AST\AttributeNode;
use Jvancoillie\LdapFilterLexer\AST\FilterTypeNode;
use Jvancoillie\LdapFilterLexer\AST\NotNode;
use Jvancoillie\LdapFilterLexer\AST\OrNode;
use Jvancoillie\LdapFilterLexer\AST\SimpleNode;
use Jvancoillie\LdapFilterLexer\Expression;
use Jvancoillie\LdapFilterLexer\Lexer;
use Jvancoillie\LdapFilterLexer\Visitor\LdapExpressionVisitor;
use PHPUnit\Framework\TestCase;

class LdapExpressionVisitorTest extends TestCase
{
    public function testVisitAndNode()
    {
        $andNode = new AndNode([
            new SimpleNode(new AttributeNode('attr1'), new FilterTypeNode(Lexer::EQUALS), new AssertionValueNode('value1')),
            new SimpleNode(new AttributeNode('attr2'), new FilterTypeNode(Lexer::EQUALS), new AssertionValueNode('value2')),
        ]);

        $visitor = new LdapExpressionVisitor();
        $result = $andNode->accept($visitor);

        $this->assertInstanceOf(Expression\AndX::class, $result);
        $this->assertSame('(&(attr1=value1)(attr2=value2))', (string) $result);
    }

    public function testVisitOrNode()
    {
        $orNode = new OrNode([
            new SimpleNode(new AttributeNode('attr1'), new FilterTypeNode(Lexer::EQUALS), new AssertionValueNode('value1')),
            new SimpleNode(new AttributeNode('attr2'), new FilterTypeNode(Lexer::EQUALS), new AssertionValueNode('value2')),
        ]);

        $visitor = new LdapExpressionVisitor();
        $result = $orNode->accept($visitor);

        $this->assertInstanceOf(Expression\OrX::class, $result);
        $this->assertSame('(|(attr1=value1)(attr2=value2))', (string) $result);
    }

    public function testVisitNotNode()
    {
        $notNode = new NotNode(new SimpleNode(new AttributeNode('attr1'), new FilterTypeNode(Lexer::EQUALS), new AssertionValueNode('value1')));

        $visitor = new LdapExpressionVisitor();
        $result = $notNode->accept($visitor);

        $this->assertInstanceOf(Expression\Not::class, $result);
    }

    public function testVisitSimpleNode()
    {
        $simpleNode = new SimpleNode(new AttributeNode('attr1'), new FilterTypeNode(Lexer::EQUALS), new AssertionValueNode('value1'));

        $visitor = new LdapExpressionVisitor();
        $result = $simpleNode->accept($visitor);

        $this->assertInstanceOf(Expression\Comparison::class, $result);
        $this->assertSame('(attr1=value1)', (string) $result);
    }

    public function testVisitSimpleNodeThrowExceptionOnEmptyAssertionValue()
    {
        $this->expectException(\InvalidArgumentException::class);
        $simpleNode = new SimpleNode(new AttributeNode('attr1'), new FilterTypeNode(Lexer::EQUALS), new AssertionValueNode(null));

        $visitor = new LdapExpressionVisitor();
        $result = $simpleNode->accept($visitor);
    }
}
