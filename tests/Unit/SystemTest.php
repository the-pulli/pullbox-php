<?php

use Pulli\Pullbox\Notification;
use Pulli\Pullbox\System;

it('can get the applications folder path', function () {
    $path = System::applicationsFolder();

    expect($path)
        ->toBeString()
        ->toContain('Applications');
})->group('mac');

it('can get the version number of an app', function () {
    $version = System::versionNumber('Finder');

    expect($version)
        ->toBeString()
        ->not->toBeEmpty();
})->group('mac');

it('can display a notification without throwing', function () {
    Notification::display('Pullbox test notification', 'Pullbox');

    expect(true)->toBeTrue();
})->group('mac');
