<?php

namespace Jvancoillie\LdapFilterLexer\Tests;

use Jvancoillie\LdapFilterLexer\Filter;
use Jvancoillie\LdapFilterLexer\FilterBuilder;
use Jvancoillie\LdapFilterLexer\Value;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

class ValueTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function values(): iterable
    {
        yield 'plain' => ['Babs Jensen', 'Babs Jensen'];
        yield 'empty' => ['', ''];
        yield 'parentheses' => ['Babs (Jensen)', 'Babs \28Jensen\29'];
        yield 'wildcard' => ['Tim*', 'Tim\2a'];
        yield 'backslash' => ['a\b', 'a\5cb'];
        yield 'already escaped' => ['\2a', '\5c2a'];
        yield 'nul' => ["a\0b", 'a\00b'];
        yield 'injection' => ['*)(uid=*', '\2a\29\28uid=\2a'];
        yield 'utf-8' => ['Hélène', 'Hélène'];
    }

    #[DataProvider('values')]
    public function testEscape(string $value, string $escaped): void
    {
        $this->assertSame($escaped, Value::escape($value));
        $this->assertTrue((new Filter('(cn='.$escaped.')'))->isValid());
    }

    #[DataProvider('values')]
    #[RequiresPhpExtension('ldap')]
    public function testEscapeMatchesLdapEscape(string $value, string $escaped): void
    {
        $this->assertSame($escaped, ldap_escape($value, '', LDAP_ESCAPE_FILTER));
    }

    public function testEscapeCanKeepWildcards(): void
    {
        $this->assertSame('Tim*\28x\29', Value::escape('Tim*(x)', keepWildcards: true));
    }

    public function testInjectionStaysInsideTheValue(): void
    {
        $filter = FilterBuilder::create()->equals('uid', Value::escape('*)(uid=*'))->getFilter();

        $this->assertSame('(uid=\2a\29\28uid=\2a)', (string) $filter);
        $this->assertTrue($filter->isValid());
    }
}
