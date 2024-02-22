<?php

namespace Pulli\Pullbox;

class DEVONthink
{
    public static function pathToRecord(string $uuid): string
    {
        $applescript = AppleScript::devonthinkPathToRecord($uuid);

        $path = `osascript -e '$applescript'`;

        return chop($path);
    }

    public static function importRecords(array|string $paths): void
    {
        $paths = is_string($paths) ? [$paths] : $paths;

        foreach (AppleScript::devonthinkImportRecords($paths) as $applescript) {
            system("osascript -e '$applescript'");
        }
    }

    public static function savePlainTextToRecord(string $text, string $uuid): void
    {
        $applescript = AppleScript::devonthinkSavePlainTextToRecord($text, $uuid);

        system("osascript -e '$applescript'");
    }

    public static function getRecordContents(string $uuid): string
    {
        return file_get_contents(static::pathToRecord($uuid));
    }

    public static function open(string $uuid): void
    {
        $path = static::pathToRecord($uuid);

        system("open '$path'");
    }
}
