<?php

namespace Pulli\Pullbox;

class Dialog
{
    public static function display(string $message, ?string $title, array $options = []): ?string
    {
        $applescript = AppleScript::displayDialog($message, $title, $options);

        return trim(`osascript -e '$applescript'`);
    }
}
