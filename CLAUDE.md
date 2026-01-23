# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Pullbox (`pulli/pullbox`) is a PHP 8.4+ library providing static methods for executing AppleScript on macOS, with wrappers for system operations, Music app, dialogs, notifications, and DEVONthink.

## Commands

- **Run tests:** `composer test` (runs `vendor/bin/pest`)
- **Run a single test:** `vendor/bin/pest tests/Unit/AppleScriptTest.php`
- **Run a single test by name:** `vendor/bin/pest --filter="test name"`
- **Run tests excluding macOS-only:** `vendor/bin/pest --exclude-group=mac`
- **Lint/format:** `vendor/bin/pint`

## Architecture

The library has two layers:

1. **AppleScript generation** (`src/AppleScript.php`) — Pure static methods that return AppleScript code strings. No side effects.
2. **Execution facades** (`src/System.php`, `src/Music.php`, `src/Dialog.php`, `src/Notification.php`, `src/DEVONthink.php`) — Static facades that call `AppleScript` methods and execute the generated scripts via `shell_exec('osascript ...')`.

Supporting types:
- `src/Enums/PlaylistExportFormat.php` — Playlist export format enum with `fromInput()` factory
- `src/Enums/SystemSound.php` — System sound enum with `filepath()` using Symfony Finder
- `src/Exceptions/NotRunningMacException.php` — Thrown on non-macOS systems

## Namespace

All classes: `Pulli\Pullbox\*` (PSR-4 autoloaded from `src/`)

## Testing

- Framework: Pest v4
- Tests in `tests/Unit/` use parameterized datasets to verify AppleScript string output
- Tests marked `->group('mac')` require macOS (excluded in CI)
- Base test case: `tests/TestCase.php`

## Dependencies

- `illuminate/collections` for collection operations
- `rodneyrehm/plist` for plist parsing
- Dev: `laravel/pint` (formatting), `pestphp/pest` (testing)
