<?php

namespace Jvancoillie\LdapFilterLexer\Tests;

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
}
