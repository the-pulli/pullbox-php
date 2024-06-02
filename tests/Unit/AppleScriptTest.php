<?php

use Pulli\Pullbox\AppleScript;

it('can generate the AppleScript intro', function () {
    $expected = <<<'APPLESCRIPT'
    use AppleScript version "2.8" -- Latest AppleScript Version
    use scripting additions
    
    APPLESCRIPT;

    expect(AppleScript::intro())
        ->toBe($expected);
});

it('can generate the AppleScript to display a dialog', function (string $message, ?string $title, array $options, string $expectedAppleScript) {
    $expected = <<<APPLESCRIPT
    use AppleScript version "2.8" -- Latest AppleScript Version
    use scripting additions
    
    try
        set theReturnedValue to (display dialog "$message" $expectedAppleScript with title "$title")
        if button returned of theReturnedValue is "OK" then
            if text returned of theReturnedValue is not "" then
                return text returned of theReturnedValue
            else
                return
            end if
        end if
    on error errorMessage number errorNumber
        if errorNumber is equal to -128 -- aborted by user
            return
        end if
    end try
    APPLESCRIPT;

    expect(AppleScript::displayDialog($message, $title, $options))
        ->toBe($expected);
})->with([
    'message and title' => ['Test', 'Test', [], 'buttons {"OK"} default button "OK" cancel button "OK"'],
    'with two custom buttons' => ['Test', null, ['buttons' => ['OK', 'Cancel']], 'buttons {"OK", "Cancel"} default button "OK" cancel button "Cancel"'],
    'with more than two custom buttons' => ['Test', null, ['buttons' => ['OK', 'Middle', 'Cancel']], 'buttons {"OK", "Middle", "Cancel"} default button "OK" cancel button "Cancel"'],
    'message with empty title' => ['Test', null, [], 'buttons {"OK"} default button "OK" cancel button "OK"'],
]);

it('can generate the AppleScript to display a notification', function (string $message, ?string $title, string $expectedAppleScript) {
    $expected = <<<APPLESCRIPT
    use AppleScript version "2.8" -- Latest AppleScript Version
    use scripting additions
    
    $expectedAppleScript
    APPLESCRIPT;

    expect(AppleScript::displayNotification($message, $title))
        ->toBe($expected);
})->with([
    'message and title' => ['Test', 'Test', 'display notification "Test" with title "Test"'],
    'only message' => ['Test', null, 'display notification "Test"'],
    'message with empty title' => ['Test', '', 'display notification "Test"'],
]);

it('can generate the AppleScript to export a Music playlist as with different file formats', function (string $format, string $expected) {
    $expected = <<<APPLESCRIPT
    use AppleScript version "2.8" -- Latest AppleScript Version
    use scripting additions
    
    try
        tell application "Music" to export playlist "Test" as $expected to "pathToPlaylist"
    on error errMsg
        display dialog errMsg with title "Error"
    end try
    APPLESCRIPT;

    expect(AppleScript::musicExportPlaylist('Test', 'pathToPlaylist', $format))
        ->toBe($expected);
})->with([
    'm3u' => ['m3u', 'M3U'],
    'm3u8' => ['m3u8', 'M3U8'],
    'plain_text' => ['plain_text', 'plain text'],
    'unicode_text' => ['unicode_text', 'Unicode text'],
    'xml' => ['xml', 'XML'],
    'unknown_returns_xml' => ['unknown', 'XML'],
]);

it('can generate the AppleScript to return the path to a DEVONthink file', function () {
    $expected = <<<'APPLESCRIPT'
    use AppleScript version "2.8" -- Latest AppleScript Version
    use scripting additions
    
    tell application id "DNtp"
        set theRecord to get record with uuid "uuid"
        if (type of theRecord as string) is not in {"group", "smart group", "tag"} then return (path of theRecord as string)
    end tell
    APPLESCRIPT;

    expect(AppleScript::devonthinkPathToRecord('uuid'))
        ->toBe($expected);
});

it('can generate the AppleScripts to import multiple files to DEVONthink', function (array $files) {
    $expected = [];

    foreach ($files as $file) {
        $expected[] = <<<APPLESCRIPT
        use AppleScript version "2.8" -- Latest AppleScript Version
        use scripting additions
        
        try
            tell application id "DNtp"
                set theRecord to import "$file"
                if (theRecord is missing value) then
                    display dialog "File: $file could not be imported." with title "Error"
                end if
            end tell
        on error errMsg
            display dialog errMsg with title "Error"
        end try
        APPLESCRIPT;
    }

    expect(AppleScript::devonthinkImportRecords($files))
        ->toBe($expected);
})->with([
    'one file' => [['pathToFile']],
    'two files' => [['pathToFile1', 'pathToFile2']],
    'three files' => [['pathToFile1', 'pathToFile2', 'pathToFile3']],
]);

it('can generate the AppleScript to save plain text to a DEVONthink record', function () {
    $expected = <<<'APPLESCRIPT'
    use AppleScript version "2.8" -- Latest AppleScript Version
    use scripting additions
    
    tell application id "DNtp"
        set theRecord to get record with uuid "uuid"
        set plain text of theRecord to "Test"
    end tell
    APPLESCRIPT;

    expect(AppleScript::devonthinkSavePlainTextToRecord('Test', 'uuid'))
        ->toBe($expected);
});
