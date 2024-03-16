<?php

namespace Pulli\Pullbox\Exceptions;

class NotRunningMacException extends \RuntimeException
{
    protected $message = 'You\'re not running macOS.';
}
