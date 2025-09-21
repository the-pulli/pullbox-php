<?php

namespace Pulli\Pullbox;

use CFPropertyList\CFPropertyList;
use CFPropertyList\IOException;
use Illuminate\Support\Collection;

use function sprintf;

class System
{
    public static function applicationsFolder(): string
    {
        $applescript = AppleScript::applicationsFolder();

        return trim(`osascript -e '$applescript'`);
    }

    public static function moveApp(string $name, string $path, bool $launch = true): void
    {
        $appExtension = '.app';

        if (! str_ends_with($appExtension, $name)) {
            $name .= $appExtension;
        }

        $applescript = AppleScript::moveApp($name, $path, $launch);

        system("osascript -e '$applescript'");
    }

    public static function versionNumber(string $appName, string $key = 'CFBundleVersion', bool $displayExceptions = true): ?string
    {
        try {
            $list = new CFPropertyList(sprintf('%s%s.app/Contents/Info.plist', static::applicationsFolder(), $appName));
        } catch (IOException $e) {
            if ($displayExceptions) {
                Dialog::display($e->getMessage(), static::titleError('Bundle', $appName));
            }

            return null;
        }

        $version = Collection::make($list->toArray())->get($key);

        if (is_null($version)) {
            if ($displayExceptions) {
                Dialog::display(
                    message: sprintf('%s version number could be parsed.', $appName),
                    title: static::titleError('Version number', $appName)
                );
            }

            return null;
        }

        return $version;
    }

    private static function titleError(string $type, string $appName): string
    {
        return sprintf('%s Error | %s', $type, $appName);
    }
}
