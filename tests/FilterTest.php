<?php

namespace Jvancoillie\LdapFilterLexer\Tests;

use Jvancoillie\LdapFilterLexer\Filter;
use PHPUnit\Framework\TestCase;

class FilterTest extends TestCase
{
    /** @dataProvider  validFilters */
    public function testFilterIsValid(string $filter)
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
        ];

        foreach ($filters as $filter) {
            yield $filter => [$filter];
        }
    }
}
