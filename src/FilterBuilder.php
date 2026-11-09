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
        $expressions = $this->collectOperands(...$x);

        return new self(1 === \count($expressions) ? $expressions[0] : new Expression\AndX(...$expressions));
    }

    public function orX(self|Expression\Base ...$x): self
    {
        $expressions = $this->collectOperands(...$x);

        return new self(1 === \count($expressions) ? $expressions[0] : new Expression\OrX(...$expressions));
    }

    public function extensible(string $value, ?string $attribute = null, ?string $matchingRule = null, bool $dnAttributes = false): self
    {
        return new self(new Expression\ExtensibleMatch($attribute, $dnAttributes, $matchingRule, $value));
    }

    public function not(self|Expression\Base|null $not = null): self
    {
        $expression = null !== $not
            ? $this->processExpressions($not)[0]
            : $this->expression;

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

    /**
     * Operands of an AND or OR: the current expression first, if any. A single operand is used as is,
     * since "(&(a=b))" and "(|(a=b))" are invalid (RFC 4515 requires at least 2 conditions).
     *
     * @return non-empty-list<Expression\Base>
     */
    private function collectOperands(self|Expression\Base ...$x): array
    {
        $expressions = array_values($this->processExpressions(...$x));

        if (null !== $this->expression) {
            array_unshift($expressions, $this->expression);
        }

        if ([] === $expressions) {
            throw new \InvalidArgumentException('AND and OR filters require at least 1 expression');
        }

        return $expressions;
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
