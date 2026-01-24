<?php

use Pulli\Pullbox\AppleScript;
use Pulli\Pullbox\System;

beforeEach(function () {
    AppleScript::fake();
});

afterEach(function () {
    AppleScript::unfake();
});

it('generates the correct AppleScript for applicationsFolder', function () {
    System::applicationsFolder();

    expect(AppleScript::lastScript())
        ->toContain('use AppleScript version "2.8"')
        ->toContain('set theApplicationsFolder to path to applications folder')
        ->toContain('return (POSIX path) of theApplicationsFolder');
});

it('generates the correct AppleScript for moveApp', function () {
    System::moveApp('MyApp', '/tmp/MyApp.app', true);

    expect(AppleScript::lastScript())
        ->toContain('tell application "MyApp.app" to quit')
        ->toContain('move (POSIX file "/tmp/MyApp.app") as alias to theApplicationsFolder with replacing')
        ->toContain('if true then')
        ->toContain('tell application "MyApp.app" to activate');
});

it('generates the correct AppleScript for moveApp without launch', function () {
    System::moveApp('MyApp.app', '/tmp/MyApp.app', false);

    expect(AppleScript::lastScript())
        ->toContain('if false then');
});

it('appends .app extension if missing', function () {
    System::moveApp('Firefox', '/tmp/Firefox.app');

    expect(AppleScript::lastScript())
        ->toContain('tell application "Firefox.app" to quit');
});

it('does not double-append .app extension', function () {
    System::moveApp('Firefox.app', '/tmp/Firefox.app');

    expect(AppleScript::lastScript())
        ->toContain('tell application "Firefox.app" to quit')
        ->not->toContain('Firefox.app.app');
});

it('uses osascript command for execution', function () {
    System::applicationsFolder();

    expect(AppleScript::lastCommand())
        ->toBe(['osascript', '-']);
});

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
