<?php

namespace Pulli\Pullbox;

class Dialog
{
    public static function display(string $message, ?string $title, array $options = []): ?string
    {
        $applescript = AppleScript::displayDialog($message, $title, $options);

        $answer = trim(`osascript -e '$applescript'`);

        if (empty($answer)) {
            return null;
        }

        return $answer;
    }
}
