<?php

namespace Jvancoillie\LdapFilterLexer;

class FilterBuilder
{
    public function __construct(private readonly ?Expression\Base $expression = null)
    {
    }

    public static function create(): self
    {
        return new self();
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
        $expressions = array_map(function ($expr) {
            $expression = $expr instanceof self ? $expr->getExpression() : $expr;

            if (null === $expression) {
                throw new \InvalidArgumentException('Expression cannot be null');
            }

            return $expression;
        }, $x);

        return new self(new Expression\AndX(...$expressions));
    }

    public function orX(self|Expression\Base ...$x): self
    {
        $expressions = array_map(function ($expr) {
            $expression = $expr instanceof self ? $expr->getExpression() : $expr;

            if (null === $expression) {
                throw new \InvalidArgumentException('Expression cannot be null');
            }

            return $expression;
        }, $x);

        return new self(new Expression\OrX(...$expressions));
    }

    public function not(self|Expression\Base $not): self
    {
        $expression = $not instanceof self ? $not->getExpression() : $not;

        if (null === $expression) {
            throw new \InvalidArgumentException('Expression cannot be null');
        }

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
}
