<?php

namespace Jvancoillie\LdapFilterLexer\Tests;

use Jvancoillie\LdapFilterLexer\Filter;
use PHPUnit\Framework\TestCase;

class FilterTest extends TestCase
{
    /** @dataProvider  validFilters */
    public function testFilterIsValid(string $filter): void
    {
        $filter = new Filter($filter);

        $this->assertTrue($filter->isValid());
    }

    public static function validFilters(): \Generator
    {
        $filters = [
            '(cn=Babs Jensen)',
            '(!(cn=Tim Howes))',
            '(&(objectClass=Person)(|(sn=Jensen)(cn=Babs J*)))',
            '(o=univ*of*mich*)',
            '(seeAlso=)',
            '(cn:caseExactMatch:=Fred Flintstone)',
            '(cn:=Betty Rubble)',
            '(sn:dn:2.4.6.8.10:=Barney Rubble)',
            '(o:dn:=Ace Industry)',
            '(:1.2.3:=Wilma Flintstone)',
            '(:DN:2.4.6.8.10:=Dino)',
            '(o=Parens R Us \28for all your parenthetical needs\29)',
            '(cn=*\2A*)',
            '(filename=C:\5cMyFile)',
            '(bin=\00\00\00\04)',
            '(sn=Lu\c4\8di\c4\87)',
            '(1.3.6.1.4.1.1466.0=\04\02\48\69)',
            '(attr=chainA*|DEC|)',
            '(attr=val|ue)',
            '(attr=foo&bar)',
            '(attr=foo!bar)',
        ];

        foreach ($filters as $filter) {
            yield $filter => [$filter];
        }
    }

    /** @dataProvider invalidFilters */
    public function testFilterIsInvalid(string $filter): void
    {
        $this->assertFalse((new Filter($filter))->isValid());
    }

    public static function invalidFilters(): \Generator
    {
        $filters = [
            '(sn*jdoe*)',
            'cn=value',
            '(cn=value',
            '(!(cn=john*)(cn=doe*)',
            '(attr =value)',
        ];

        foreach ($filters as $filter) {
            yield $filter => [$filter];
        }
    }

    public function testIsValidDoesNotSwallowNonFilterExceptions(): void
    {
        $this->expectException(\RuntimeException::class);

        $filter = new class('(cn=test)') extends Filter {
            public function getParser(): \Jvancoillie\LdapFilterLexer\Parser
            {
                throw new \RuntimeException('unexpected internal error');
            }
        };

        $filter->isValid();
    }
}
