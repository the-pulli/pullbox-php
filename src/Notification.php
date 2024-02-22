<?php

namespace Pulli\Pullbox;

class Notification
{
    public static function display(string $message, ?string $title = null): void
    {
        $applescript = AppleScript::displayNotification($message, $title);

        system("osascript -e '$applescript'");
    }
}
