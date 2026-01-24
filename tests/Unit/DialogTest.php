<?php

use Pulli\Pullbox\AppleScript;
use Pulli\Pullbox\Dialog;

beforeEach(function () {
    AppleScript::fake();
});

afterEach(function () {
    AppleScript::unfake();
});

it('generates the correct AppleScript for a simple dialog', function () {
    Dialog::display('Hello', 'Title');

    expect(AppleScript::lastScript())
        ->toContain('use AppleScript version "2.8"')
        ->toContain('display dialog "Hello"')
        ->toContain('buttons {"OK"}')
        ->toContain('with title "Title"')
        ->toContain('return button returned of theReturnedValue');
});

it('generates the correct AppleScript for a dialog with custom buttons', function () {
    Dialog::display('Save changes?', 'Confirm', [
        'buttons' => ['Save', 'Discard', 'Cancel'],
        'defaultButton' => 'Save',
        'cancelButton' => 'Cancel',
    ]);

    expect(AppleScript::lastScript())
        ->toContain('buttons {"Save", "Discard", "Cancel"}')
        ->toContain('default button "Save"')
        ->toContain('cancel button "Cancel"');
});

it('generates the correct AppleScript for a dialog with text input', function () {
    Dialog::display('Enter name:', 'Input', [
        'answer' => true,
    ]);

    expect(AppleScript::lastScript())
        ->toContain('default answer ""')
        ->toContain('return text returned of theReturnedValue');
});

it('generates the correct AppleScript for a dialog with pre-filled answer', function () {
    Dialog::display('Name:', 'Edit', [
        'answer' => 'John',
    ]);

    expect(AppleScript::lastScript())
        ->toContain('default answer "John"');
});

it('generates the correct AppleScript for a dialog with hidden answer', function () {
    Dialog::display('Password:', 'Auth', [
        'answer' => true,
        'hiddenAnswer' => true,
    ]);

    expect(AppleScript::lastScript())
        ->toContain('default answer "" with hidden answer');
});

it('generates the correct AppleScript for a dialog with icon', function () {
    Dialog::display('Warning!', 'Alert', [
        'icon' => 'caution',
    ]);

    expect(AppleScript::lastScript())
        ->toContain('with icon caution');
});

it('generates the correct AppleScript for a dialog with giving up after', function () {
    Dialog::display('Auto-dismiss', 'Timeout', [
        'givingUpAfter' => 5,
    ]);

    expect(AppleScript::lastScript())
        ->toContain('giving up after 5');
});

it('generates the correct AppleScript for a dialog without title', function () {
    Dialog::display('No title', null);

    expect(AppleScript::lastScript())
        ->toContain('display dialog "No title"')
        ->not->toContain('with title');
});

it('returns null when capture returns empty', function () {
    $result = Dialog::display('Test', 'Title');

    expect($result)->toBeNull();
});

it('escapes special characters in dialog message', function () {
    Dialog::display('Say "hello"', 'Title');

    expect(AppleScript::lastScript())
        ->toContain('display dialog "Say \\"hello\\""');
});

it('uses osascript for execution', function () {
    Dialog::display('Test', 'Title');

    expect(AppleScript::lastCommand())
        ->toBe(['osascript', '-']);
});
