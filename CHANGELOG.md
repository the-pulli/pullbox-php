# Changelog

All notable changes to `pullbox` will be documented in this file

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
