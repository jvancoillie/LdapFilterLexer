<?php

namespace Jvancoillie\LdapFilterLexer\Tests;

use Jvancoillie\LdapFilterLexer\Expression;
use Jvancoillie\LdapFilterLexer\FilterBuilder;
use PHPUnit\Framework\TestCase;

class FilterBuilderTest extends TestCase
{
    public function testEquals()
    {
        $filterBuilder = FilterBuilder::create();

        $filter = $filterBuilder->equals('givename', 'Jensen')->getFilter();

        $this->assertEquals('(givename=Jensen)', (string) $filter);
    }

    public function testGreaterThan()
    {
        $filterBuilder = FilterBuilder::create();

        $filter = $filterBuilder->greaterThan('age', '30')->getFilter();

        $this->assertEquals('(age>=30)', (string) $filter);
    }

    public function testLowerThan()
    {
        $filterBuilder = FilterBuilder::create();

        $filter = $filterBuilder->lowerThan('age', '30')->getFilter();

        $this->assertEquals('(age<=30)', (string) $filter);
    }

    public function testApproximate()
    {
        $filterBuilder = FilterBuilder::create();

        $filter = $filterBuilder->approximate('email', 'example@domain.com')->getFilter();

        $this->assertEquals('(email~=example@domain.com)', (string) $filter);
    }

    public function testAndX()
    {
        $filterBuilder = FilterBuilder::create();

        $filter = $filterBuilder->andX(
            $filterBuilder->equals('givename', 'Jensen'),
            $filterBuilder->equals('uid', 'bJensen')
        )->getFilter();

        $this->assertEquals('(&(givename=Jensen)(uid=bJensen))', (string) $filter);
    }

    public function testOrX()
    {
        $filterBuilder = FilterBuilder::create();

        $filter = $filterBuilder->orX(
            $filterBuilder->equals('givename', 'Jensen'),
            $filterBuilder->equals('uid', 'bJensen')
        )->getFilter();

        $this->assertEquals('(|(givename=Jensen)(uid=bJensen))', (string) $filter);
    }

    public function testNot()
    {
        $filterBuilder = FilterBuilder::create();

        $filter = $filterBuilder->not($filterBuilder->equals('uid', 'bJensen'))->getFilter();

        $this->assertEquals('(!(uid=bJensen))', (string) $filter);
    }

    public function testCreateWithStringFilter()
    {
        $stringFilter = '(cn=John Doe)';

        $filterBuilder = FilterBuilder::create($stringFilter);

        $expression = $filterBuilder->getExpression();

        $this->assertNotNull($expression);
        $this->assertInstanceOf(Expression\Base::class, $expression);

        $this->assertInstanceOf(Expression\Comparison::class, $expression);
    }

    public function testInvalidArgumentExceptionOnNullExpression()
    {
        $this->expectException(\InvalidArgumentException::class);

        $filterBuilder = FilterBuilder::create();
        $filterBuilder->andX(
            $filterBuilder->equals('givename', 'Jensen'),
            FilterBuilder::create()
        );
    }

    public function testAndXChaining(): void
    {
        $filter = FilterBuilder::create()
            ->equals('cn', 'Jensen')
            ->andX(FilterBuilder::create()->equals('uid', 'bJensen'))
            ->getFilter();

        $this->assertSame('(&(cn=Jensen)(uid=bJensen))', (string) $filter);
    }

    public function testOrXChaining(): void
    {
        $filter = FilterBuilder::create()
            ->equals('cn', 'Jensen')
            ->orX(FilterBuilder::create()->equals('uid', 'bJensen'))
            ->getFilter();

        $this->assertSame('(|(cn=Jensen)(uid=bJensen))', (string) $filter);
    }

    public function testNotChainingWithoutArgument(): void
    {
        $filter = FilterBuilder::create()
            ->equals('uid', 'bJensen')
            ->not()
            ->getFilter();

        $this->assertSame('(!(uid=bJensen))', (string) $filter);
    }

    public function testNotThrowsWhenNoExpressionAvailable(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        FilterBuilder::create()->not();
    }

    /** @dataProvider extensibleProvider */
    public function testExtensible(string $expected, string $value, ?string $attribute, ?string $matchingRule, bool $dn): void
    {
        $filter = FilterBuilder::create()
            ->extensible($value, $attribute, $matchingRule, $dn)
            ->getFilter();

        $this->assertSame($expected, (string) $filter);
    }

    public static function extensibleProvider(): \Generator
    {
        yield 'attr only' => ['(cn:=Betty Rubble)', 'Betty Rubble', 'cn', null, false];
        yield 'attr + matchingRule' => ['(cn:caseExactMatch:=Fred Flintstone)', 'Fred Flintstone', 'cn', 'caseExactMatch', false];
        yield 'attr + dn' => ['(o:dn:=Ace Industry)', 'Ace Industry', 'o', null, true];
        yield 'attr + dn + matchingRule' => ['(sn:dn:2.4.6.8.10:=Barney Rubble)', 'Barney Rubble', 'sn', '2.4.6.8.10', true];
        yield 'matchingRule only' => ['(:1.2.3:=Wilma Flintstone)', 'Wilma Flintstone', null, '1.2.3', false];
        yield 'AD recursive membership' => ['(member:1.2.840.113556.1.4.1941:=CN=John,DC=example,DC=com)', 'CN=John,DC=example,DC=com', 'member', '1.2.840.113556.1.4.1941', false];
    }
}
