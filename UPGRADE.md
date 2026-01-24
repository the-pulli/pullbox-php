# Upgrade Guide

## From v1.0.0 to v2.0.0

### DEVONthink Class Expanded

The `DEVONthink` class has been rewritten with all 119 AppleScript commands. Existing public methods (`pathToRecord`, `importRecords`, `savePlainTextToRecord`, `getRecordContents`) remain unchanged. All new methods are additive.

### New Testing Helpers

Both `AppleScript` and `DEVONthink` now support a `fake()` mode for unit testing without executing scripts:

```php
// Fake mode — captures scripts without executing
AppleScript::fake();
DEVONthink::fake();

// Run a method
DEVONthink::delete('uuid');

// Inspect what would have been executed
DEVONthink::lastScript();   // Full AppleScript string
AppleScript::lastScript();  // Script passed to osascript
AppleScript::lastCommand(); // Command array (e.g. ['osascript', '-'])

// Restore real execution
AppleScript::unfake();
DEVONthink::unfake();
```

### New Enums

Three new backed string enums:

- `RecordType` — DEVONthink record types (bookmark, markdown, group, etc.)
- `UpdateMode` — Modes for the `update` command (appending, inserting, replacing)
- `SummaryType` — Summary output formats (markdown, rich, sheet, simple)

### AI & Chat Methods (DEVONthink 4)

Nine methods require DEVONthink 4 and are annotated with `@since DEVONthink 4`:

- `getChatCapabilitiesForEngine()`, `getChatModelsForEngine()`, `getChatResponseForMessage()`
- `downloadImageForPrompt()`, `summarizeText()`
- `summarizeAnnotationsOf()`, `summarizeContentsOf()`, `summarizeMentionsOf()`
- `transcribe()`

## From v0.2.0 to v1.0.0

### Shell Execution Changed

All facade classes (`Dialog`, `Notification`, `Music`, `System`, `DEVONthink`) now use `proc_open` with array-based commands instead of `system()` / backtick operators with shell-interpolated strings. This eliminates shell injection risks entirely.

If you were relying on shell-level behavior (e.g., environment variable expansion in paths), this will no longer work. Pass fully resolved values instead.

### `Notification::display()` Signature Changed

```php
// Before
Notification::display(string $message, ?string $title = null): void

// After
Notification::display(string $message, ?string $title = null, ?string $subtitle = null, ?string $soundName = null): void
```

New optional parameters were added. Existing calls are fully compatible.

### `AppleScript::displayNotification()` Signature Changed

```php
// Before
AppleScript::displayNotification(string $message, ?string $title = null): string

// After
AppleScript::displayNotification(string $message, ?string $title = null, ?string $subtitle = null, ?string $soundName = null): string
```

New optional parameters were added. Existing calls are fully compatible.

### New `AppleScript::execute()` and `AppleScript::executeAndCapture()`

Two new public static methods for safe script execution:

```php
// Fire and forget
AppleScript::execute(string $script): void

// Capture output
AppleScript::executeAndCapture(string $script): string
```

These pipe scripts to `osascript` via stdin, avoiding shell quoting issues.

## From v0.1.x to v0.2.0

### `AppleScript::displayDialog()` Breaking Changes

The `displayDialog` method was fully refactored:

**Options array keys changed:**

| Before | After |
|--------|-------|
| `'answer' => true` (boolean only) | `'answer' => true` or `'answer' => 'pre-filled text'` |
| Implicit `default button` (always first) | `'defaultButton' => 'Name'` or `'defaultButton' => 1` (optional) |
| Implicit `cancel button` (always last) | `'cancelButton' => 'Name'` or `'cancelButton' => 1` (optional) |
| — | `'hiddenAnswer' => true` (new) |
| — | `'icon' => 'caution'` (new) |
| — | `'givingUpAfter' => 10` (new) |

**Return value logic changed:**

- Before: Only returned `text returned` when the default button was pressed
- After: Returns `button returned` (no text input) or `text returned` (with text input) for any non-cancel button

**Generated AppleScript structure changed:**

- `default button` and `cancel button` are no longer always included
- `with title` is omitted when title is null/empty
- Error handling uses `if errorNumber is equal to -128 then` (was `is equal to -128 -- aborted by user`)

### `AppleScript::escapeString()` Added

All user-provided strings are now escaped (backslashes and double quotes) before interpolation into AppleScript. If you were passing pre-escaped strings, you may get double-escaping.

### `System::moveApp()` Bug Fix

The `str_ends_with()` arguments were inverted — this is now fixed. If you had a workaround for this bug (e.g., always appending `.app` yourself), you can remove it.
