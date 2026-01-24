<?php

use Pulli\Pullbox\AppleScript;
use Pulli\Pullbox\Enums\SystemSound;
use Pulli\Pullbox\Music;

beforeEach(function () {
    AppleScript::fake();
});

afterEach(function () {
    AppleScript::unfake();
});

it('generates the correct AppleScript for exportPlaylist with default format', function () {
    Music::exportPlaylist('My Playlist', '/tmp/playlist.xml');

    expect(AppleScript::lastScript())
        ->toContain('use AppleScript version "2.8"')
        ->toContain('tell application "Music" to export playlist "My Playlist" as XML to "/tmp/playlist.xml"');
});

it('generates the correct AppleScript for exportPlaylist with m3u format', function () {
    Music::exportPlaylist('My Playlist', '/tmp/playlist.m3u', 'm3u');

    expect(AppleScript::lastScript())
        ->toContain('export playlist "My Playlist" as M3U to "/tmp/playlist.m3u"');
});

it('generates the correct AppleScript for exportPlaylist with m3u8 format', function () {
    Music::exportPlaylist('My Playlist', '/tmp/playlist.m3u8', 'm3u8');

    expect(AppleScript::lastScript())
        ->toContain('export playlist "My Playlist" as M3U8 to "/tmp/playlist.m3u8"');
});

it('generates the correct AppleScript for exportPlaylist with plain_text format', function () {
    Music::exportPlaylist('My Playlist', '/tmp/playlist.txt', 'plain_text');

    expect(AppleScript::lastScript())
        ->toContain('as plain text to');
});

it('generates the correct AppleScript for exportPlaylist with unicode_text format', function () {
    Music::exportPlaylist('My Playlist', '/tmp/playlist.txt', 'unicode_text');

    expect(AppleScript::lastScript())
        ->toContain('as Unicode text to');
});

it('uses osascript for exportPlaylist', function () {
    Music::exportPlaylist('Test', '/tmp/test.xml');

    expect(AppleScript::lastCommand())
        ->toBe(['osascript', '-']);
});

it('uses afplay command for playSystemSound', function () {
    Music::playSystemSound(SystemSound::Glass);

    expect(AppleScript::lastCommand())
        ->toBe(['afplay', SystemSound::Glass->filepath()]);
});

it('escapes special characters in playlist name', function () {
    Music::exportPlaylist('My "Best" Playlist', '/tmp/playlist.xml');

    expect(AppleScript::lastScript())
        ->toContain('export playlist "My \\"Best\\" Playlist"');
});
