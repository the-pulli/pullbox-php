<?php

namespace Pulli\Pullbox;

use Pulli\Pullbox\Enums\SystemSound;

class Music
{
    public static function exportPlaylist(string $name, string $to, string $format = 'xml'): void
    {
        AppleScript::execute(AppleScript::musicExportPlaylist($name, $to, $format));
    }

    public static function playSystemSound(SystemSound $sound): void
    {
        AppleScript::runCommand(['afplay', $sound->filepath()]);
    }
}
