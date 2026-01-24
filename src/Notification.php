<?php

namespace Pulli\Pullbox;

class Notification
{
    public static function display(string $message, ?string $title = null, ?string $subtitle = null, ?string $soundName = null): void
    {
        AppleScript::execute(
            AppleScript::displayNotification($message, $title, $subtitle, $soundName)
        );
    }
}
