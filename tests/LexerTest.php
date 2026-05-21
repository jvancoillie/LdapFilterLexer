<?php

namespace Jvancoillie\LdapFilterLexer\Tests;

use Jvancoillie\LdapFilterLexer\Lexer;
use PHPUnit\Framework\TestCase;

class LexerTest extends TestCase
{
    public function testLexerExtendsLib(): void
    {
        $lexer = new Lexer('test extend');

        $this->assertInstanceOf('Doctrine\Common\Lexer\AbstractLexer', $lexer);
    }

    /** @return iterable<string, array{string, string, string}> */
    public static function provideSingleTokens(): iterable
    {
        yield 'left parenthesis' => ['(',       Lexer::LPAREN,                       '('];
        yield 'right parenthesis' => [')',       Lexer::RPAREN,                       ')'];
        yield 'ampersand' => ['&',       Lexer::AMPERSAND,                    '&'];
        yield 'vertical bar' => ['|',       Lexer::VERTBAR,                      '|'];
        yield 'exclamation' => ['!',       Lexer::EXCLAMATION,                  '!'];
        yield 'equals' => ['=',       Lexer::EQUALS,                       '='];
        yield 'tilde equals' => ['~=',      Lexer::TILDE_EQUALS,                 '~='];
        yield 'right angle equals' => ['>=',      Lexer::RANGLE_EQUALS,                '>='];
        yield 'left angle equals' => ['<=',      Lexer::LANGLE_EQUALS,                '<='];
        yield 'attribute name' => ['cn',      Lexer::ATTRIBUTE_OR_ASSERTION_VALUE, 'cn'];
        yield 'assertion value' => ['jdoe',    Lexer::ATTRIBUTE_OR_ASSERTION_VALUE, 'jdoe'];
        yield 'asterisk alone' => ['*',       Lexer::ATTRIBUTE_OR_ASSERTION_VALUE, '*'];
        yield 'wildcard in value' => ['*jdoe*',  Lexer::ATTRIBUTE_OR_ASSERTION_VALUE, '*jdoe*'];
        yield 'colon sequence' => ['cn:dn:',  Lexer::ATTRIBUTE_OR_ASSERTION_VALUE, 'cn:dn:'];
        yield 'escape sequence' => ['\2a',     Lexer::ATTRIBUTE_OR_ASSERTION_VALUE, '\2a'];
    }

    /**
     * After construction lookahead and token are both null.
     * The first moveNext() loads the first real token into lookahead.
     *
     * @dataProvider provideSingleTokens
     */
    public function testSingleTokenType(string $input, string $expectedType, string $expectedValue): void
    {
        $lexer = new Lexer($input);
        $lexer->moveNext();

        $this->assertNotNull($lexer->lookahead);
        $this->assertSame($expectedType, $lexer->lookahead->type);
        $this->assertSame($expectedValue, $lexer->lookahead->value);
    }

