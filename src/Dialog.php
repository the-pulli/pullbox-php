<?php

namespace Pulli\Pullbox;

class Dialog
{
    public static function display(string $message, ?string $title, array $options = []): ?string
    {
        $answer = AppleScript::executeAndCapture(
            AppleScript::displayDialog($message, $title, $options)
        );

        if (empty($answer)) {
            return null;
        }

        return $answer;
    }
}
