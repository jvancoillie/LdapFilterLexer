<?php

namespace Jvancoillie\LdapFilterLexer\Tests;

use Jvancoillie\LdapFilterLexer\Expression;
use Jvancoillie\LdapFilterLexer\FilterBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FilterBuilderTest extends TestCase
{
    public function testEquals(): void
    {
        $filterBuilder = FilterBuilder::create();

        $filter = $filterBuilder->equals('givename', 'Jensen')->getFilter();

        $this->assertEquals('(givename=Jensen)', (string) $filter);
    }

    /**
     * @return iterable<string, array{FilterBuilder, string}>
     */
    public static function escapingMethods(): iterable
    {
        $qb = FilterBuilder::create();

        yield 'present' => [$qb->present('mail'), '(mail=*)'];
        yield 'startsWith' => [$qb->startsWith('cn', 'Ba'), '(cn=Ba*)'];
        yield 'startsWith escapes' => [$qb->startsWith('cn', 'Ba(*'), '(cn=Ba\28\2a*)'];
        yield 'endsWith' => [$qb->endsWith('cn', 'sen'), '(cn=*sen)'];
        yield 'endsWith escapes' => [$qb->endsWith('cn', 'a\b)'), '(cn=*a\5cb\29)'];
        yield 'contains' => [$qb->contains('cn', 'ab'), '(cn=*ab*)'];
        yield 'contains escapes' => [$qb->contains('cn', 'ab*'), '(cn=*ab\2a*)'];
        yield 'contains injection' => [$qb->contains('uid', '*)(uid=*'), '(uid=*\2a\29\28uid=\2a*)'];
    }

    #[DataProvider('escapingMethods')]
    public function testEscapingMethods(FilterBuilder $builder, string $expected): void
    {
        $filter = $builder->getFilter();

        $this->assertSame($expected, (string) $filter);
        $this->assertTrue($filter->isValid());
    }

    public function testEscapingMethodsCompose(): void
    {
        $qb = FilterBuilder::create();

        $filter = $qb->andX($qb->present('mail'), $qb->orX($qb->startsWith('cn', 'Ba'), $qb->contains('sn', '(x)')))->getFilter();

        $this->assertSame('(&(mail=*)(|(cn=Ba*)(sn=*\28x\29*)))', (string) $filter);
    }

    public function testGreaterThan(): void
    {
        $filterBuilder = FilterBuilder::create();

        $filter = $filterBuilder->greaterThan('age', '30')->getFilter();

        $this->assertEquals('(age>=30)', (string) $filter);
    }

    public function testLowerThan(): void
    {
        $filterBuilder = FilterBuilder::create();

        $filter = $filterBuilder->lowerThan('age', '30')->getFilter();

        $this->assertEquals('(age<=30)', (string) $filter);
    }

    public function testApproximate(): void
    {
        $filterBuilder = FilterBuilder::create();

        $filter = $filterBuilder->approximate('email', 'example@domain.com')->getFilter();

        $this->assertEquals('(email~=example@domain.com)', (string) $filter);
    }

    public function testAndX(): void
    {
        $filterBuilder = FilterBuilder::create();

        $filter = $filterBuilder->andX(
            $filterBuilder->equals('givename', 'Jensen'),
            $filterBuilder->equals('uid', 'bJensen')
        )->getFilter();

        $this->assertEquals('(&(givename=Jensen)(uid=bJensen))', (string) $filter);
    }

    public function testOrX(): void
    {
        $filterBuilder = FilterBuilder::create();

        $filter = $filterBuilder->orX(
            $filterBuilder->equals('givename', 'Jensen'),
            $filterBuilder->equals('uid', 'bJensen')
        )->getFilter();

        $this->assertEquals('(|(givename=Jensen)(uid=bJensen))', (string) $filter);
    }

    public function testNot(): void
    {
        $filterBuilder = FilterBuilder::create();

        $filter = $filterBuilder->not($filterBuilder->equals('uid', 'bJensen'))->getFilter();

        $this->assertEquals('(!(uid=bJensen))', (string) $filter);
    }

    public function testCreateWithStringFilter(): void
    {
        $stringFilter = '(cn=John Doe)';

        $filterBuilder = FilterBuilder::create($stringFilter);

        $expression = $filterBuilder->getExpression();

        $this->assertNotNull($expression);
        $this->assertInstanceOf(Expression\Base::class, $expression);

        $this->assertInstanceOf(Expression\Comparison::class, $expression);
    }

    public function testInvalidArgumentExceptionOnNullExpression(): void
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

    public function testOrXWithASingleExpressionReturnsThatExpression(): void
    {
        $filter = FilterBuilder::create()->orX(FilterBuilder::create()->equals('uid', 'bJensen'))->getFilter();

        $this->assertSame('(uid=bJensen)', (string) $filter);
        $this->assertTrue($filter->isValid());
    }

    public function testAndXWithASingleExpressionReturnsThatExpression(): void
    {
        $filter = FilterBuilder::create()->andX(FilterBuilder::create()->equals('uid', 'bJensen'))->getFilter();

        $this->assertSame('(uid=bJensen)', (string) $filter);
        $this->assertTrue($filter->isValid());
    }

    public function testOrXWithoutExpressionThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        FilterBuilder::create()->orX();
    }

    public function testAndXWithoutExpressionThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        FilterBuilder::create()->andX();
    }

    public function testCreateAcceptsAnEmptyAssertionValue(): void
    {
        $this->assertSame('(cn=)', (string) FilterBuilder::create('(cn=)')->getFilter());
        $this->assertSame('(cn~=)', (string) FilterBuilder::create('(cn~=)')->getFilter());
    }

    #[DataProvider('extensibleProvider')]
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
