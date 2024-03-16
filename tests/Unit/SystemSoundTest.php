<?php

use Pulli\Pullbox\Sounds\SystemSound;

it('can generate the AppleScript to play a System Sound', function (SystemSound $sound, string $expectedSound) {
    $expected = "/System/Library/Sounds/$expectedSound.aiff";

    expect($sound->filepath())
        ->toBe($expected);
})->with([
    'Basso' => [SystemSound::Basso, 'Basso'],
    'Blow' => [SystemSound::Blow, 'Blow'],
    'Bottle' => [SystemSound::Bottle, 'Bottle'],
    'Frog' => [SystemSound::Frog, 'Frog'],
    'Funk' => [SystemSound::Funk, 'Funk'],
    'Glass' => [SystemSound::Glass, 'Glass'],
    'Hero' => [SystemSound::Hero, 'Hero'],
    'Morse' => [SystemSound::Morse, 'Morse'],
    'Ping' => [SystemSound::Ping, 'Ping'],
    'Pop' => [SystemSound::Pop, 'Pop'],
    'Purr' => [SystemSound::Purr, 'Purr'],
    'Sosumi' => [SystemSound::Sosumi, 'Sosumi'],
    'Submarine' => [SystemSound::Submarine, 'Submarine'],
    'Tink' => [SystemSound::Tink, 'Tink'],
])->group('mac');