    /**
     * @return iterable<string, array{string, list<array{string, string}>}>
     */
    public static function provideFilterSequences(): iterable
    {
        yield 'simple equality' => [
            '(cn=jdoe)',
            [
                [Lexer::LPAREN,                       '('],
                [Lexer::ATTRIBUTE_OR_ASSERTION_VALUE, 'cn'],
                [Lexer::EQUALS,                       '='],
                [Lexer::ATTRIBUTE_OR_ASSERTION_VALUE, 'jdoe'],
                [Lexer::RPAREN,                       ')'],
            ],
        ];

        yield 'approximate match' => [
            '(cn~=jdoe)',
            [
                [Lexer::LPAREN,                       '('],
                [Lexer::ATTRIBUTE_OR_ASSERTION_VALUE, 'cn'],
                [Lexer::TILDE_EQUALS,                 '~='],
                [Lexer::ATTRIBUTE_OR_ASSERTION_VALUE, 'jdoe'],
                [Lexer::RPAREN,                       ')'],
            ],
        ];

        yield 'greater or equal' => [
            '(age>=18)',
            [
                [Lexer::LPAREN,                       '('],
                [Lexer::ATTRIBUTE_OR_ASSERTION_VALUE, 'age'],
                [Lexer::RANGLE_EQUALS,                '>='],
                [Lexer::ATTRIBUTE_OR_ASSERTION_VALUE, '18'],
                [Lexer::RPAREN,                       ')'],
            ],
        ];

        yield 'less or equal' => [
            '(age<=18)',
            [
                [Lexer::LPAREN,                       '('],
                [Lexer::ATTRIBUTE_OR_ASSERTION_VALUE, 'age'],
                [Lexer::LANGLE_EQUALS,                '<='],
                [Lexer::ATTRIBUTE_OR_ASSERTION_VALUE, '18'],
                [Lexer::RPAREN,                       ')'],
            ],
        ];

        yield 'presence filter' => [
            '(cn=*)',
            [
                [Lexer::LPAREN,                       '('],
                [Lexer::ATTRIBUTE_OR_ASSERTION_VALUE, 'cn'],
                [Lexer::EQUALS,                       '='],
                [Lexer::ATTRIBUTE_OR_ASSERTION_VALUE, '*'],
                [Lexer::RPAREN,                       ')'],
            ],
        ];

        yield 'substring filter with wildcards' => [
            '(cn=*jdoe*)',
            [
                [Lexer::LPAREN,                       '('],
                [Lexer::ATTRIBUTE_OR_ASSERTION_VALUE, 'cn'],
                [Lexer::EQUALS,                       '='],
                [Lexer::ATTRIBUTE_OR_ASSERTION_VALUE, '*jdoe*'],
                [Lexer::RPAREN,                       ')'],
            ],
        ];

        yield 'AND filter' => [
            '(&(cn=a)(sn=b))',
            [
                [Lexer::LPAREN,                       '('],
                [Lexer::AMPERSAND,                    '&'],
                [Lexer::LPAREN,                       '('],
                [Lexer::ATTRIBUTE_OR_ASSERTION_VALUE, 'cn'],
                [Lexer::EQUALS,                       '='],
                [Lexer::ATTRIBUTE_OR_ASSERTION_VALUE, 'a'],
                [Lexer::RPAREN,                       ')'],
                [Lexer::LPAREN,                       '('],
                [Lexer::ATTRIBUTE_OR_ASSERTION_VALUE, 'sn'],
                [Lexer::EQUALS,                       '='],
                [Lexer::ATTRIBUTE_OR_ASSERTION_VALUE, 'b'],
                [Lexer::RPAREN,                       ')'],
                [Lexer::RPAREN,                       ')'],
            ],
        ];

        yield 'OR filter' => [
            '(|(cn=a)(sn=b))',
            [
                [Lexer::LPAREN,                       '('],
                [Lexer::VERTBAR,                      '|'],
                [Lexer::LPAREN,                       '('],
                [Lexer::ATTRIBUTE_OR_ASSERTION_VALUE, 'cn'],
                [Lexer::EQUALS,                       '='],
                [Lexer::ATTRIBUTE_OR_ASSERTION_VALUE, 'a'],
                [Lexer::RPAREN,                       ')'],
                [Lexer::LPAREN,                       '('],
                [Lexer::ATTRIBUTE_OR_ASSERTION_VALUE, 'sn'],
                [Lexer::EQUALS,                       '='],
                [Lexer::ATTRIBUTE_OR_ASSERTION_VALUE, 'b'],
                [Lexer::RPAREN,                       ')'],
                [Lexer::RPAREN,                       ')'],
            ],
        ];

        yield 'NOT filter' => [
            '(!(cn=a))',
            [
                [Lexer::LPAREN,                       '('],
                [Lexer::EXCLAMATION,                  '!'],
                [Lexer::LPAREN,                       '('],
                [Lexer::ATTRIBUTE_OR_ASSERTION_VALUE, 'cn'],
                [Lexer::EQUALS,                       '='],
                [Lexer::ATTRIBUTE_OR_ASSERTION_VALUE, 'a'],
                [Lexer::RPAREN,                       ')'],
                [Lexer::RPAREN,                       ')'],
            ],
        ];

        yield 'extensible match with dn and matching rule' => [
            '(cn:dn:caseExactMatch:=Fred)',
            [
                [Lexer::LPAREN,                       '('],
                [Lexer::ATTRIBUTE_OR_ASSERTION_VALUE, 'cn:dn:caseExactMatch:'],
                [Lexer::EQUALS,                       '='],
                [Lexer::ATTRIBUTE_OR_ASSERTION_VALUE, 'Fred'],
                [Lexer::RPAREN,                       ')'],
            ],
        ];

        yield 'extensible match attribute only' => [
            '(cn:=Fred)',
            [
                [Lexer::LPAREN,                       '('],
                [Lexer::ATTRIBUTE_OR_ASSERTION_VALUE, 'cn:'],
                [Lexer::EQUALS,                       '='],
                [Lexer::ATTRIBUTE_OR_ASSERTION_VALUE, 'Fred'],
                [Lexer::RPAREN,                       ')'],
            ],
        ];

        yield 'value with RFC escape sequence' => [
            '(sn=Lu\c4\8di\c4\87)',
            [
                [Lexer::LPAREN,                       '('],
                [Lexer::ATTRIBUTE_OR_ASSERTION_VALUE, 'sn'],
                [Lexer::EQUALS,                       '='],
                [Lexer::ATTRIBUTE_OR_ASSERTION_VALUE, 'Lu\c4\8di\c4\87'],
                [Lexer::RPAREN,                       ')'],
            ],
        ];

        yield 'value with pipe and ampersand characters' => [
            '(description=a|b&c)',
            [
                [Lexer::LPAREN,                       '('],
                [Lexer::ATTRIBUTE_OR_ASSERTION_VALUE, 'description'],
                [Lexer::EQUALS,                       '='],
                [Lexer::ATTRIBUTE_OR_ASSERTION_VALUE, 'a'],
                [Lexer::VERTBAR,                      '|'],
                [Lexer::ATTRIBUTE_OR_ASSERTION_VALUE, 'b'],
                [Lexer::AMPERSAND,                    '&'],
                [Lexer::ATTRIBUTE_OR_ASSERTION_VALUE, 'c'],
                [Lexer::RPAREN,                       ')'],
            ],
        ];
    }

