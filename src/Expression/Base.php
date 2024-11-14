<?php

namespace Jvancoillie\LdapFilterLexer\Expression;

abstract class Base implements \Stringable
{
    protected string $leftParenthesis = '(';
    protected string $rightParenthesis = ')';
}
