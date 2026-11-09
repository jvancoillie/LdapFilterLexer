<?php

namespace Jvancoillie\LdapFilterLexer\Tests;

use Jvancoillie\LdapFilterLexer\AST\ExtensibleNode;
use Jvancoillie\LdapFilterLexer\AST\Node;
use Jvancoillie\LdapFilterLexer\AST\SimpleNode;
use Jvancoillie\LdapFilterLexer\Filter;
use Jvancoillie\LdapFilterLexer\FilterException;
use Jvancoillie\LdapFilterLexer\Parser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    public function testParserReturnAst(): void
    {
        $parser = new Parser(new Filter('(&(objectClass=person)(|(sn=*jdoe*)(givenname=*jdoe*)))'));

        $this->assertInstanceOf(Node::class, $parser->getAST());
    }

    public function testParserThrowExceptionOnSimpleFilterWithoutFilterType(): void
    {
        $this->expectException(FilterException::class);
        $this->expectExceptionMessage("[Syntax Error] line 0, col 9: Error: Expected filter type (~=, =, <=, >=), got ')' on query '(sn*jdoe*)'");

        $parser = new Parser(new Filter('(sn*jdoe*)'));
        $parser->getAST();
    }

    public function testParserThrowExceptionOnNotFilterWithMultipleSimpleFilter(): void
    {
        $this->expectException(FilterException::class);
        $this->expectExceptionMessage("[Syntax Error] line 0, col 12: Error: Expected ), got '(' on query '(!(cn=john*)(cn=doe*)'");

        $parser = new Parser(new Filter('(!(cn=john*)(cn=doe*)'));
        $parser->getAST();
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function expectedTokenProvider(): iterable
    {
        yield 'missing (' => ['(&(cn=a)', "Expected (, got end of string. on query '(&(cn=a)'"];
        yield 'missing )' => ['(cn=a', "Expected ), got end of string. on query '(cn=a'"];
        yield 'missing attribute' => ['(=a)', "Expected attribute or value, got '=' on query '(=a)'"];
        yield 'missing = in extensible match' => ['(cn:dn:a)', "Expected =, got ')' on query '(cn:dn:a)'"];
    }

    #[DataProvider('expectedTokenProvider')]
    public function testSyntaxErrorShowsTheExpectedCharacter(string $filter, string $message): void
    {
        $this->expectException(FilterException::class);
        $this->expectExceptionMessage($message);

        (new Parser(new Filter($filter)))->getAST();
    }

    #[DataProvider('assertionValueWithSpecialCharsProvider')]
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
        yield 'escaped parentheses' => ['(cn=a\28b\29)', 'a\28b\29'];
        yield 'escaped asterisk' => ['(cn=*\2A*)', '*\2A*'];
        yield 'escaped backslash' => ['(cn=a\5c)', 'a\5c'];
    }

    #[DataProvider('invalidAssertionValueProvider')]
    public function testParserRejectsUnescapedSpecialCharsInAssertionValue(string $filter): void
    {
        $this->expectException(FilterException::class);

        (new Parser(new Filter($filter)))->getAST();
    }

    public static function invalidAssertionValueProvider(): \Generator
    {
        yield 'unescaped left parenthesis' => ['(cn=a(b)'];
        yield 'value starting with left parenthesis' => ['(cn=(a)'];
        yield 'left parenthesis in a nested filter' => ['(&(cn=a(b)(sn=c))'];
        yield 'lone backslash at end' => ['(cn=a\)'];
        yield 'backslash followed by non hex' => ['(cn=a\zz)'];
        yield 'backslash followed by one hex digit' => ['(cn=a\2)'];
    }

    public function testFilterWithUnescapedLeftParenthesisIsInvalid(): void
    {
        $this->assertFalse((new Filter('(cn=a(b)'))->isValid());
    }

    #[DataProvider('invalidAttributeWithSpacesProvider')]
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

    #[DataProvider('insufficientConditionsProvider')]
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
        yield 'NOT with zero conditions' => ['(!)'];
    }

    public function testNotWithoutConditionIsInvalid(): void
    {
        $this->assertFalse((new Filter('(!)'))->isValid());
    }

    public function testEmptyAssertionValueIsAnEmptyString(): void
    {
        $ast = (new Parser(new Filter('(cn=)')))->getAST();

        $this->assertInstanceOf(SimpleNode::class, $ast);
        $this->assertSame('', $ast->assertionValue->value);
    }

    #[DataProvider('validMultipleConditionsProvider')]
    public function testParserAcceptsAndOrWithTwoOrMoreConditions(string $filter): void
    {
        $this->assertInstanceOf(Node::class, (new Parser(new Filter($filter)))->getAST());
    }

    public static function validMultipleConditionsProvider(): \Generator
    {
        yield 'AND with two conditions' => ['(&(cn=a)(sn=b))'];
        yield 'AND with three conditions' => ['(&(cn=a)(sn=b)(uid=c))'];
        yield 'OR with two conditions' => ['(|(cn=a)(sn=b))'];
        yield 'OR with three conditions' => ['(|(cn=a)(sn=b)(uid=c))'];
    }

    #[DataProvider('extensibleMatchProvider')]
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

    public function testParserRejectsExcessiveNestingDepth(): void
    {
        $this->expectException(FilterException::class);
        $this->expectExceptionMessageMatches('/nesting depth/');

        // 100 levels of NOT → innermost reaches depth 101, exceeds MAX_DEPTH of 100
        $filter = str_repeat('(!', 100).'(cn=test)'.str_repeat(')', 100);
        (new Parser(new Filter($filter)))->getAST();
    }

    public function testParserAcceptsNestingAtDepthLimit(): void
    {
        // 99 levels of NOT → innermost reaches depth 100 = MAX_DEPTH, should pass
        $filter = str_repeat('(!', 99).'(cn=test)'.str_repeat(')', 99);
        $this->assertInstanceOf(Node::class, (new Parser(new Filter($filter)))->getAST());
    }
}
