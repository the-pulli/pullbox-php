<?php

namespace Pulli\Pullbox;

use Pulli\Pullbox\Sounds\SystemSound;

class Music
{
    public static function exportPlaylist(string $name, string $to, string $format = 'xml'): void
    {
        $applescript = AppleScript::musicExportPlaylist($name, $to, $format);

        system("osascript -e '$applescript'");
    }

    public static function playSystemSound(SystemSound $sound): void
    {
        $file = $sound->filepath();

        system("afplay '$file'");
    }
}
