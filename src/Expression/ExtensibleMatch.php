<?php

namespace Jvancoillie\LdapFilterLexer\Expression;

class ExtensibleMatch extends Base
{
    public function __construct(
        private readonly ?string $attribute,
        private readonly bool $dnAttributes,
        private readonly ?string $matchingRule,
        private readonly string $value,
    ) {
    }

    public function __toString(): string
    {
        $filter = $this->leftParenthesis;
        $filter .= $this->attribute ?? '';

        if ($this->dnAttributes) {
            $filter .= ':dn';
        }

        if (null !== $this->matchingRule) {
            $filter .= ':'.$this->matchingRule;
        }

        $filter .= ':='.$this->value.$this->rightParenthesis;

        return $filter;
    }
}
