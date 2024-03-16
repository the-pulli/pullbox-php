<?php

namespace Pulli\Pullbox\Sounds;

use Pulli\Pullbox\Exceptions\NotRunningMacException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

enum SystemSound: string
{
    case Basso = 'Basso';
    case Blow = 'Blow';
    case Bottle = 'Bottle';
    case Frog = 'Frog';
    case Funk = 'Funk';
    case Glass = 'Glass';
    case Hero = 'Hero';
    case Morse = 'Morse';
    case Ping = 'Ping';
    case Pop = 'Pop';
    case Purr = 'Purr';
    case Sosumi = 'Sosumi';
    case Submarine = 'Submarine';
    case Tink = 'Tink';

    /**
     * @throws NotRunningMacException
     */
    public function filepath(): string
    {
        $systemSoundDir = '/System/Library/Sounds';
        $suffix = '.aiff';

        return !file_exists($systemSoundDir) ? throw new NotRunningMacException() : collect(
            Finder::create()
                ->in($systemSoundDir)
                ->name("*$suffix")
                ->files()
        )
            ->flatMap(fn (SplFileInfo $fileInfo) => [$fileInfo->getBasename($suffix) => $fileInfo->getRealPath()])
            ->get($this->value);

    }
}