    /**
     * @param list<array{string, string}> $expectedTokens
     *
     * @dataProvider provideFilterSequences
     */
    public function testFilterTokenSequence(string $input, array $expectedTokens): void
    {
        $lexer = new Lexer($input);
        $tokens = [];
        $lexer->moveNext();
        while (null !== $lexer->lookahead) {
            $tokens[] = [$lexer->lookahead->type, $lexer->lookahead->value];
            $lexer->moveNext();
        }

        $this->assertSame($expectedTokens, $tokens);
    }

    public function testTokenCount(): void
    {
        $lexer = new Lexer('(&(cn=alice)(ou=dev)(o=acme))');
        $count = 0;
        $lexer->moveNext();
        while (null !== $lexer->lookahead) {
            ++$count;
            $lexer->moveNext();
        }

        $this->assertSame(18, $count);
    }

    public function testResetOnNewInput(): void
    {
        $lexer = new Lexer('(cn=first)');
        $lexer->moveNext();
        $this->assertSame(Lexer::LPAREN, $lexer->lookahead?->type);

        $lexer->setInput('(sn=second)');
        $lexer->moveNext();
        $this->assertSame(Lexer::LPAREN, $lexer->lookahead?->type);

        $lexer->moveNext();
        $this->assertSame('sn', $lexer->lookahead?->value);
    }
}
