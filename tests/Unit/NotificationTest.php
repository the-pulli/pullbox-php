<?php

use Pulli\Pullbox\AppleScript;
use Pulli\Pullbox\Notification;

beforeEach(function () {
    AppleScript::fake();
});

afterEach(function () {
    AppleScript::unfake();
});

it('generates the correct AppleScript for a simple notification', function () {
    Notification::display('Hello');

    expect(AppleScript::lastScript())
        ->toContain('use AppleScript version "2.8"')
        ->toContain('display notification "Hello"');
});

it('generates the correct AppleScript for a notification with title', function () {
    Notification::display('Done', 'App');

    expect(AppleScript::lastScript())
        ->toContain('display notification "Done" with title "App"');
});

it('generates the correct AppleScript for a notification with subtitle', function () {
    Notification::display('Done', 'App', 'Processing complete');

    expect(AppleScript::lastScript())
        ->toContain('display notification "Done" with title "App" subtitle "Processing complete"');
});

it('generates the correct AppleScript for a notification with sound', function () {
    Notification::display('Done', 'App', null, 'Glass');

    expect(AppleScript::lastScript())
        ->toContain('display notification "Done" with title "App" sound name "Glass"');
});

it('generates the correct AppleScript for a notification with all parameters', function () {
    Notification::display('Done', 'App', 'Subtitle', 'Frog');

    expect(AppleScript::lastScript())
        ->toContain('display notification "Done" with title "App" subtitle "Subtitle" sound name "Frog"');
});

it('uses osascript for execution', function () {
    Notification::display('Test');

    expect(AppleScript::lastCommand())
        ->toBe(['osascript', '-']);
});

it('escapes special characters in notification message', function () {
    Notification::display('Say "hello"', 'Title');

    expect(AppleScript::lastScript())
        ->toContain('display notification "Say \\"hello\\""');
});
