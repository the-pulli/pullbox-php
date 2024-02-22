<?php

namespace Pulli\Pullbox;

class Music
{
    public static function exportPlaylist(string $name, string $to, string $format = 'xml'): void
    {
        $applescript = AppleScript::musicExportPlaylist($name, $to, $format);

        system("osascript -e '$applescript'");
    }
}
