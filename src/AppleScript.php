<?php

namespace Pulli\Pullbox;

use Illuminate\Support\Collection;
use Pulli\Pullbox\Enums\PlaylistExportFormat;

class AppleScript
{
    public static function escapeString(string $value): string
    {
        return str_replace(
            ['\\', '"'],
            ['\\\\', '\\"'],
            $value
        );
    }

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
        $message = static::escapeString($message);

        $opts = Collection::make(array_merge([
            'buttons' => ['OK'],
            'defaultButton' => null,
            'cancelButton' => null,
            'answer' => false,
            'hiddenAnswer' => false,
            'icon' => null,
            'givingUpAfter' => null,
        ], $options));

        $buttons = Collection::make($opts->get('buttons'))->map(fn (string $b) => static::escapeString($b));

        $parts = [];
        $parts[] = sprintf('buttons {"%s"}', $buttons->join('", "'));

        $defaultButton = $opts->get('defaultButton');
        if ($defaultButton !== null) {
            $parts[] = is_int($defaultButton)
                ? sprintf('default button %d', $defaultButton)
                : sprintf('default button "%s"', static::escapeString($defaultButton));
        }

        $cancelButton = $opts->get('cancelButton');
        if ($cancelButton !== null) {
            $parts[] = is_int($cancelButton)
                ? sprintf('cancel button %d', $cancelButton)
                : sprintf('cancel button "%s"', static::escapeString($cancelButton));
        }

        $hasAnswer = $opts->get('answer') !== false;
        if ($hasAnswer) {
            $answerText = $opts->get('answer') === true ? '' : static::escapeString($opts->get('answer'));
            $parts[] = sprintf('default answer "%s"', $answerText);
            if ($opts->get('hiddenAnswer')) {
                $parts[] = 'with hidden answer';
            }
        }

        if ($title !== null && $title !== '') {
            $title = static::escapeString($title);
            $parts[] = sprintf('with title "%s"', $title);
        }

        $icon = $opts->get('icon');
        if ($icon !== null) {
            $parts[] = sprintf('with icon %s', $icon);
        }

        $givingUpAfter = $opts->get('givingUpAfter');
        if ($givingUpAfter !== null) {
            $parts[] = sprintf('giving up after %d', (int) $givingUpAfter);
        }

        $dialogParams = implode(' ', $parts);
        $returnLogic = $hasAnswer
            ? 'return text returned of theReturnedValue'
            : 'return button returned of theReturnedValue';

        return <<<APPLESCRIPT
        $intro
        try
            set theReturnedValue to (display dialog "$message" $dialogParams)
            $returnLogic
        on error errorMessage number errorNumber
            if errorNumber is equal to -128 then
                return
            end if
        end try
        APPLESCRIPT;
    }

    public static function displayNotification(string $message, ?string $title = null): string
    {
        $intro = static::intro();
        $message = static::escapeString($message);

        if (! is_null($title) && $title !== '') {
            $title = static::escapeString($title);

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
        $name = static::escapeString($name);
        $to = static::escapeString($to);

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
        $uuid = static::escapeString($uuid);

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
            $escapedPath = static::escapeString($path);
            $scripts[] = <<<APPLESCRIPT
            $intro
            try
                tell application id "DNtp"
                    set theRecord to import "$escapedPath"
                    if (theRecord is missing value) then
                        display dialog "File: $escapedPath could not be imported." with title "Error"
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
        $text = static::escapeString($text);
        $uuid = static::escapeString($uuid);

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
        $name = static::escapeString($name);
        $path = static::escapeString($path);
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
