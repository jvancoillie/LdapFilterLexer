<?php

namespace Jvancoillie\LdapFilterLexer\Tests;

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
}
