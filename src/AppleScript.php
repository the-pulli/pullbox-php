<?php

namespace Pulli\Pullbox;

class AppleScript
{
    public static function intro(): string
    {
        return <<<APPLESCRIPT
        use AppleScript version "2.8" -- Latest AppleScript Version
        use scripting additions

        APPLESCRIPT;
    }

    public static function displayNotification(string $message, ?string $title = null): string
    {
        $intro = static::intro();

        if (!is_null($title) && $title !== '') {
            return <<<APPLESCRIPT
            $intro
            display notification "$message" with title "$title"
            APPLESCRIPT;
        }

        return <<<APPLESCRIPT
        $intro
        display notification "$message"
        APPLESCRIPT;
    }

    public static function musicExportPlaylist(string $name, string $to, string $format = 'xml'): string
    {
        $formats = [
            'm3u' => 'M3U',
            'm3u8' => 'M3U8',
            'plain_text' => 'plain text',
            'unicode_text' => 'Unicode text',
            'xml' => 'XML',
        ];

        $format = $formats[$format] ?? 'XML';
        $intro = static::intro();

        return <<<APPLESCRIPT
        $intro
        try
            tell application "Music" to export playlist "$name" as $format to "$to"
        on error errMsg
            display dialog errMsg with title "Error"
        end try
        APPLESCRIPT;
    }

    public static function devonthinkPathToRecord(string $uuid): string
    {
        $intro = static::intro();

        return <<<APPLESCRIPT
        $intro
        tell application id "DNtp"
            set theRecord to get record with uuid "$uuid"
            if (type of theRecord as string) is not in {"group", "smart group", "tag"} then return (path of theRecord as string)
        end tell
        APPLESCRIPT;
    }

    public static function devonthinkImportRecords(array $paths): array
    {
        $intro = static::intro();
        $scripts = [];

        foreach ($paths as $path) {
            $scripts[] = <<<APPLESCRIPT
            $intro
            try
                tell application id "DNtp"
                    set theRecord to import "$path"
                    if (theRecord is missing value) then
                        display dialog "File: $path could not be imported." with title "Error"
                    end if
                end tell
            on error errMsg
                display dialog errMsg with title "Error"
            end try
            APPLESCRIPT;
        }

        return $scripts;
    }

    public static function devonthinkSavePlainTextToRecord(string $text, string $uuid): string
    {
        $intro = static::intro();

        return <<<APPLESCRIPT
        $intro
        tell application id "DNtp"
            set theRecord to get record with uuid "$uuid"
            set plain text of theRecord to "$text"
        end tell
        APPLESCRIPT;
    }
}
