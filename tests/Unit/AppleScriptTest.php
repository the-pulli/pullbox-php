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

it('can generate the AppleScript to display a dialog with buttons only', function () {
    $result = AppleScript::displayDialog('Test', 'Title');

    expect($result)
        ->toContain('display dialog "Test" buttons {"OK"} with title "Title"')
        ->toContain('return button returned of theReturnedValue');
});

it('can generate the AppleScript to display a dialog with default and cancel buttons', function () {
    $result = AppleScript::displayDialog('Test', 'Title', [
        'buttons' => ['Save', 'Cancel'],
        'defaultButton' => 'Save',
        'cancelButton' => 'Cancel',
    ]);

    expect($result)
        ->toContain('buttons {"Save", "Cancel"} default button "Save" cancel button "Cancel" with title "Title"')
        ->toContain('return button returned of theReturnedValue');
});

it('can generate the AppleScript to display a dialog with three buttons', function () {
    $result = AppleScript::displayDialog('Test', null, [
        'buttons' => ['Save', 'Discard', 'Cancel'],
        'defaultButton' => 1,
        'cancelButton' => 3,
    ]);

    expect($result)
        ->toContain('buttons {"Save", "Discard", "Cancel"} default button 1 cancel button 3')
        ->not->toContain('with title');
});

it('can generate the AppleScript to display a dialog with text input', function () {
    $result = AppleScript::displayDialog('Enter name:', 'Input', [
        'answer' => true,
    ]);

    expect($result)
        ->toContain('default answer ""')
        ->toContain('return text returned of theReturnedValue');
});

it('can generate the AppleScript to display a dialog with pre-filled text input', function () {
    $result = AppleScript::displayDialog('Name:', 'Input', [
        'answer' => 'default value',
    ]);

    expect($result)
        ->toContain('default answer "default value"')
        ->toContain('return text returned of theReturnedValue');
});

it('can generate the AppleScript to display a dialog with hidden answer', function () {
    $result = AppleScript::displayDialog('Password:', 'Auth', [
        'answer' => true,
        'hiddenAnswer' => true,
    ]);

    expect($result)
        ->toContain('default answer "" with hidden answer')
        ->toContain('return text returned of theReturnedValue');
});

it('can generate the AppleScript to display a dialog with icon', function () {
    $result = AppleScript::displayDialog('Warning!', 'Alert', [
        'icon' => 'caution',
    ]);

    expect($result)
        ->toContain('with icon caution');
});

it('can generate the AppleScript to display a dialog with giving up after', function () {
    $result = AppleScript::displayDialog('Auto-dismiss', 'Timeout', [
        'givingUpAfter' => 10,
    ]);

    expect($result)
        ->toContain('giving up after 10');
});

it('can generate the AppleScript to display a dialog without a title', function () {
    $result = AppleScript::displayDialog('No title');

    expect($result)
        ->toContain('display dialog "No title" buttons {"OK"}')
        ->not->toContain('with title');
});

it('can generate the AppleScript to display a notification', function (string $message, ?string $title, string $expectedContains) {
    $result = AppleScript::displayNotification($message, $title);

    expect($result)
        ->toContain('use AppleScript version "2.8"')
        ->toContain($expectedContains);
})->with([
    'message and title' => ['Test', 'Test', 'display notification "Test" with title "Test"'],
    'only message' => ['Test', null, 'display notification "Test"'],
    'message with empty title' => ['Test', '', 'display notification "Test"'],
]);

it('can generate the AppleScript to display a notification with subtitle', function () {
    $result = AppleScript::displayNotification('Done', 'App', 'Processing complete');

    expect($result)
        ->toContain('display notification "Done" with title "App" subtitle "Processing complete"');
});

it('can generate the AppleScript to display a notification with sound', function () {
    $result = AppleScript::displayNotification('Done', 'App', null, 'Glass');

    expect($result)
        ->toContain('display notification "Done" with title "App" sound name "Glass"');
});

it('can generate the AppleScript to display a notification with all parameters', function () {
    $result = AppleScript::displayNotification('Done', 'App', 'Subtitle', 'Frog');

    expect($result)
        ->toContain('display notification "Done" with title "App" subtitle "Subtitle" sound name "Frog"');
});

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

it('can generate the AppleScript to get the applications folder', function () {
    $result = AppleScript::applicationsFolder();

    expect($result)
        ->toContain('use AppleScript version "2.8"')
        ->toContain('use scripting additions')
        ->toContain('set theApplicationsFolder to path to applications folder')
        ->toContain('return (POSIX path) of theApplicationsFolder');
});

it('can generate the AppleScript to move an app', function (string $name, string $path, bool $launch, string $launchString) {
    $result = AppleScript::moveApp($name, $path, $launch);

    expect($result)
        ->toContain('use AppleScript version "2.8"')
        ->toContain('use scripting additions')
        ->toContain('set theApplicationsFolder to path to applications folder')
        ->toContain("tell application \"$name\" to quit")
        ->toContain("move (POSIX file \"$path\") as alias to theApplicationsFolder with replacing")
        ->toContain("if $launchString then")
        ->toContain("tell application \"$name\" to activate");
})->with([
    'with launch' => ['MyApp', '/tmp/MyApp.app', true, 'true'],
    'without launch' => ['MyApp', '/tmp/MyApp.app', false, 'false'],
]);

it('escapes special characters in AppleScript strings', function () {
    expect(AppleScript::escapeString('Hello "World"'))
        ->toBe('Hello \\"World\\"');

    expect(AppleScript::escapeString('path\\to\\file'))
        ->toBe('path\\\\to\\\\file');

    expect(AppleScript::escapeString('no special chars'))
        ->toBe('no special chars');
});
