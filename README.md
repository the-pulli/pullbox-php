# Pullbox

A PHP 8.4+ library providing static methods for executing AppleScript on macOS, with wrappers for system operations, Music app, dialogs, notifications, and DEVONthink.

[![release](https://img.shields.io/github/release/the-pulli/pullbox-php.svg?style=flat-square)](https://github.com/the-pulli/pullbox-php/releases)
[![packagist](https://img.shields.io/packagist/v/pulli/pullbox.svg?style=flat-square)](https://packagist.org/packages/pulli/pullbox)
[![license](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](https://github.com/the-pulli/pullbox-php/blob/main/LICENSE.md)
[![downloads](https://img.shields.io/packagist/dt/pulli/pullbox.svg?style=flat-square)](https://packagist.org/packages/pulli/pullbox)
![tests](https://github.com/the-pulli/pullbox-php/actions/workflows/run-tests.yml/badge.svg)

> **Note:** This library requires macOS to execute AppleScript commands. The `AppleScript` class can generate scripts on any platform, but the facade classes (`System`, `Music`, `Dialog`, `Notification`, `DEVONthink`) require macOS.

## Installation

```bash
composer require pulli/pullbox
```

## Usage

### Dialog

Display macOS dialogs with customizable buttons and optional text input.

```php
use Pulli\Pullbox\Dialog;

// Simple dialog with OK button
$result = Dialog::display('Are you sure?', 'Confirmation');

// Dialog with custom buttons, default and cancel
$result = Dialog::display('Save changes?', 'Save', [
    'buttons' => ['Save', 'Discard', 'Cancel'],
    'defaultButton' => 'Save',
    'cancelButton' => 'Cancel',
]);

// Buttons referenced by position (1-indexed)
$result = Dialog::display('Continue?', 'Confirm', [
    'buttons' => ['Yes', 'No'],
    'defaultButton' => 1,
    'cancelButton' => 2,
]);

// Dialog with text input
$result = Dialog::display('Enter your name:', 'Input', [
    'answer' => true,
]);

// Pre-filled text input
$result = Dialog::display('Rename:', 'Edit', [
    'answer' => 'current name',
]);

// Password input (hidden)
$result = Dialog::display('Password:', 'Auth', [
    'answer' => true,
    'hiddenAnswer' => true,
]);

// With icon (stop, caution, or note)
$result = Dialog::display('Are you sure?', 'Warning', [
    'icon' => 'caution',
]);

// Auto-dismiss after N seconds
$result = Dialog::display('Closing soon...', 'Notice', [
    'givingUpAfter' => 10,
]);
```

**Methods:**

| Method | Parameters | Returns |
|--------|-----------|---------|
| `display` | `string $message`, `?string $title`, `array $options = []` | `?string` (button pressed or text input, null if cancelled) |

**Options array:**

- `buttons` — Array of button labels, max 3 (default: `['OK']`)
- `defaultButton` — Button name or 1-indexed position for the default button
- `cancelButton` — Button name or 1-indexed position for the cancel button
- `answer` — `true` for empty text field, or a string to pre-fill (default: `false`)
- `hiddenAnswer` — `true` to mask input like a password field
- `icon` — Dialog icon: `'stop'`, `'caution'`, or `'note'`
- `givingUpAfter` — Auto-dismiss the dialog after N seconds

### Notification

Display macOS notifications with optional title, subtitle, and sound.

```php
use Pulli\Pullbox\Notification;

// Simple notification
Notification::display('Download complete');

// With title
Notification::display('Download complete', 'My App');

// With subtitle
Notification::display('Download complete', 'My App', 'All files processed');

// With sound
Notification::display('Download complete', 'My App', null, 'Glass');

// All parameters
Notification::display('Download complete', 'My App', 'All files processed', 'Frog');
```

**Methods:**

| Method | Parameters | Returns |
|--------|-----------|---------|
| `display` | `string $message`, `?string $title = null`, `?string $subtitle = null`, `?string $soundName = null` | `void` |

### System

System-level operations for managing applications.

```php
use Pulli\Pullbox\System;

// Get the Applications folder path
$path = System::applicationsFolder(); // e.g. "/Applications/"

// Move an app to Applications (quits it first, then relaunches)
System::moveApp('MyApp', '/tmp/MyApp.app');

// Move without relaunching
System::moveApp('MyApp', '/tmp/MyApp.app', launch: false);

// Get app version number
$version = System::versionNumber('Safari');
$version = System::versionNumber('Safari', 'CFBundleShortVersionString');

// Suppress error dialogs
$version = System::versionNumber('Safari', displayExceptions: false);
```

**Methods:**

| Method | Parameters | Returns |
|--------|-----------|---------|
| `applicationsFolder` | — | `string` |
| `moveApp` | `string $name`, `string $path`, `bool $launch = true` | `void` |
| `versionNumber` | `string $appName`, `string $key = 'CFBundleVersion'`, `bool $displayExceptions = true` | `?string` |

### Music

Interact with the macOS Music app and play system sounds.

```php
use Pulli\Pullbox\Music;
use Pulli\Pullbox\Enums\SystemSound;

// Export a playlist
Music::exportPlaylist('My Playlist', '/path/to/export.xml');
Music::exportPlaylist('My Playlist', '/path/to/export.m3u', 'm3u');

// Play a system sound
Music::playSystemSound(SystemSound::Glass);
Music::playSystemSound(SystemSound::Ping);
```

**Methods:**

| Method | Parameters | Returns |
|--------|-----------|---------|
| `exportPlaylist` | `string $name`, `string $to`, `string $format = 'xml'` | `void` |
| `playSystemSound` | `SystemSound $sound` | `void` |

### DEVONthink

Interact with [DEVONthink](https://www.devontechnologies.com/apps/devonthink) via AppleScript.

```php
use Pulli\Pullbox\DEVONthink;

// Get the file path of a record by UUID
$path = DEVONthink::pathToRecord('your-uuid-here');

// Import files
DEVONthink::importRecords('/path/to/file.pdf');
DEVONthink::importRecords(['/path/to/file1.pdf', '/path/to/file2.pdf']);

// Save text to a record
DEVONthink::savePlainTextToRecord('Hello world', 'record-uuid');

// Get record contents as a string
$contents = DEVONthink::getRecordContents('record-uuid');

// Open a record in the default app
DEVONthink::open('record-uuid');
```

**Methods:**

| Method | Parameters | Returns |
|--------|-----------|---------|
| `pathToRecord` | `string $uuid` | `string` |
| `importRecords` | `array\|string $paths` | `void` |
| `savePlainTextToRecord` | `string $text`, `string $uuid` | `void` |
| `getRecordContents` | `string $uuid` | `string` |
| `open` | `string $uuid` | `void` |

### AppleScript

Low-level class for generating and executing AppleScript code. Use this to generate scripts without executing them, or to execute arbitrary scripts safely.

```php
use Pulli\Pullbox\AppleScript;

// Execute a script (fire and forget)
AppleScript::execute($script);

// Execute and capture output
$result = AppleScript::executeAndCapture($script);

// Escape user input for safe AppleScript string interpolation
$safe = AppleScript::escapeString('He said "hello"');

// Generate script strings
$script = AppleScript::displayDialog('Hello', 'Title');
$script = AppleScript::displayNotification('Done', 'App', 'Subtitle', 'Glass');
$script = AppleScript::applicationsFolder();
$script = AppleScript::moveApp('MyApp', '/path/to/app');
$script = AppleScript::musicExportPlaylist('Playlist', '/path/to/file', 'xml');
$script = AppleScript::devonthinkPathToRecord('uuid');
$scripts = AppleScript::devonthinkImportRecords(['/path/to/file']);
$script = AppleScript::devonthinkSavePlainTextToRecord('text', 'uuid');
```

### Enums

#### PlaylistExportFormat

```php
use Pulli\Pullbox\Enums\PlaylistExportFormat;

// Available formats
PlaylistExportFormat::M3U;         // 'M3U'
PlaylistExportFormat::M3U8;        // 'M3U8'
PlaylistExportFormat::PlainText;   // 'plain text'
PlaylistExportFormat::UnicodeText; // 'Unicode text'
PlaylistExportFormat::XML;         // 'XML'

// Resolve from string
$format = PlaylistExportFormat::fromInput('m3u');  // PlaylistExportFormat::M3U
```

#### SystemSound

```php
use Pulli\Pullbox\Enums\SystemSound;

// Available sounds
SystemSound::Basso;
SystemSound::Blow;
SystemSound::Bottle;
SystemSound::Frog;
SystemSound::Funk;
SystemSound::Glass;
SystemSound::Hero;
SystemSound::Morse;
SystemSound::Ping;
SystemSound::Pop;
SystemSound::Purr;
SystemSound::Sosumi;
SystemSound::Submarine;
SystemSound::Tink;

// Get the file path of a sound
$path = SystemSound::Glass->filepath(); // /System/Library/Sounds/Glass.aiff
```

## Testing

```bash
# Run all tests (excluding macOS-only)
composer test -- --exclude-group=mac

# Run all tests (on macOS)
composer test

# Run a specific test file
vendor/bin/pest tests/Unit/AppleScriptTest.php
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
