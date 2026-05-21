<?php

namespace Jvancoillie\LdapFilterLexer\Tests;

use Jvancoillie\LdapFilterLexer\AST\ExtensibleNode;
use Jvancoillie\LdapFilterLexer\AST\SimpleNode;
use Jvancoillie\LdapFilterLexer\Filter;
use Jvancoillie\LdapFilterLexer\FilterException;
use Jvancoillie\LdapFilterLexer\Parser;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    public function testParserReturnAst()
    {
        $parser = new Parser(new Filter('(&(objectClass=person)(|(sn=*jdoe*)(givenname=*jdoe*)))'));

        $ast = $parser->getAST();

        $this->assertNotNull($ast);
    }

    public function testParserThrowExceptionOnSimpleFilterWithoutFilterType()
    {
        $this->expectException(FilterException::class);
        $this->expectExceptionMessage("[Syntax Error] line 0, col 9: Error: Expected filter type (~=, =, <=, >=), got ')' on query '(sn*jdoe*)'");

        $parser = new Parser(new Filter('(sn*jdoe*)'));
        $parser->getAST();
    }

    public function testParserThrowExceptionOnNotFilterWithMultipleSimpleFilter()
    {
        $this->expectException(FilterException::class);
        $this->expectExceptionMessage("[Syntax Error] line 0, col 12: Error: Expected ), got '(' on query '(!(cn=john*)(cn=doe*)'");

        $parser = new Parser(new Filter('(!(cn=john*)(cn=doe*)'));
        $parser->getAST();
    }

    /**
     * @dataProvider assertionValueWithSpecialCharsProvider
     */
    public function testAssertionValuePreservesSpecialChars(string $filter, string $expectedValue): void
    {
        $parser = new Parser(new Filter($filter));
        $ast = $parser->getAST();

        $this->assertInstanceOf(SimpleNode::class, $ast);
        $this->assertSame($expectedValue, $ast->assertionValue->value);
    }

    public static function assertionValueWithSpecialCharsProvider(): \Generator
    {
        yield 'pipe in value' => ['(attr=chainA*|DEC|)', 'chainA*|DEC|'];
        yield 'pipe at end' => ['(attr=val|)', 'val|'];
        yield 'ampersand in value' => ['(attr=foo&bar)', 'foo&bar'];
        yield 'exclamation in value' => ['(attr=foo!bar)', 'foo!bar'];
        yield 'multiple pipes' => ['(attr=a|b|c)', 'a|b|c'];
    }

    /** @dataProvider invalidAttributeWithSpacesProvider */
    public function testParserRejectsAttributeWithSpaces(string $filter): void
    {
        $this->expectException(FilterException::class);

        $parser = new Parser(new Filter($filter));
        $parser->getAST();
    }

    public static function invalidAttributeWithSpacesProvider(): \Generator
    {
        yield 'trailing space before operator' => ['(attr =value)'];
        yield 'leading space after lparen' => ['( attr=value)'];
        yield 'spaces on both sides' => ['(attr = value)'];
    }

    /** @dataProvider insufficientConditionsProvider */
    public function testParserRejectsAndOrWithLessThanTwoConditions(string $filter): void
    {
        $this->expectException(FilterException::class);

        (new Parser(new Filter($filter)))->getAST();
    }

    public static function insufficientConditionsProvider(): \Generator
    {
        yield 'AND with zero conditions' => ['(&)'];
        yield 'AND with one condition' => ['(&(cn=value))'];
        yield 'OR with zero conditions' => ['(|)'];
        yield 'OR with one condition' => ['(|(cn=value))'];
    }

    /** @dataProvider validMultipleConditionsProvider */
    public function testParserAcceptsAndOrWithTwoOrMoreConditions(string $filter): void
    {
        $ast = (new Parser(new Filter($filter)))->getAST();

        $this->assertNotNull($ast);
    }

    public static function validMultipleConditionsProvider(): \Generator
    {
        yield 'AND with two conditions' => ['(&(cn=a)(sn=b))'];
        yield 'AND with three conditions' => ['(&(cn=a)(sn=b)(uid=c))'];
        yield 'OR with two conditions' => ['(|(cn=a)(sn=b))'];
        yield 'OR with three conditions' => ['(|(cn=a)(sn=b)(uid=c))'];
    }

    /** @dataProvider extensibleMatchProvider */
    public function testParserBuildsExtensibleNode(string $filter, ?string $attr, bool $dn, ?string $rule, ?string $value): void
    {
        $ast = (new Parser(new Filter($filter)))->getAST();

        $this->assertInstanceOf(ExtensibleNode::class, $ast);
        $this->assertSame($attr, $ast->attribute);
        $this->assertSame($dn, $ast->dnAttributes);
        $this->assertSame($rule, $ast->matchingRule);
        $this->assertSame($value, $ast->assertionValue->value);
    }

    public static function extensibleMatchProvider(): \Generator
    {
        yield 'attr only' => ['(cn:=Betty Rubble)', 'cn', false, null, 'Betty Rubble'];
        yield 'attr + matchingRule' => ['(cn:caseExactMatch:=Fred Flintstone)', 'cn', false, 'caseExactMatch', 'Fred Flintstone'];
        yield 'attr + dn' => ['(o:dn:=Ace Industry)', 'o', true, null, 'Ace Industry'];
        yield 'attr + dn + matchingRule' => ['(sn:dn:2.4.6.8.10:=Barney Rubble)', 'sn', true, '2.4.6.8.10', 'Barney Rubble'];
        yield 'matchingRule only' => ['(:1.2.3:=Wilma Flintstone)', null, false, '1.2.3', 'Wilma Flintstone'];
        yield 'dn + matchingRule' => ['(:DN:2.4.6.8.10:=Dino)', null, true, '2.4.6.8.10', 'Dino'];
    }
}
