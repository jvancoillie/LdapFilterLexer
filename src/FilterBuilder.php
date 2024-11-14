<?php

namespace Jvancoillie\LdapFilterLexer;

use Jvancoillie\LdapFilterLexer\Visitor\LdapExpressionVisitor;

class FilterBuilder
{
    public function __construct(private readonly ?Expression\Base $expression = null)
    {
    }

    public static function create(?string $stringFilter = null): self
    {
        $expression = null;

        if (null !== $stringFilter) {
            $filter = new Filter($stringFilter);
            $visitor = new LdapExpressionVisitor();
            $ast = $filter->getParser()->getAST();
            /**
             * @var Expression\Base $expression
             */
            $expression = $ast->accept($visitor);
        }

        return new self($expression);
    }

    public function equals(string $attribute, string $value): self
    {
        return new self(new Expression\Comparison($attribute, Expression\Comparison::EQUALS, $value));
    }

    public function greaterThan(string $attribute, string $value): self
    {
        return new self(new Expression\Comparison($attribute, Expression\Comparison::RANGLE_EQUALS, $value));
    }

    public function lowerThan(string $attribute, string $value): self
    {
        return new self(new Expression\Comparison($attribute, Expression\Comparison::LANGLE_EQUALS, $value));
    }

    public function approximate(string $attribute, string $value): self
    {
        return new self(new Expression\Comparison($attribute, Expression\Comparison::TILDE_EQUALS, $value));
    }

    public function andX(self|Expression\Base ...$x): self
    {
        $expressions = $this->processExpressions(...$x);

        return new self(new Expression\AndX(...$expressions));
    }

    public function orX(self|Expression\Base ...$x): self
    {
        $expressions = $this->processExpressions(...$x);

        return new self(new Expression\OrX(...$expressions));
    }

    public function not(self|Expression\Base $not): self
    {
        $expression = $this->processExpressions($not)[0];

        return new self(new Expression\Not($expression));
    }

    public function getExpression(): ?Expression\Base
    {
        return $this->expression;
    }

    public function getFilter(): Filter
    {
        return new Filter((string) $this->expression);
    }

    /**
     * @return array<Expression\Base>
     */
    private function processExpressions(self|Expression\Base ...$expressions): array
    {
        return array_map(function ($expr) {
            $expression = $expr instanceof self ? $expr->getExpression() : $expr;

            if (null === $expression) {
                throw new \InvalidArgumentException('Expression cannot be null');
            }

            return $expression;
        }, $expressions);
    }
}
