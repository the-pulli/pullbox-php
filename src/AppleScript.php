<?php

namespace Pulli\Pullbox;

use Illuminate\Support\Collection;
use Pulli\Pullbox\Enums\PlaylistExportFormat;

class AppleScript
{
    public static function intro(): string
    {
        return <<<'APPLESCRIPT'
        use AppleScript version "2.8" -- Latest AppleScript Version
        use scripting additions

        APPLESCRIPT;
    }

    public static function displayDialog(string $message, ?string $title = null, array $options = []): string
    {
        $intro = static::intro();
        $defaults = Collection::make(array_merge(['answer' => false, 'buttons' => ['OK']], $options));
        $answer = $defaults->get('answer') ? ' default answer ""' : '';
        $buttons = Collection::make($defaults->get('buttons', []));
        $defaultButton = $buttons->first();
        $cancelButton = $buttons->last();

        $buttonString = sprintf('buttons {"%s"} default button "%s" cancel button "%s"', $buttons->join('", "'), $defaultButton, $cancelButton);

        return <<<APPLESCRIPT
        $intro
        try
            set theReturnedValue to (display dialog "$message" $buttonString$answer with title "$title")
            if button returned of theReturnedValue is "$defaultButton" then
                if text returned of theReturnedValue is not "" then
                    return text returned of theReturnedValue
                else
                    return
                end if
            end if
        on error errorMessage number errorNumber
            if errorNumber is equal to -128 -- aborted by user
                return
            end if
        end try
        APPLESCRIPT;
    }

    public static function displayNotification(string $message, ?string $title = null): string
    {
        $intro = static::intro();

        if (! is_null($title) && $title !== '') {
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

    public static function musicExportPlaylist(string $name, string $to, string|PlaylistExportFormat $format = PlaylistExportFormat::XML): string
    {
        $format = PlaylistExportFormat::fromInput($format)->value;

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

    public static function applicationsFolder(): string
    {
        $intro = static::intro();

        return <<<APPLESCRIPT
          $intro
          set theApplicationsFolder to path to applications folder
          return (POSIX path) of theApplicationsFolder
        APPLESCRIPT;
    }

    public static function moveApp(string $name, string $path, bool $launch = true): string
    {
        $intro = static::intro();
        $launch = $launch ? 'true' : 'false';

        return <<<APPLESCRIPT
          $intro
          set theApplicationsFolder to path to applications folder
          try
            tell application "$name" to quit
          on error errMsg
          end try
          delay 3
          tell application "Finder"
            move (POSIX file "$path") as alias to theApplicationsFolder with replacing
          end tell
          if $launch then
            tell application "$name" to activate
          end if
        APPLESCRIPT;
    }
}
