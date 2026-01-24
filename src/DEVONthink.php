<?php

namespace Pulli\Pullbox;

class DEVONthink
{
    public static function pathToRecord(string $uuid): string
    {
        return AppleScript::executeAndCapture(AppleScript::devonthinkPathToRecord($uuid));
    }

    public static function importRecords(array|string $paths): void
    {
        $paths = is_string($paths) ? [$paths] : $paths;

        foreach (AppleScript::devonthinkImportRecords($paths) as $applescript) {
            AppleScript::execute($applescript);
        }
    }

    public static function savePlainTextToRecord(string $text, string $uuid): void
    {
        AppleScript::execute(AppleScript::devonthinkSavePlainTextToRecord($text, $uuid));
    }

    public static function getRecordContents(string $uuid): string
    {
        return file_get_contents(static::pathToRecord($uuid));
    }

    public static function open(string $uuid): void
    {
        $path = static::pathToRecord($uuid);

        $process = proc_open(
            ['open', $path],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes
        );

        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);
    }
}
