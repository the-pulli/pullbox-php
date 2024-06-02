<?php

namespace Pulli\Pullbox;

class System
{
    public static function applicationsFolder(): string
    {
        $applescript = AppleScript::applicationsFolder();

        return trim(`osascript -e '$applescript'`);
    }

    public static function moveApp(string $name, string $path, bool $launch = true): void
    {
        $applescript = AppleScript::moveApp($name, $path, $launch);

        system("osascript -e '$applescript'");
    }
}
