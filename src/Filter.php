<?php

namespace Jvancoillie\LdapFilterLexer;

class Filter
{
    public function __construct(private readonly string $filterString)
    {
    }

    public function getParser(): Parser
    {
        return new Parser($this);
    }

    public function getFilterString(): string
    {
        return $this->filterString;
    }

    public function isValid(): bool
    {
        try {
            $this->getParser()->getAST();
        } catch (\Exception) {
            return false;
        }

        return true;
    }

    public function __toString(): string
    {
        return $this->getFilterString();
    }
}
