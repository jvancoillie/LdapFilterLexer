<?php

namespace Jvancoillie\LdapFilterLexer;

use Jvancoillie\LdapFilterLexer\AST\AndNode;
use Jvancoillie\LdapFilterLexer\AST\AssertionValueNode;
use Jvancoillie\LdapFilterLexer\AST\AttributeNode;
use Jvancoillie\LdapFilterLexer\AST\FilterTypeNode;
use Jvancoillie\LdapFilterLexer\AST\Node;
use Jvancoillie\LdapFilterLexer\AST\NotNode;
use Jvancoillie\LdapFilterLexer\AST\OrNode;
use Jvancoillie\LdapFilterLexer\AST\SimpleNode;

class Parser
{
    private Lexer $lexer;
    private Filter $filter;

    public function __construct(Filter $filter)
    {
        $this->filter = $filter;
        $this->lexer = new Lexer((string) $filter);
    }

    /**
     * @throws FilterException
     */
    public function getAST(): Node
    {
        $this->lexer->moveNext();

        $node = $this->parseExpression();

        if (null !== $this->lexer->lookahead) {
            $this->syntaxError('Expected End of filter');
        }

        return $node;
    }

    /**
     * @throws FilterException
     */
    private function parseExpression(): Node
    {
        $this->match(Lexer::LPAREN);
        $node = $this->parseLogicalOperator();
        $this->match(Lexer::RPAREN);

        return $node;
    }

    /**
     * @throws FilterException
     */
    private function parseLogicalOperator(): Node
    {
        return match ($this->lexer->lookahead->type ?? null) {
            Lexer::AMPERSAND => $this->parseAndCondition(),
            Lexer::VERTBAR => $this->parseOrCondition(),
            Lexer::EXCLAMATION => $this->parseNotCondition(),
            default => $this->parseFilter(),
        };
    }

    /**
     * @throws FilterException
     */
    private function parseAndCondition(): Node
    {
        $this->match(Lexer::AMPERSAND);

        $conditions = [];

        while (!$this->lexer->isNextToken(Lexer::RPAREN)) {
            $conditions[] = $this->parseExpression();
        }

        return new AndNode($conditions);
    }

    /**
     * @throws FilterException
     */
    private function parseOrCondition(): Node
    {
        $this->match(Lexer::VERTBAR);
        $conditions = [];

        while (!$this->lexer->isNextToken(Lexer::RPAREN)) {
            $conditions[] = $this->parseExpression();
        }

        return new OrNode($conditions);
    }

    /**
     * @throws FilterException
     */
    private function parseNotCondition(): Node
    {
        $this->match(Lexer::EXCLAMATION);
        $conditions = [];

        while (!$this->lexer->isNextToken(Lexer::RPAREN)) {
            if (!empty($conditions)) {
                $this->syntaxError(Lexer::RPAREN);
            }
            $conditions[] = $this->parseExpression();
        }

        return new NotNode($conditions[0]);
    }

    /**
     * @throws FilterException
     */
    private function parseFilter(): Node
    {
        $attribute = $this->getAttribute();
        $operator = $this->getOperator();
        $assertionValue = $this->getAssertionValue();

        return new SimpleNode($attribute, $operator, $assertionValue);
    }

    private function getAttribute(): AttributeNode
    {
        $value = $this->lexer->lookahead->value ?? null;

        if (null === $value) {
            $this->syntaxError('attribute cannot be null');
        }

        $this->match(Lexer::ATTRIBUTE_OR_ASSERTION_VALUE);

        return new AttributeNode($value ?? '');
    }

    private function getAssertionValue(): AssertionValueNode
    {
        $value = null;

        if (!$this->lexer->isNextToken(Lexer::RPAREN)) {
            $value = $this->lexer->lookahead->value ?? null;

            $this->match(Lexer::ATTRIBUTE_OR_ASSERTION_VALUE);
        }

        return new AssertionValueNode($value);
    }

    /**
     * @throws FilterException
     */
    private function match(string $token): void
    {
        $lookaheadType = $this->lexer->lookahead->type ?? null;

        // Short-circuit on first condition, usually types match
        if ($lookaheadType !== $token) {
            $this->syntaxError((string) $this->lexer->getLiteral($token));
        }

        $this->lexer->moveNext();
    }

    private function getOperator(): FilterTypeNode
    {
        $token = $this->lexer->lookahead;

        switch ($token->type ?? null) {
            case Lexer::EQUALS:
                $this->match(Lexer::EQUALS);
                break;
            case Lexer::TILDE_EQUALS:
                $this->match(Lexer::TILDE_EQUALS);
                break;
            case Lexer::RANGLE_EQUALS:
                $this->match(Lexer::RANGLE_EQUALS);
                break;
            case Lexer::LANGLE_EQUALS:
                $this->match(Lexer::LANGLE_EQUALS);
                break;
            default:
                $this->syntaxError('filter type (~=, =, <=, >=)');
        }

        return new FilterTypeNode($token->value ?? '');
    }

    /**
     * @throws FilterException
     */
    private function syntaxError(string $expected = ''): void
    {
        $token = $this->lexer->lookahead;

        $tokenPos = $token->position ?? '-1';

        $message = sprintf('line 0, col %d: Error: ', $tokenPos);
        $message .= '' !== $expected ? sprintf('Expected %s, got ', $expected) : 'Unexpected ';
        $message .= null === $this->lexer->lookahead ? 'end of string.' : sprintf("'%s'", $token->value ?? '-');
        $message .= sprintf(" on query '%s'", $this->filter);

        throw FilterException::syntaxError($message);
    }
}
