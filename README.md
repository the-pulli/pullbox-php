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

Interact with [DEVONthink](https://www.devontechnologies.com/apps/devonthink) via AppleScript. All 119 scripting commands are supported. Most methods are compatible with DEVONthink 3 and 4; AI/Chat methods require DEVONthink 4.

Records are referenced by UUID throughout. Methods that create or find records return their UUID.

```php
use Pulli\Pullbox\DEVONthink;
use Pulli\Pullbox\Enums\RecordType;
use Pulli\Pullbox\Enums\UpdateMode;
use Pulli\Pullbox\Enums\SummaryType;

// Create a record
$uuid = DEVONthink::createRecordWith([
    'name' => 'My Note',
    'type' => RecordType::Markdown,
    'content' => '# Hello World',
]);

// Create in a specific group
$uuid = DEVONthink::createRecordWith(
    ['name' => 'Note', 'type' => RecordType::Text, 'content' => 'Hello'],
    $groupUuid
);

// Search
$uuids = DEVONthink::search('kind:markdown name:report');

// Import, index, export
$uuid = DEVONthink::importPath('/path/to/file.pdf', $groupUuid);
$uuid = DEVONthink::indexPath('/path/to/folder', $groupUuid);
DEVONthink::export($uuid, '/path/to/export');

// Web capture
$uuid = DEVONthink::createMarkdownFrom('https://example.com');
$uuid = DEVONthink::createPdfDocumentFrom('https://example.com');
$uuid = DEVONthink::downloadUrl('https://example.com/file.zip');

// Record operations
DEVONthink::move($uuid, $targetGroupUuid);
DEVONthink::duplicate($uuid);
DEVONthink::replicate($uuid, $targetGroupUuid);
DEVONthink::delete($uuid);

// Content access
$text = DEVONthink::getTextOf($uuid);
$path = DEVONthink::pathToRecord($uuid);
$contents = DEVONthink::getRecordContents($uuid);
DEVONthink::savePlainTextToRecord('Updated text', $uuid);
DEVONthink::update($uuid, 'Appended text', UpdateMode::Appending);

// Custom metadata
DEVONthink::addCustomMetaData('value', 'myKey', $uuid);
$value = DEVONthink::getCustomMetaData('myKey', $uuid);

// Database operations
$dbName = DEVONthink::createDatabase('/path/to/new.dtBase2');
$dbName = DEVONthink::openDatabase('/path/to/existing.dtBase2');
DEVONthink::optimize('My Database');
$errors = DEVONthink::verify('My Database');

// AI & Chat (DEVONthink 4 only)
$response = DEVONthink::getChatResponseForMessage('Summarize this', engine: 'openai');
$transcript = DEVONthink::transcribe($audioUuid, language: 'en');
$summaryUuid = DEVONthink::summarizeAnnotationsOf($uuids, SummaryType::Markdown);
```

#### Record CRUD

| Method | Parameters | Returns |
|--------|-----------|---------|
| `createRecordWith` | `array $properties`, `?string $groupUuid` | `?string` |
| `delete` | `string $uuid` | `bool` |
| `duplicate` | `string $uuid`, `?string $toGroupUuid` | `?string` |
| `move` | `string $uuid`, `string $toGroupUuid` | `?string` |
| `moveIntoDatabase` | `string $uuid`, `string $databaseName` | `?string` |
| `moveToExternalFolder` | `string $uuid` | `bool` |
| `replicate` | `string $uuid`, `string $toGroupUuid` | `?string` |
| `merge` | `array $uuids`, `?string $groupUuid` | `?string` |
| `update` | `string $uuid`, `string $text`, `UpdateMode $mode`, `?string $url` | `bool` |

#### Record Access

| Method | Parameters | Returns |
|--------|-----------|---------|
| `pathToRecord` | `string $uuid` | `string` |
| `getRecordContents` | `string $uuid` | `string` |
| `getRecordAt` | `string $path`, `?string $databaseName` | `?string` |
| `getRecordWithId` | `int $id`, `?string $databaseName` | `?string` |
| `getRecordWithUuid` | `string $uuid` | `?string` |
| `getDatabaseWithId` | `int $id` | `?string` |
| `getDatabaseWithUuid` | `string $uuid` | `?string` |

#### Record Content

| Method | Parameters | Returns |
|--------|-----------|---------|
| `getTextOf` | `string $uuid` | `string` |
| `getRichTextOf` | `string $uuid` | `string` |
| `getTitleOf` | `string $uuid` | `string` |
| `getMetadataOf` | `string $uuid` | `string` |
| `getConcordanceOf` | `string $uuid` | `string` |
| `getLinksOf` | `string $uuid` | `array` |
| `getEmbeddedImagesOf` | `string $uuid` | `array` |
| `getEmbeddedObjectsOf` | `string $uuid` | `array` |
| `getEmbeddedSheetsAndScriptsOf` | `string $uuid` | `array` |
| `getFramesOf` | `string $uuid` | `array` |
| `getFaviconOf` | `string $uuid` | `string` |

#### Custom Meta Data

| Method | Parameters | Returns |
|--------|-----------|---------|
| `addCustomMetaData` | `mixed $value`, `string $forKey`, `string $uuid` | `void` |
| `getCustomMetaData` | `string $forKey`, `string $uuid` | `string` |

#### Search & Lookup

| Method | Parameters | Returns |
|--------|-----------|---------|
| `search` | `string $query`, `?string $inGroupUuid`, `?string $comparison`, `bool $excludeSubgroups` | `array` |
| `lookupRecordsWithComment` | `string $comment`, `?string $databaseName` | `array` |
| `lookupRecordsWithContentHash` | `string $hash`, `?string $databaseName` | `array` |
| `lookupRecordsWithFile` | `string $path`, `?string $databaseName` | `array` |
| `lookupRecordsWithPath` | `string $path`, `?string $databaseName` | `array` |
| `lookupRecordsWithTags` | `array $tags`, `?string $databaseName` | `array` |
| `lookupRecordsWithUrl` | `string $url`, `?string $databaseName` | `array` |
| `exists` | `string $uuid` | `bool` |
| `existsRecordAt` | `string $path`, `?string $databaseName` | `bool` |
| `existsRecordWithComment` | `string $comment`, `?string $databaseName` | `bool` |
| `existsRecordWithContentHash` | `string $hash`, `?string $databaseName` | `bool` |
| `existsRecordWithFile` | `string $path`, `?string $databaseName` | `bool` |
| `existsRecordWithPath` | `string $path`, `?string $databaseName` | `bool` |
| `existsRecordWithUrl` | `string $url`, `?string $databaseName` | `bool` |
| `classify` | `string $uuid`, `?string $groupUuid` | `array` |
| `compare` | `string $uuid`, `string $toUuid` | `float` |
| `count` | `string $uuid` | `int` |

#### Import & Export

| Method | Parameters | Returns |
|--------|-----------|---------|
| `importRecords` | `array\|string $paths` | `void` |
| `importPath` | `string $path`, `?string $groupUuid` | `?string` |
| `importTemplate` | `string $path`, `?string $groupUuid` | `?string` |
| `importAttachmentsOf` | `string $uuid`, `?string $groupUuid` | `array` |
| `indexPath` | `string $path`, `?string $groupUuid` | `?string` |
| `export` | `string $uuid`, `string $toPath` | `bool` |
| `exportTagsOf` | `string $uuid` | `string` |
| `exportWebsite` | `string $uuid`, `string $toPath` | `bool` |

#### Web Capture & Download

| Method | Parameters | Returns |
|--------|-----------|---------|
| `createWebDocumentFrom` | `string $url`, `?string $groupUuid` | `?string` |
| `createMarkdownFrom` | `string $url`, `?string $groupUuid` | `?string` |
| `createPdfDocumentFrom` | `string $url`, `?string $groupUuid` | `?string` |
| `createFormattedNoteFrom` | `string $url`, `?string $groupUuid` | `?string` |
| `downloadUrl` | `string $url`, `?string $groupUuid` | `?string` |
| `downloadMarkupFrom` | `string $url` | `string` |
| `addDownload` | `string $url` | `void` |
| `addReadingList` | `string $url` | `void` |
| `getCachedDataForUrl` | `string $url` | `string` |
| `startDownloads` | — | `bool` |
| `stopDownloads` | — | `bool` |

#### Conversion

| Method | Parameters | Returns |
|--------|-----------|---------|
| `convert` | `string $uuid`, `string $toType`, `?string $groupUuid` | `?string` |
| `convertFeedToHtml` | `string $uuid` | `string` |
| `convertImage` | `string $uuid`, `?string $groupUuid` | `?string` |

#### Text & OCR

| Method | Parameters | Returns |
|--------|-----------|---------|
| `savePlainTextToRecord` | `string $text`, `string $uuid` | `void` |
| `extractKeywordsFrom` | `string $text` | `string` |
| `ocr` | `string $uuid`, `?string $groupUuid` | `?string` |

#### Database Operations

| Method | Parameters | Returns |
|--------|-----------|---------|
| `createDatabase` | `string $path` | `?string` |
| `openDatabase` | `string $path` | `?string` |
| `optimize` | `string $databaseName` | `bool` |
| `verify` | `string $databaseName` | `int` |
| `checkFileIntegrityOf` | `string $uuid` | `int` |
| `compress` | `string $databaseName` | `bool` |
| `synchronize` | `?string $uuid`, `?string $databaseName` | `bool` |
| `createLocation` | `string $path`, `?string $databaseName` | `?string` |

#### Thumbnails

| Method | Parameters | Returns |
|--------|-----------|---------|
| `createThumbnail` | `string $uuid` | `bool` |
| `deleteThumbnail` | `string $uuid` | `bool` |
| `updateThumbnail` | `string $uuid` | `bool` |

#### Versions

| Method | Parameters | Returns |
|--------|-----------|---------|
| `getVersionsOf` | `string $uuid` | `array` |
| `saveVersionOf` | `string $uuid` | `?string` |
| `restoreRecordWith` | `string $versionUuid` | `bool` |

#### AI & Chat (DEVONthink 4 only)

| Method | Parameters | Returns |
|--------|-----------|---------|
| `getChatCapabilitiesForEngine` | `string $engine` | `string` |
| `getChatModelsForEngine` | `string $engine` | `string` |
| `getChatResponseForMessage` | `string $message`, `?string $engine`, `?string $model` | `string` |
| `downloadImageForPrompt` | `string $prompt`, `?string $engine` | `string` |
| `summarizeText` | `string $text`, `?string $style` | `string` |
| `summarizeAnnotationsOf` | `array $uuids`, `SummaryType $type`, `?string $groupUuid` | `?string` |
| `summarizeContentsOf` | `array $uuids`, `SummaryType $type`, `?string $style`, `?string $groupUuid` | `?string` |
| `summarizeMentionsOf` | `array $uuids`, `SummaryType $type`, `?string $groupUuid` | `?string` |
| `transcribe` | `string $uuid`, `?string $language`, `?bool $timestamps` | `string` |

#### Feeds

| Method | Parameters | Returns |
|--------|-----------|---------|
| `getFeedItemsOf` | `string $uuid` | `array` |
| `getItemsOfFeed` | `string $uuid` | `array` |
| `refresh` | `string $uuid` | `bool` |

#### Sheets

| Method | Parameters | Returns |
|--------|-----------|---------|
| `addRow` | `string $uuid` | `void` |
| `deleteRowAt` | `int $row`, `string $uuid` | `void` |
| `getCellAt` | `int $column`, `int $row`, `string $uuid` | `string` |
| `setCellAt` | `int $column`, `int $row`, `string $value`, `string $uuid` | `bool` |

#### UI Dialogs

| Method | Parameters | Returns |
|--------|-----------|---------|
| `displayAuthenticationDialog` | `string $message`, `?string $title` | `array` |
| `displayChatDialog` | `?string $message`, `?string $title` | `string` |
| `displayDateEditor` | `?string $date`, `?string $title` | `string` |
| `displayGroupSelector` | `string $message`, `?string $title`, `?string $databaseName` | `?string` |
| `displayNameEditor` | `string $message`, `?string $title`, `?string $defaultName` | `string` |

#### UI Windows & Tabs

| Method | Parameters | Returns |
|--------|-----------|---------|
| `open` | `string $uuid` | `void` |
| `openTabFor` | `?string $uuid`, `?string $url`, `?string $referrer` | `void` |
| `openWindowFor` | `string $uuid`, `bool $force` | `void` |
| `showSearch` | `?string $query` | `void` |

#### Progress Indicator

| Method | Parameters | Returns |
|--------|-----------|---------|
| `showProgressIndicator` | `string $title`, `?bool $cancelButton`, `?int $steps` | `bool` |
| `stepProgressIndicator` | `?string $info` | `bool` |
| `hideProgressIndicator` | — | `bool` |

#### Workspaces

| Method | Parameters | Returns |
|--------|-----------|---------|
| `saveWorkspace` | `string $name` | `bool` |
| `loadWorkspace` | `string $name` | `void` |
| `deleteWorkspace` | `string $name` | `void` |

#### Smart Rules

| Method | Parameters | Returns |
|--------|-----------|---------|
| `performSmartRule` | `?string $name`, `?string $uuid`, `?string $trigger` | `bool` |

#### Imprinting

| Method | Parameters | Returns |
|--------|-----------|---------|
| `imprint` | `string $uuid`, `?string $configuration` | `void` |
| `imprintConfiguration` | `string $name` | `string` |
| `imprinterConfigurationNames` | — | `array` |

#### Misc

| Method | Parameters | Returns |
|--------|-----------|---------|
| `addReminder` | `string $uuid`, `?string $alarm` | `void` |
| `logMessage` | `string $message`, `?string $info` | `void` |
| `pasteClipboard` | `?string $groupUuid` | `?string` |
| `make` | `string $type`, `array $properties`, `?string $groupUuid` | `?string` |

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

#### RecordType

```php
use Pulli\Pullbox\Enums\RecordType;

RecordType::Bookmark;       // 'bookmark'
RecordType::Feed;           // 'feed'
RecordType::FormattedNote;  // 'formatted note'
RecordType::Group;          // 'group'
RecordType::HTML;           // 'html'
RecordType::Markdown;       // 'markdown'
RecordType::Picture;        // 'picture'
RecordType::Plist;          // 'plist'
RecordType::Quicktime;      // 'quicktime'
RecordType::RTF;            // 'rtf'
RecordType::RTFD;           // 'rtfd'
RecordType::Script;         // 'script'
RecordType::Sheet;          // 'sheet'
RecordType::SmartGroup;     // 'smart group'
RecordType::Text;           // 'txt'
RecordType::WebArchive;     // 'web archive'
RecordType::Unknown;        // 'unknown'
```

#### UpdateMode

```php
use Pulli\Pullbox\Enums\UpdateMode;

UpdateMode::Appending;   // 'appending'
UpdateMode::Inserting;   // 'inserting'
UpdateMode::Replacing;   // 'replacing'
```

#### SummaryType

```php
use Pulli\Pullbox\Enums\SummaryType;

SummaryType::Markdown;  // 'markdown'
SummaryType::Rich;      // 'rich'
SummaryType::Sheet;     // 'sheet'
SummaryType::Simple;    // 'simple'
```

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
