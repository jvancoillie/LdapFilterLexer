<?php

namespace Jvancoillie\LdapFilterLexer\Tests\Visitor;

use Jvancoillie\LdapFilterLexer\AST\AndNode;
use Jvancoillie\LdapFilterLexer\AST\AssertionValueNode;
use Jvancoillie\LdapFilterLexer\AST\AttributeNode;
use Jvancoillie\LdapFilterLexer\AST\ExtensibleNode;
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
    public function testVisitAndNode(): void
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

    public function testVisitOrNode(): void
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

    public function testVisitNotNode(): void
    {
        $notNode = new NotNode(new SimpleNode(new AttributeNode('attr1'), new FilterTypeNode(Lexer::EQUALS), new AssertionValueNode('value1')));

        $visitor = new LdapExpressionVisitor();
        $result = $notNode->accept($visitor);

        $this->assertInstanceOf(Expression\Not::class, $result);
    }

    public function testVisitSimpleNode(): void
    {
        $simpleNode = new SimpleNode(new AttributeNode('attr1'), new FilterTypeNode(Lexer::EQUALS), new AssertionValueNode('value1'));

        $visitor = new LdapExpressionVisitor();
        $result = $simpleNode->accept($visitor);

        $this->assertInstanceOf(Expression\Comparison::class, $result);
        $this->assertSame('(attr1=value1)', (string) $result);
    }

    public function testVisitSimpleNodeThrowExceptionOnEmptyAssertionValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $simpleNode = new SimpleNode(new AttributeNode('attr1'), new FilterTypeNode(Lexer::EQUALS), new AssertionValueNode(null));

        $visitor = new LdapExpressionVisitor();
        $simpleNode->accept($visitor);
    }

    /** @dataProvider extensibleNodeProvider */
    public function testVisitExtensibleNode(ExtensibleNode $node, string $expected): void
    {
        $visitor = new LdapExpressionVisitor();
        $result = $node->accept($visitor);

        $this->assertInstanceOf(Expression\ExtensibleMatch::class, $result);
        $this->assertSame($expected, (string) $result);
    }

    public static function extensibleNodeProvider(): \Generator
    {
        yield 'attr only' => [new ExtensibleNode('cn', false, null, new AssertionValueNode('Betty Rubble')), '(cn:=Betty Rubble)'];
        yield 'attr + matchingRule' => [new ExtensibleNode('cn', false, 'caseExactMatch', new AssertionValueNode('Fred Flintstone')), '(cn:caseExactMatch:=Fred Flintstone)'];
        yield 'attr + dn' => [new ExtensibleNode('o', true, null, new AssertionValueNode('Ace Industry')), '(o:dn:=Ace Industry)'];
        yield 'attr + dn + matchingRule' => [new ExtensibleNode('sn', true, '2.4.6.8.10', new AssertionValueNode('Barney Rubble')), '(sn:dn:2.4.6.8.10:=Barney Rubble)'];
        yield 'matchingRule only' => [new ExtensibleNode(null, false, '1.2.3', new AssertionValueNode('Wilma Flintstone')), '(:1.2.3:=Wilma Flintstone)'];
        yield 'dn + matchingRule' => [new ExtensibleNode(null, true, '2.4.6.8.10', new AssertionValueNode('Dino')), '(:dn:2.4.6.8.10:=Dino)'];
    }
}
