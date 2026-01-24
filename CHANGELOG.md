# Changelog

All notable changes to `pullbox` will be documented in this file

## v1.0.0 - 2026-01-24

### v1.0.0 — Stable Release

First stable release of Pullbox. The API is now considered stable and follows semantic versioning.

#### Security: Shell Injection Fix

All facade classes (`Dialog`, `Notification`, `Music`, `System`, `DEVONthink`) now use `proc_open` with array-based commands instead of `system()` / backtick operators. Scripts are piped to `osascript` via stdin, completely eliminating shell injection risks.

**Before (vulnerable):**

```php
system("osascript -e '$applescript'");  // single-quote breakout possible

```
**After (safe):**

```php
AppleScript::execute($script);  // piped via stdin, no shell interpolation

```
#### New Features

- **`AppleScript::execute()`** — Safe fire-and-forget script execution via stdin
- **`AppleScript::executeAndCapture()`** — Safe script execution with output capture
- **`Notification::display()`** — Added `$subtitle` and `$soundName` parameters, completing the full `display notification` spec
- **`AppleScript::displayNotification()`** — Added `$subtitle` and `$soundName` parameters

#### Documentation

- Added `UPGRADE.md` with migration guides for v0.1.x → v0.2.0 and v0.2.0 → v1.0.0

#### Full Notification Example

```php
Notification::display(
    'Download complete',
    'My App',
    'All files processed',
    'Glass'
);

```
#### Breaking Changes from v0.2.0

- Facade classes no longer use shell execution — if you relied on shell-level behavior (env var expansion in paths), pass fully resolved values instead
- `Notification::display()` and `AppleScript::displayNotification()` have new optional parameters (existing calls are compatible)

See [UPGRADE.md](UPGRADE.md) for the full migration guide.

## v0.2.0 - 2026-01-24

### What's Changed

#### Breaking Changes

- **`displayDialog`** — `default button` and `cancel button` are no longer automatically forced. The return value is now `button returned` (without text input) or `text returned` (with text input) for any non-cancel button press.

#### New Features

- **`displayDialog` options** — Full AppleScript `display dialog` support: `defaultButton`, `cancelButton` (by name or position), `answer` (pre-filled text), `hiddenAnswer` (password input), `icon` (stop/caution/note), `givingUpAfter` (auto-dismiss)
- **`AppleScript::escapeString()`** — Escapes backslashes and double quotes for safe AppleScript string interpolation, applied to all user-provided strings

#### Bug Fixes

- **`System::moveApp()`** — Fixed inverted `str_ends_with()` arguments that prevented `.app` extension detection from working correctly

#### CI & Tooling

- Replaced `ci.yml` with `run-tests.yml` (uses actions/checkout@v4, shivammathur/setup-php@v2)
- Added `fix-php-code-style.yml` — auto-fix with Pint on push
- Added `dependabot-auto-merge.yml` — auto-merge minor/patch PRs
- Added `update-changelog.yml` — auto-update CHANGELOG.md on release
- Added `dependabot.yml` — weekly checks for github-actions and composer

#### Tests

- Added tests for `AppleScript::applicationsFolder()`, `AppleScript::moveApp()`, `AppleScript::escapeString()`
- Added `PlaylistExportFormatTest` covering all inputs and enum values
- Added mac-group integration tests for `System` and `Notification`
- Expanded dialog tests covering all new options (41 tests, 65 assertions)

#### Documentation

- Comprehensive README rewrite with full API reference, usage examples, and method signatures for all classes and enums

## 0.1.0 - 2024-02-22

- Initial release
