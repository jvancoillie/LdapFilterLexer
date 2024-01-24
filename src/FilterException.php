<?php

namespace Jvancoillie\LdapFilterLexer;

class FilterException extends \Exception
{
    public static function syntaxError(string $message, \Exception $previous = null): self
    {
        return new self('[Syntax Error] '.$message, 0, $previous);
    }
}
