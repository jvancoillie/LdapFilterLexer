<?php

namespace Jvancoillie\LdapFilterLexer\Tests;

use Jvancoillie\LdapFilterLexer\Lexer;
use PHPUnit\Framework\TestCase;

class LexerTest extends TestCase
{
    public function testLexerExtendsLib()
    {
        $lexer = new Lexer('test extend');

        $this->assertInstanceOf('Doctrine\Common\Lexer\AbstractLexer', $lexer);
    }
}
