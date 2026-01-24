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
        $file = $sound->filepath();

        $process = proc_open(
            ['afplay', $file],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes
        );

        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);
    }
}
