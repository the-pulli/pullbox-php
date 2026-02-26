<?php

namespace Pulli\Pullbox;

use Pulli\Pullbox\Enums\RecordType;
use Pulli\Pullbox\Enums\SummaryType;
use Pulli\Pullbox\Enums\UpdateMode;

class DEVONthink
{
    // =========================================================================
    // Testing
    // =========================================================================

    private static bool $testing = false;

    private static ?string $lastScript = null;

    public static function fake(): void
    {
        static::$testing = true;
        static::$lastScript = null;
    }

    public static function unfake(): void
    {
        static::$testing = false;
        static::$lastScript = null;
    }

    public static function lastScript(): ?string
    {
        return static::$lastScript;
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private static function escape(string $value): string
    {
        return AppleScript::escapeString($value);
    }

    private static function script(string $body): string
    {
        $intro = AppleScript::intro();

        return <<<APPLESCRIPT
        $intro
        tell application id "DNtp"
            $body
        end tell
        APPLESCRIPT;
    }

    private static function execute(string $body): void
    {
        static::$lastScript = static::script($body);

        if (! static::$testing) {
            AppleScript::execute(static::$lastScript);
        }
    }

    private static function capture(string $body): string
    {
        static::$lastScript = static::script($body);

        if (static::$testing) {
            return '';
        }

        return AppleScript::executeAndCapture(static::$lastScript);
    }

    private static function parseBool(string $output): bool
    {
        return $output === 'true';
    }

    private static function parseList(string $output): array
    {
        return $output !== '' ? explode("\n", $output) : [];
    }

    private static function captureList(string $body, string $listVar = 'theRecords', string $expression = 'uuid of r as text'): array
    {
        $result = static::capture(<<<APPLESCRIPT
            $body
            set theResult to ""
            repeat with r in $listVar
                if theResult is not "" then set theResult to theResult & linefeed
                set theResult to theResult & ($expression)
            end repeat
            return theResult
        APPLESCRIPT);

        return static::parseList($result);
    }

    private static function recordRef(string $uuid): string
    {
        return sprintf('get record with uuid "%s"', static::escape($uuid));
    }

    private static function groupRef(?string $uuid): string
    {
        if ($uuid === null) {
            return 'incoming group';
        }

        return sprintf('(%s)', static::recordRef($uuid));
    }

    // =========================================================================
    // Record CRUD
    // =========================================================================

    public static function createRecordWith(array $properties, ?string $groupUuid = null): ?string
    {
        $parts = [];
        foreach ($properties as $key => $value) {
            $key = static::escape($key);
            if ($value instanceof RecordType) {
                $parts[] = sprintf('%s:%s', $key, $value->value);
            } elseif (is_bool($value)) {
                $parts[] = sprintf('%s:%s', $key, $value ? 'true' : 'false');
            } elseif (is_int($value) || is_float($value)) {
                $parts[] = sprintf('%s:%s', $key, $value);
            } else {
                $parts[] = sprintf('%s:"%s"', $key, static::escape((string) $value));
            }
        }
        $props = '{'.implode(', ', $parts).'}';
        $group = static::groupRef($groupUuid);

        return static::capture(<<<APPLESCRIPT
            set theRecord to create record with $props in $group
            if theRecord is not missing value then return uuid of theRecord as text
        APPLESCRIPT) ?: null;
    }

    public static function delete(string $uuid): bool
    {
        $ref = static::recordRef($uuid);

        return static::parseBool(static::capture(<<<APPLESCRIPT
            set theRecord to $ref
            return (delete record theRecord) as text
        APPLESCRIPT));
    }

    public static function duplicate(string $uuid, ?string $toGroupUuid = null): ?string
    {
        $ref = static::recordRef($uuid);
        $to = $toGroupUuid !== null
            ? sprintf(' to (%s)', static::recordRef($toGroupUuid))
            : '';

        return static::capture(<<<APPLESCRIPT
            set theRecord to $ref
            set theDuplicate to duplicate record theRecord$to
            if theDuplicate is not missing value then return uuid of theDuplicate as text
        APPLESCRIPT) ?: null;
    }

    public static function move(string $uuid, string $toGroupUuid): ?string
    {
        $ref = static::recordRef($uuid);
        $groupRef = static::recordRef($toGroupUuid);

        return static::capture(<<<APPLESCRIPT
            set theRecord to $ref
            set theGroup to $groupRef
            set theMoved to move record theRecord to theGroup
            if theMoved is not missing value then return uuid of theMoved as text
        APPLESCRIPT) ?: null;
    }

    public static function moveIntoDatabase(string $uuid, string $databaseName): ?string
    {
        $ref = static::recordRef($uuid);
        $db = static::escape($databaseName);

        return static::capture(<<<APPLESCRIPT
            set theRecord to $ref
            set theDatabase to database "$db"
            set theMoved to move into database record theRecord database theDatabase
            if theMoved is not missing value then return uuid of theMoved as text
        APPLESCRIPT) ?: null;
    }

    public static function moveToExternalFolder(string $uuid): bool
    {
        $ref = static::recordRef($uuid);

        return static::parseBool(static::capture(<<<APPLESCRIPT
            set theRecord to $ref
            return (move to external folder record theRecord) as text
        APPLESCRIPT));
    }

    public static function replicate(string $uuid, string $toGroupUuid): ?string
    {
        $ref = static::recordRef($uuid);
        $groupRef = static::recordRef($toGroupUuid);

        return static::capture(<<<APPLESCRIPT
            set theRecord to $ref
            set theGroup to $groupRef
            set theReplicate to replicate record theRecord to theGroup
            if theReplicate is not missing value then return uuid of theReplicate as text
        APPLESCRIPT) ?: null;
    }

    public static function merge(array $uuids, ?string $groupUuid = null): ?string
    {
        $refs = '{'.implode(', ', array_map(fn (string $uuid) => sprintf('(%s)', static::recordRef($uuid)), $uuids)).'}';
        $group = $groupUuid !== null ? sprintf(' in (%s)', static::recordRef($groupUuid)) : '';

        return static::capture(<<<APPLESCRIPT
            set theMerged to merge $refs$group
            if theMerged is not missing value then return uuid of theMerged as text
        APPLESCRIPT) ?: null;
    }

    public static function update(string $uuid, string $text, UpdateMode $mode, ?string $url = null): bool
    {
        $ref = static::recordRef($uuid);
        $escapedText = static::escape($text);
        $modeValue = $mode->value;
        $urlPart = $url !== null ? sprintf(' URL "%s"', static::escape($url)) : '';

        return static::parseBool(static::capture(<<<APPLESCRIPT
            set theRecord to $ref
            return (update record theRecord with text "$escapedText" mode $modeValue$urlPart) as text
        APPLESCRIPT));
    }

    // =========================================================================
    // Record Access
    // =========================================================================

    public static function pathToRecord(string $uuid): string
    {
        return AppleScript::executeAndCapture(AppleScript::devonthinkPathToRecord($uuid));
    }

    public static function getRecordContents(string $uuid): string
    {
        return file_get_contents(static::pathToRecord($uuid));
    }

    public static function getRecordAt(string $path, ?string $databaseName = null): ?string
    {
        $escapedPath = static::escape($path);
        $in = $databaseName !== null ? sprintf(' in database "%s"', static::escape($databaseName)) : '';

        return static::capture(<<<APPLESCRIPT
            set theRecord to get record at "$escapedPath"$in
            if theRecord is not missing value then return uuid of theRecord as text
        APPLESCRIPT) ?: null;
    }

    public static function getRecordWithId(int $id, ?string $databaseName = null): ?string
    {
        $in = $databaseName !== null ? sprintf(' in database "%s"', static::escape($databaseName)) : '';

        return static::capture(<<<APPLESCRIPT
            set theRecord to get record with id $id$in
            if theRecord is not missing value then return uuid of theRecord as text
        APPLESCRIPT) ?: null;
    }

    public static function getRecordWithUuid(string $uuid): ?string
    {
        $ref = static::recordRef($uuid);

        return static::capture(<<<APPLESCRIPT
            set theRecord to $ref
            if theRecord is not missing value then return uuid of theRecord as text
        APPLESCRIPT) ?: null;
    }

    public static function getDatabaseWithId(int $id): ?string
    {
        return static::capture(<<<APPLESCRIPT
            set theDatabase to get database with id $id
            if theDatabase is not missing value then return name of theDatabase as text
        APPLESCRIPT) ?: null;
    }

    public static function getDatabaseWithUuid(string $uuid): ?string
    {
        $escaped = static::escape($uuid);

        return static::capture(<<<APPLESCRIPT
            set theDatabase to get database with uuid "$escaped"
            if theDatabase is not missing value then return name of theDatabase as text
        APPLESCRIPT) ?: null;
    }

    // =========================================================================
    // Record Content
    // =========================================================================

    public static function getTextOf(string $uuid): string
    {
        $ref = static::recordRef($uuid);

        return static::capture(<<<APPLESCRIPT
            set theRecord to $ref
            return get text of theRecord
        APPLESCRIPT);
    }

    public static function getRichTextOf(string $uuid): string
    {
        $ref = static::recordRef($uuid);

        return static::capture(<<<APPLESCRIPT
            set theRecord to $ref
            return get rich text of theRecord
        APPLESCRIPT);
    }

    public static function getTitleOf(string $uuid): string
    {
        $ref = static::recordRef($uuid);

        return static::capture(<<<APPLESCRIPT
            set theRecord to $ref
            return get title of theRecord
        APPLESCRIPT);
    }

    public static function getMetadataOf(string $uuid): string
    {
        $ref = static::recordRef($uuid);

        return static::capture(<<<APPLESCRIPT
            set theRecord to $ref
            return get metadata of theRecord
        APPLESCRIPT);
    }

    public static function getConcordanceOf(string $uuid): string
    {
        $ref = static::recordRef($uuid);

        return static::capture(<<<APPLESCRIPT
            set theRecord to $ref
            return get concordance of theRecord
        APPLESCRIPT);
    }

    public static function getLinksOf(string $uuid): array
    {
        $ref = static::recordRef($uuid);

        return static::captureList(<<<APPLESCRIPT
            set theRecord to $ref
            set theRecords to get links of theRecord
        APPLESCRIPT, 'theRecords', 'r as text');
    }

    public static function getEmbeddedImagesOf(string $uuid): array
    {
        $ref = static::recordRef($uuid);

        return static::captureList(<<<APPLESCRIPT
            set theRecord to $ref
            set theRecords to get embedded images of theRecord
        APPLESCRIPT);
    }

    public static function getEmbeddedObjectsOf(string $uuid): array
    {
        $ref = static::recordRef($uuid);

        return static::captureList(<<<APPLESCRIPT
            set theRecord to $ref
            set theRecords to get embedded objects of theRecord
        APPLESCRIPT);
    }

    public static function getEmbeddedSheetsAndScriptsOf(string $uuid): array
    {
        $ref = static::recordRef($uuid);

        return static::captureList(<<<APPLESCRIPT
            set theRecord to $ref
            set theRecords to get embedded sheets and scripts of theRecord
        APPLESCRIPT);
    }

    public static function getFramesOf(string $uuid): array
    {
        $ref = static::recordRef($uuid);

        return static::captureList(<<<APPLESCRIPT
            set theRecord to $ref
            set theRecords to get frames of theRecord
        APPLESCRIPT);
    }

    public static function getFaviconOf(string $uuid): string
    {
        $ref = static::recordRef($uuid);

        return static::capture(<<<APPLESCRIPT
            set theRecord to $ref
            return get favicon of theRecord
        APPLESCRIPT);
    }

    // =========================================================================
    // Custom Meta Data
    // =========================================================================

    public static function addCustomMetaData(mixed $value, string $forKey, string $uuid): void
    {
        $ref = static::recordRef($uuid);
        $key = static::escape($forKey);
        if (is_string($value)) {
            $val = sprintf('"%s"', static::escape($value));
        } elseif (is_bool($value)) {
            $val = $value ? 'true' : 'false';
        } else {
            $val = (string) $value;
        }

        static::execute(<<<APPLESCRIPT
            set theRecord to $ref
            add custom meta data $val for "$key" to theRecord
        APPLESCRIPT);
    }

    public static function getCustomMetaData(string $forKey, string $uuid): string
    {
        $ref = static::recordRef($uuid);
        $key = static::escape($forKey);

        return static::capture(<<<APPLESCRIPT
            set theRecord to $ref
            return get custom meta data for "$key" from theRecord
        APPLESCRIPT);
    }

    // =========================================================================
    // Search & Lookup
    // =========================================================================

    public static function search(string $query, ?string $inGroupUuid = null, ?string $comparison = null, bool $excludeSubgroups = false): array
    {
        $escaped = static::escape($query);
        $in = $inGroupUuid !== null ? sprintf(' in (%s)', static::recordRef($inGroupUuid)) : '';
        $comp = $comparison !== null ? sprintf(' comparison %s', $comparison) : '';
        $exclude = $excludeSubgroups ? ' exclude subgroups true' : '';

        return static::captureList(<<<APPLESCRIPT
            set theRecords to search "$escaped"$comp$exclude$in
        APPLESCRIPT);
    }

    public static function lookupRecordsWithComment(string $comment, ?string $databaseName = null): array
    {
        $escaped = static::escape($comment);
        $in = $databaseName !== null ? sprintf(' in database "%s"', static::escape($databaseName)) : '';

        return static::captureList(<<<APPLESCRIPT
            set theRecords to lookup records with comment "$escaped"$in
        APPLESCRIPT);
    }

    public static function lookupRecordsWithContentHash(string $hash, ?string $databaseName = null): array
    {
        $escaped = static::escape($hash);
        $in = $databaseName !== null ? sprintf(' in database "%s"', static::escape($databaseName)) : '';

        return static::captureList(<<<APPLESCRIPT
            set theRecords to lookup records with content hash "$escaped"$in
        APPLESCRIPT);
    }

    public static function lookupRecordsWithFile(string $path, ?string $databaseName = null): array
    {
        $escaped = static::escape($path);
        $in = $databaseName !== null ? sprintf(' in database "%s"', static::escape($databaseName)) : '';

        return static::captureList(<<<APPLESCRIPT
            set theRecords to lookup records with file "$escaped"$in
        APPLESCRIPT);
    }

    public static function lookupRecordsWithPath(string $path, ?string $databaseName = null): array
    {
        $escaped = static::escape($path);
        $in = $databaseName !== null ? sprintf(' in database "%s"', static::escape($databaseName)) : '';

        return static::captureList(<<<APPLESCRIPT
            set theRecords to lookup records with path "$escaped"$in
        APPLESCRIPT);
    }

    public static function lookupRecordsWithTags(array $tags, ?string $databaseName = null): array
    {
        $tagList = implode('", "', array_map(fn ($t) => static::escape($t), $tags));
        $in = $databaseName !== null ? sprintf(' in database "%s"', static::escape($databaseName)) : '';

        return static::captureList(<<<APPLESCRIPT
            set theRecords to lookup records with tags {"$tagList"}$in
        APPLESCRIPT);
    }

    public static function lookupRecordsWithUrl(string $url, ?string $databaseName = null): array
    {
        $escaped = static::escape($url);
        $in = $databaseName !== null ? sprintf(' in database "%s"', static::escape($databaseName)) : '';

        return static::captureList(<<<APPLESCRIPT
            set theRecords to lookup records with URL "$escaped"$in
        APPLESCRIPT);
    }

    public static function exists(string $uuid): bool
    {
        $ref = static::recordRef($uuid);

        return static::parseBool(static::capture(<<<APPLESCRIPT
            return (exists record id (id of ($ref))) as text
        APPLESCRIPT));
    }

    public static function existsRecordAt(string $path, ?string $databaseName = null): bool
    {
        $escaped = static::escape($path);
        $in = $databaseName !== null ? sprintf(' in database "%s"', static::escape($databaseName)) : '';

        return static::parseBool(static::capture(<<<APPLESCRIPT
            return (exists record at "$escaped"$in) as text
        APPLESCRIPT));
    }

    public static function existsRecordWithComment(string $comment, ?string $databaseName = null): bool
    {
        $escaped = static::escape($comment);
        $in = $databaseName !== null ? sprintf(' in database "%s"', static::escape($databaseName)) : '';

        return static::parseBool(static::capture(<<<APPLESCRIPT
            return (exists record with comment "$escaped"$in) as text
        APPLESCRIPT));
    }

    public static function existsRecordWithContentHash(string $hash, ?string $databaseName = null): bool
    {
        $escaped = static::escape($hash);
        $in = $databaseName !== null ? sprintf(' in database "%s"', static::escape($databaseName)) : '';

        return static::parseBool(static::capture(<<<APPLESCRIPT
            return (exists record with content hash "$escaped"$in) as text
        APPLESCRIPT));
    }

    public static function existsRecordWithFile(string $path, ?string $databaseName = null): bool
    {
        $escaped = static::escape($path);
        $in = $databaseName !== null ? sprintf(' in database "%s"', static::escape($databaseName)) : '';

        return static::parseBool(static::capture(<<<APPLESCRIPT
            return (exists record with file "$escaped"$in) as text
        APPLESCRIPT));
    }

    public static function existsRecordWithPath(string $path, ?string $databaseName = null): bool
    {
        $escaped = static::escape($path);
        $in = $databaseName !== null ? sprintf(' in database "%s"', static::escape($databaseName)) : '';

        return static::parseBool(static::capture(<<<APPLESCRIPT
            return (exists record with path "$escaped"$in) as text
        APPLESCRIPT));
    }

    public static function existsRecordWithUrl(string $url, ?string $databaseName = null): bool
    {
        $escaped = static::escape($url);
        $in = $databaseName !== null ? sprintf(' in database "%s"', static::escape($databaseName)) : '';

        return static::parseBool(static::capture(<<<APPLESCRIPT
            return (exists record with URL "$escaped"$in) as text
        APPLESCRIPT));
    }

    public static function classify(string $uuid, ?string $groupUuid = null): array
    {
        $ref = static::recordRef($uuid);
        $in = $groupUuid !== null ? sprintf(' in (%s)', static::recordRef($groupUuid)) : '';

        return static::captureList(<<<APPLESCRIPT
            set theRecord to $ref
            set theRecords to classify record theRecord$in
        APPLESCRIPT);
    }

    public static function compare(string $uuid, string $toUuid): float
    {
        $ref = static::recordRef($uuid);
        $otherRef = static::recordRef($toUuid);

        return (float) static::capture(<<<APPLESCRIPT
            set theRecord to $ref
            set theOther to $otherRef
            return (compare record theRecord to theOther) as text
        APPLESCRIPT);
    }

    public static function count(string $uuid): int
    {
        $ref = static::recordRef($uuid);

        return (int) static::capture(<<<APPLESCRIPT
            set theRecord to $ref
            return (count of theRecord) as text
        APPLESCRIPT);
    }

    // =========================================================================
    // Import & Export
    // =========================================================================

    public static function importRecords(array|string $paths): void
    {
        $paths = is_string($paths) ? [$paths] : $paths;

        foreach (AppleScript::devonthinkImportRecords($paths) as $applescript) {
            AppleScript::execute($applescript);
        }
    }

    public static function importPath(string $path, ?string $groupUuid = null): ?string
    {
        $escaped = static::escape($path);
        $to = $groupUuid !== null ? sprintf(' to (%s)', static::recordRef($groupUuid)) : '';

        return static::capture(<<<APPLESCRIPT
            set theRecord to import "$escaped"$to
            if theRecord is not missing value then return uuid of theRecord as text
        APPLESCRIPT) ?: null;
    }

    public static function importTemplate(string $path, ?string $groupUuid = null): ?string
    {
        $escaped = static::escape($path);
        $to = $groupUuid !== null ? sprintf(' to (%s)', static::recordRef($groupUuid)) : '';

        return static::capture(<<<APPLESCRIPT
            set theRecord to import template "$escaped"$to
            if theRecord is not missing value then return uuid of theRecord as text
        APPLESCRIPT) ?: null;
    }

    public static function importAttachmentsOf(string $uuid, ?string $groupUuid = null): array
    {
        $ref = static::recordRef($uuid);
        $to = $groupUuid !== null ? sprintf(' to (%s)', static::recordRef($groupUuid)) : '';

        return static::captureList(<<<APPLESCRIPT
            set theRecord to $ref
            set theRecords to import attachments of theRecord$to
        APPLESCRIPT);
    }

    public static function indexPath(string $path, ?string $groupUuid = null): ?string
    {
        $escaped = static::escape($path);
        $to = $groupUuid !== null ? sprintf(' to (%s)', static::recordRef($groupUuid)) : '';

        return static::capture(<<<APPLESCRIPT
            set theRecord to index "$escaped"$to
            if theRecord is not missing value then return uuid of theRecord as text
        APPLESCRIPT) ?: null;
    }

    public static function export(string $uuid, string $toPath): bool
    {
        $ref = static::recordRef($uuid);
        $to = static::escape($toPath);

        return static::parseBool(static::capture(<<<APPLESCRIPT
            set theRecord to $ref
            return (export record theRecord to "$to") as text
        APPLESCRIPT));
    }

    public static function exportTagsOf(string $uuid): string
    {
        $ref = static::recordRef($uuid);

        return static::capture(<<<APPLESCRIPT
            set theRecord to $ref
            return export tags of theRecord
        APPLESCRIPT);
    }

    public static function exportWebsite(string $uuid, string $toPath): bool
    {
        $ref = static::recordRef($uuid);
        $to = static::escape($toPath);

        return static::parseBool(static::capture(<<<APPLESCRIPT
            set theRecord to $ref
            return (export website record theRecord to "$to") as text
        APPLESCRIPT));
    }

    // =========================================================================
    // Web Capture & Download
    // =========================================================================

    public static function createWebDocumentFrom(string $url, ?string $groupUuid = null): ?string
    {
        $escaped = static::escape($url);
        $in = $groupUuid !== null ? sprintf(' in (%s)', static::recordRef($groupUuid)) : '';

        return static::capture(<<<APPLESCRIPT
            set theRecord to create web document from "$escaped"$in
            if theRecord is not missing value then return uuid of theRecord as text
        APPLESCRIPT) ?: null;
    }

    public static function createMarkdownFrom(string $url, ?string $groupUuid = null): ?string
    {
        $escaped = static::escape($url);
        $in = $groupUuid !== null ? sprintf(' in (%s)', static::recordRef($groupUuid)) : '';

        return static::capture(<<<APPLESCRIPT
            set theRecord to create Markdown from "$escaped"$in
            if theRecord is not missing value then return uuid of theRecord as text
        APPLESCRIPT) ?: null;
    }

    public static function createPdfDocumentFrom(
        string $url,
        ?string $groupUuid = null,
        bool $pagination = false,
        int $width = 1280,
    ): ?string {
        $escaped = static::escape($url);
        $in = $groupUuid !== null ? sprintf(' in (%s)', static::recordRef($groupUuid)) : '';
        $paginationStr = $pagination ? 'true' : 'false';

        return static::capture(<<<APPLESCRIPT
            set theRecord to create PDF document from "$escaped" pagination $paginationStr width $width$in
            if theRecord is not missing value then return uuid of theRecord as text
        APPLESCRIPT) ?: null;
    }

    public static function createFormattedNoteFrom(string $url, ?string $groupUuid = null): ?string
    {
        $escaped = static::escape($url);
        $in = $groupUuid !== null ? sprintf(' in (%s)', static::recordRef($groupUuid)) : '';

        return static::capture(<<<APPLESCRIPT
            set theRecord to create formatted note from "$escaped"$in
            if theRecord is not missing value then return uuid of theRecord as text
        APPLESCRIPT) ?: null;
    }

    public static function downloadUrl(string $url, ?string $groupUuid = null): ?string
    {
        $escaped = static::escape($url);
        $in = $groupUuid !== null ? sprintf(' in (%s)', static::recordRef($groupUuid)) : '';

        return static::capture(<<<APPLESCRIPT
            set theRecord to download URL "$escaped"$in
            if theRecord is not missing value then return uuid of theRecord as text
        APPLESCRIPT) ?: null;
    }

    public static function downloadMarkupFrom(string $url): string
    {
        $escaped = static::escape($url);

        return static::capture(<<<APPLESCRIPT
            return download markup from "$escaped"
        APPLESCRIPT);
    }

    public static function addDownload(string $url): void
    {
        $escaped = static::escape($url);

        static::execute(<<<APPLESCRIPT
            add download "$escaped"
        APPLESCRIPT);
    }

    public static function addReadingList(string $url): void
    {
        $escaped = static::escape($url);

        static::execute(<<<APPLESCRIPT
            add reading list "$escaped"
        APPLESCRIPT);
    }

    public static function getCachedDataForUrl(string $url): string
    {
        $escaped = static::escape($url);

        return static::capture(<<<APPLESCRIPT
            return get cached data for URL "$escaped"
        APPLESCRIPT);
    }

    public static function startDownloads(): bool
    {
        return static::parseBool(static::capture(<<<'APPLESCRIPT'
            return (start downloads) as text
        APPLESCRIPT));
    }

    public static function stopDownloads(): bool
    {
        return static::parseBool(static::capture(<<<'APPLESCRIPT'
            return (stop downloads) as text
        APPLESCRIPT));
    }

    // =========================================================================
    // Conversion
    // =========================================================================

    public static function convert(string $uuid, string $toType, ?string $groupUuid = null): ?string
    {
        $ref = static::recordRef($uuid);
        $in = $groupUuid !== null ? sprintf(' in (%s)', static::recordRef($groupUuid)) : '';

        return static::capture(<<<APPLESCRIPT
            set theRecord to $ref
            set theConverted to convert record theRecord to $toType$in
            if theConverted is not missing value then return uuid of theConverted as text
        APPLESCRIPT) ?: null;
    }

    public static function convertFeedToHtml(string $uuid): string
    {
        $ref = static::recordRef($uuid);

        return static::capture(<<<APPLESCRIPT
            set theRecord to $ref
            return convert feed to HTML theRecord
        APPLESCRIPT);
    }

    public static function convertImage(string $uuid, ?string $groupUuid = null): ?string
    {
        $ref = static::recordRef($uuid);
        $in = $groupUuid !== null ? sprintf(' in (%s)', static::recordRef($groupUuid)) : '';

        return static::capture(<<<APPLESCRIPT
            set theRecord to $ref
            set theConverted to convert image theRecord$in
            if theConverted is not missing value then return uuid of theConverted as text
        APPLESCRIPT) ?: null;
    }

    // =========================================================================
    // Text Content
    // =========================================================================

    public static function savePlainTextToRecord(string $text, string $uuid): void
    {
        AppleScript::execute(AppleScript::devonthinkSavePlainTextToRecord($text, $uuid));
    }

    public static function extractKeywordsFrom(string $text): string
    {
        $escaped = static::escape($text);

        return static::capture(<<<APPLESCRIPT
            return extract keywords from "$escaped"
        APPLESCRIPT);
    }

    // =========================================================================
    // OCR
    // =========================================================================

    public static function ocr(string $uuid, ?string $groupUuid = null): ?string
    {
        $ref = static::recordRef($uuid);
        $in = $groupUuid !== null ? sprintf(' in (%s)', static::recordRef($groupUuid)) : '';

        return static::capture(<<<APPLESCRIPT
            set theRecord to $ref
            set theOcr to ocr theRecord$in
            if theOcr is not missing value then return uuid of theOcr as text
        APPLESCRIPT) ?: null;
    }

    // =========================================================================
    // Database Operations
    // =========================================================================

    public static function createDatabase(string $path): ?string
    {
        $escaped = static::escape($path);

        return static::capture(<<<APPLESCRIPT
            set theDatabase to create database "$escaped"
            if theDatabase is not missing value then return name of theDatabase as text
        APPLESCRIPT) ?: null;
    }

    public static function openDatabase(string $path): ?string
    {
        $escaped = static::escape($path);

        return static::capture(<<<APPLESCRIPT
            set theDatabase to open database "$escaped"
            if theDatabase is not missing value then return name of theDatabase as text
        APPLESCRIPT) ?: null;
    }

    public static function optimize(string $databaseName): bool
    {
        $escaped = static::escape($databaseName);

        return static::parseBool(static::capture(<<<APPLESCRIPT
            return (optimize database "$escaped") as text
        APPLESCRIPT));
    }

    public static function verify(string $databaseName): int
    {
        $escaped = static::escape($databaseName);

        return (int) static::capture(<<<APPLESCRIPT
            return (verify database "$escaped") as text
        APPLESCRIPT);
    }

    public static function checkFileIntegrityOf(string $uuid): int
    {
        $ref = static::recordRef($uuid);

        return (int) static::capture(<<<APPLESCRIPT
            set theRecord to $ref
            return (check file integrity of theRecord) as text
        APPLESCRIPT);
    }

    public static function compress(string $databaseName): bool
    {
        $escaped = static::escape($databaseName);

        return static::parseBool(static::capture(<<<APPLESCRIPT
            return (compress database "$escaped") as text
        APPLESCRIPT));
    }

    public static function synchronize(?string $uuid = null, ?string $databaseName = null): bool
    {
        $parts = [];
        if ($uuid !== null) {
            $parts[] = sprintf('record (%s)', static::recordRef($uuid));
        }
        if ($databaseName !== null) {
            $parts[] = sprintf('database "%s"', static::escape($databaseName));
        }
        $params = implode(' ', $parts);

        return static::parseBool(static::capture(<<<APPLESCRIPT
            return (synchronize $params) as text
        APPLESCRIPT));
    }

    public static function createLocation(string $path, ?string $databaseName = null): ?string
    {
        $escaped = static::escape($path);
        $in = $databaseName !== null ? sprintf(' in database "%s"', static::escape($databaseName)) : '';

        return static::capture(<<<APPLESCRIPT
            set theRecord to create location "$escaped"$in
            if theRecord is not missing value then return uuid of theRecord as text
        APPLESCRIPT) ?: null;
    }

    // =========================================================================
    // Thumbnails
    // =========================================================================

    public static function createThumbnail(string $uuid): bool
    {
        $ref = static::recordRef($uuid);

        return static::parseBool(static::capture(<<<APPLESCRIPT
            set theRecord to $ref
            return (create thumbnail of theRecord) as text
        APPLESCRIPT));
    }

    public static function deleteThumbnail(string $uuid): bool
    {
        $ref = static::recordRef($uuid);

        return static::parseBool(static::capture(<<<APPLESCRIPT
            set theRecord to $ref
            return (delete thumbnail of theRecord) as text
        APPLESCRIPT));
    }

    public static function updateThumbnail(string $uuid): bool
    {
        $ref = static::recordRef($uuid);

        return static::parseBool(static::capture(<<<APPLESCRIPT
            set theRecord to $ref
            return (update thumbnail of theRecord) as text
        APPLESCRIPT));
    }

    // =========================================================================
    // Versions
    // =========================================================================

    public static function getVersionsOf(string $uuid): array
    {
        $ref = static::recordRef($uuid);

        return static::captureList(<<<APPLESCRIPT
            set theRecord to $ref
            set theRecords to get versions of record theRecord
        APPLESCRIPT);
    }

    public static function saveVersionOf(string $uuid): ?string
    {
        $ref = static::recordRef($uuid);

        return static::capture(<<<APPLESCRIPT
            set theRecord to $ref
            set theVersion to save version of record theRecord
            if theVersion is not missing value then return uuid of theVersion as text
        APPLESCRIPT) ?: null;
    }

    public static function restoreRecordWith(string $versionUuid): bool
    {
        $ref = static::recordRef($versionUuid);

        return static::parseBool(static::capture(<<<APPLESCRIPT
            set theVersion to $ref
            return (restore record with version theVersion) as text
        APPLESCRIPT));
    }

    // =========================================================================
    // AI & Chat (requires DEVONthink 4)
    // =========================================================================

    /** @since DEVONthink 4 */
    public static function getChatCapabilitiesForEngine(string $engine): string
    {
        $escaped = static::escape($engine);

        return static::capture(<<<APPLESCRIPT
            return get chat capabilities for engine "$escaped"
        APPLESCRIPT);
    }

    /** @since DEVONthink 4 */
    public static function getChatModelsForEngine(string $engine): string
    {
        $escaped = static::escape($engine);

        return static::capture(<<<APPLESCRIPT
            return get chat models for engine "$escaped"
        APPLESCRIPT);
    }

    /** @since DEVONthink 4 */
    public static function getChatResponseForMessage(string $message, ?string $engine = null, ?string $model = null): string
    {
        $escaped = static::escape($message);
        $parts = [];
        if ($engine !== null) {
            $parts[] = sprintf('engine "%s"', static::escape($engine));
        }
        if ($model !== null) {
            $parts[] = sprintf('model "%s"', static::escape($model));
        }
        $params = $parts !== [] ? ' '.implode(' ', $parts) : '';

        return static::capture(<<<APPLESCRIPT
            return get chat response for message "$escaped"$params
        APPLESCRIPT);
    }

    /** @since DEVONthink 4 */
    public static function downloadImageForPrompt(string $prompt, ?string $engine = null): string
    {
        $escaped = static::escape($prompt);
        $enginePart = $engine !== null ? sprintf(' engine "%s"', static::escape($engine)) : '';

        return static::capture(<<<APPLESCRIPT
            return download image for prompt "$escaped"$enginePart
        APPLESCRIPT);
    }

    /** @since DEVONthink 4 */
    public static function summarizeText(string $text, ?string $style = null): string
    {
        $escaped = static::escape($text);
        $asPart = $style !== null ? sprintf(' as %s', $style) : '';

        return static::capture(<<<APPLESCRIPT
            return summarize text "$escaped"$asPart
        APPLESCRIPT);
    }

    /** @since DEVONthink 4 */
    public static function summarizeAnnotationsOf(array $uuids, SummaryType $type, ?string $groupUuid = null): ?string
    {
        $refs = '{'.implode(', ', array_map(fn (string $uuid) => sprintf('(%s)', static::recordRef($uuid)), $uuids)).'}';
        $typeValue = $type->value;
        $in = $groupUuid !== null ? sprintf(' in (%s)', static::recordRef($groupUuid)) : '';

        return static::capture(<<<APPLESCRIPT
            set theRecord to summarize annotations of records $refs to $typeValue$in
            if theRecord is not missing value then return uuid of theRecord as text
        APPLESCRIPT) ?: null;
    }

    /** @since DEVONthink 4 */
    public static function summarizeContentsOf(array $uuids, SummaryType $type, ?string $style = null, ?string $groupUuid = null): ?string
    {
        $refs = '{'.implode(', ', array_map(fn (string $uuid) => sprintf('(%s)', static::recordRef($uuid)), $uuids)).'}';
        $typeValue = $type->value;
        $in = $groupUuid !== null ? sprintf(' in (%s)', static::recordRef($groupUuid)) : '';
        $asPart = $style !== null ? sprintf(' as %s', $style) : '';

        return static::capture(<<<APPLESCRIPT
            set theRecord to summarize contents of records $refs to $typeValue$asPart$in
            if theRecord is not missing value then return uuid of theRecord as text
        APPLESCRIPT) ?: null;
    }

    /** @since DEVONthink 4 */
    public static function summarizeMentionsOf(array $uuids, SummaryType $type, ?string $groupUuid = null): ?string
    {
        $refs = '{'.implode(', ', array_map(fn (string $uuid) => sprintf('(%s)', static::recordRef($uuid)), $uuids)).'}';
        $typeValue = $type->value;
        $in = $groupUuid !== null ? sprintf(' in (%s)', static::recordRef($groupUuid)) : '';

        return static::capture(<<<APPLESCRIPT
            set theRecord to summarize mentions of records $refs to $typeValue$in
            if theRecord is not missing value then return uuid of theRecord as text
        APPLESCRIPT) ?: null;
    }

    /** @since DEVONthink 4 */
    public static function transcribe(string $uuid, ?string $language = null, ?bool $timestamps = null): string
    {
        $ref = static::recordRef($uuid);
        $parts = [];
        if ($language !== null) {
            $parts[] = sprintf('language "%s"', static::escape($language));
        }
        if ($timestamps !== null) {
            $parts[] = sprintf('timestamps %s', $timestamps ? 'true' : 'false');
        }
        $params = $parts !== [] ? ' '.implode(' ', $parts) : '';

        return static::capture(<<<APPLESCRIPT
            set theRecord to $ref
            return transcribe record theRecord$params
        APPLESCRIPT);
    }

    // =========================================================================
    // Feeds
    // =========================================================================

    public static function getFeedItemsOf(string $uuid): array
    {
        $ref = static::recordRef($uuid);

        return static::captureList(<<<APPLESCRIPT
            set theRecord to $ref
            set theRecords to get feed items of theRecord
        APPLESCRIPT);
    }

    public static function getItemsOfFeed(string $uuid): array
    {
        $ref = static::recordRef($uuid);

        return static::captureList(<<<APPLESCRIPT
            set theRecord to $ref
            set theRecords to get items of feed theRecord
        APPLESCRIPT);
    }

    public static function refresh(string $uuid): bool
    {
        $ref = static::recordRef($uuid);

        return static::parseBool(static::capture(<<<APPLESCRIPT
            set theRecord to $ref
            return (refresh record theRecord) as text
        APPLESCRIPT));
    }

    // =========================================================================
    // Sheets
    // =========================================================================

    public static function addRow(string $uuid): void
    {
        $ref = static::recordRef($uuid);

        static::execute(<<<APPLESCRIPT
            set theRecord to $ref
            add row to theRecord
        APPLESCRIPT);
    }

    public static function deleteRowAt(int $row, string $uuid): void
    {
        $ref = static::recordRef($uuid);

        static::execute(<<<APPLESCRIPT
            set theRecord to $ref
            delete row at $row of theRecord
        APPLESCRIPT);
    }

    public static function getCellAt(int $column, int $row, string $uuid): string
    {
        $ref = static::recordRef($uuid);

        return static::capture(<<<APPLESCRIPT
            set theRecord to $ref
            return get cell at column $column row $row of theRecord
        APPLESCRIPT);
    }

    public static function setCellAt(int $column, int $row, string $value, string $uuid): bool
    {
        $ref = static::recordRef($uuid);
        $escaped = static::escape($value);

        return static::parseBool(static::capture(<<<APPLESCRIPT
            set theRecord to $ref
            return (set cell at column $column row $row to "$escaped" of theRecord) as text
        APPLESCRIPT));
    }

    // =========================================================================
    // UI Dialogs
    // =========================================================================

    public static function displayAuthenticationDialog(string $message, ?string $title = null): array
    {
        $escaped = static::escape($message);
        $titlePart = $title !== null ? sprintf(' with title "%s"', static::escape($title)) : '';

        $result = static::capture(<<<APPLESCRIPT
            set theResult to display authentication dialog "$escaped"$titlePart
            return (name of theResult) & linefeed & (password of theResult)
        APPLESCRIPT);

        $lines = explode("\n", $result);

        return [
            'username' => $lines[0] ?? '',
            'password' => $lines[1] ?? '',
        ];
    }

    public static function displayChatDialog(?string $message = null, ?string $title = null): string
    {
        $parts = [];
        if ($message !== null) {
            $parts[] = sprintf('"%s"', static::escape($message));
        }
        if ($title !== null) {
            $parts[] = sprintf('with title "%s"', static::escape($title));
        }
        $params = implode(' ', $parts);

        return static::capture(<<<APPLESCRIPT
            return display chat dialog $params
        APPLESCRIPT);
    }

    public static function displayDateEditor(?string $date = null, ?string $title = null): string
    {
        $parts = [];
        if ($date !== null) {
            $parts[] = sprintf('"%s"', static::escape($date));
        }
        if ($title !== null) {
            $parts[] = sprintf('with title "%s"', static::escape($title));
        }
        $params = implode(' ', $parts);

        return static::capture(<<<APPLESCRIPT
            return display date editor $params
        APPLESCRIPT);
    }

    public static function displayGroupSelector(string $message, ?string $title = null, ?string $databaseName = null): ?string
    {
        $escaped = static::escape($message);
        $titlePart = $title !== null ? sprintf(' with title "%s"', static::escape($title)) : '';
        $dbPart = $databaseName !== null ? sprintf(' for database "%s"', static::escape($databaseName)) : '';

        return static::capture(<<<APPLESCRIPT
            set theGroup to display group selector "$escaped"$titlePart$dbPart
            if theGroup is not missing value then return uuid of theGroup as text
        APPLESCRIPT) ?: null;
    }

    public static function displayNameEditor(string $message, ?string $title = null, ?string $defaultName = null): string
    {
        $escaped = static::escape($message);
        $titlePart = $title !== null ? sprintf(' with title "%s"', static::escape($title)) : '';
        $defaultPart = $defaultName !== null ? sprintf(' default name "%s"', static::escape($defaultName)) : '';

        return static::capture(<<<APPLESCRIPT
            return display name editor "$escaped"$titlePart$defaultPart
        APPLESCRIPT);
    }

    // =========================================================================
    // UI Windows & Tabs
    // =========================================================================

    public static function open(string $uuid): void
    {
        AppleScript::runCommand(['open', static::pathToRecord($uuid)]);
    }

    public static function openTabFor(?string $uuid = null, ?string $url = null, ?string $referrer = null): void
    {
        $parts = [];
        if ($uuid !== null) {
            $parts[] = sprintf('record (%s)', static::recordRef($uuid));
        }
        if ($url !== null) {
            $parts[] = sprintf('URL "%s"', static::escape($url));
        }
        if ($referrer !== null) {
            $parts[] = sprintf('referrer "%s"', static::escape($referrer));
        }
        $params = implode(' ', $parts);

        static::execute(<<<APPLESCRIPT
            open tab for $params
        APPLESCRIPT);
    }

    public static function openWindowFor(string $uuid, bool $force = false): void
    {
        $ref = static::recordRef($uuid);
        $forcePart = $force ? ' force true' : '';

        static::execute(<<<APPLESCRIPT
            set theRecord to $ref
            open window for record theRecord$forcePart
        APPLESCRIPT);
    }

    public static function showSearch(?string $query = null): void
    {
        $param = $query !== null ? sprintf(' "%s"', static::escape($query)) : '';

        static::execute(<<<APPLESCRIPT
            show search$param
        APPLESCRIPT);
    }

    // =========================================================================
    // Progress Indicator
    // =========================================================================

    public static function showProgressIndicator(string $title, ?bool $cancelButton = null, ?int $steps = null): bool
    {
        $escaped = static::escape($title);
        $parts = [];
        if ($cancelButton !== null) {
            $parts[] = sprintf('cancel button %s', $cancelButton ? 'true' : 'false');
        }
        if ($steps !== null) {
            $parts[] = sprintf('steps %d', $steps);
        }
        $params = $parts !== [] ? ' '.implode(' ', $parts) : '';

        return static::parseBool(static::capture(<<<APPLESCRIPT
            return (show progress indicator "$escaped"$params) as text
        APPLESCRIPT));
    }

    public static function stepProgressIndicator(?string $info = null): bool
    {
        $param = $info !== null ? sprintf(' "%s"', static::escape($info)) : '';

        return static::parseBool(static::capture(<<<APPLESCRIPT
            return (step progress indicator$param) as text
        APPLESCRIPT));
    }

    public static function hideProgressIndicator(): bool
    {
        return static::parseBool(static::capture(<<<'APPLESCRIPT'
            return (hide progress indicator) as text
        APPLESCRIPT));
    }

    // =========================================================================
    // Workspaces
    // =========================================================================

    public static function saveWorkspace(string $name): bool
    {
        $escaped = static::escape($name);

        return static::parseBool(static::capture(<<<APPLESCRIPT
            return (save workspace "$escaped") as text
        APPLESCRIPT));
    }

    public static function loadWorkspace(string $name): void
    {
        $escaped = static::escape($name);

        static::execute(<<<APPLESCRIPT
            load workspace "$escaped"
        APPLESCRIPT);
    }

    public static function deleteWorkspace(string $name): void
    {
        $escaped = static::escape($name);

        static::execute(<<<APPLESCRIPT
            delete workspace "$escaped"
        APPLESCRIPT);
    }

    // =========================================================================
    // Smart Rules
    // =========================================================================

    public static function performSmartRule(?string $name = null, ?string $uuid = null, ?string $trigger = null): bool
    {
        $parts = [];
        if ($name !== null) {
            $parts[] = sprintf('name "%s"', static::escape($name));
        }
        if ($uuid !== null) {
            $parts[] = sprintf('record (%s)', static::recordRef($uuid));
        }
        if ($trigger !== null) {
            $parts[] = sprintf('trigger %s', $trigger);
        }
        $params = $parts !== [] ? ' '.implode(' ', $parts) : '';

        return static::parseBool(static::capture(<<<APPLESCRIPT
            return (perform smart rule$params) as text
        APPLESCRIPT));
    }

    // =========================================================================
    // Imprinting
    // =========================================================================

    public static function imprint(string $uuid, ?string $configuration = null): void
    {
        $ref = static::recordRef($uuid);
        $configPart = $configuration !== null ? sprintf(' configuration "%s"', static::escape($configuration)) : '';

        static::execute(<<<APPLESCRIPT
            set theRecord to $ref
            imprint record theRecord$configPart
        APPLESCRIPT);
    }

    public static function imprintConfiguration(string $name): string
    {
        $escaped = static::escape($name);

        return static::capture(<<<APPLESCRIPT
            return imprint configuration "$escaped"
        APPLESCRIPT);
    }

    public static function imprinterConfigurationNames(): array
    {
        return static::captureList(<<<'APPLESCRIPT'
            set theRecords to imprinter configuration names
        APPLESCRIPT, 'theRecords', 'r as text');
    }

    // =========================================================================
    // Misc Operations
    // =========================================================================

    public static function addReminder(string $uuid, ?string $alarm = null): void
    {
        $ref = static::recordRef($uuid);
        $alarmPart = $alarm !== null ? sprintf(' alarm "%s"', static::escape($alarm)) : '';

        static::execute(<<<APPLESCRIPT
            set theRecord to $ref
            add reminder to theRecord$alarmPart
        APPLESCRIPT);
    }

    public static function logMessage(string $message, ?string $info = null): void
    {
        $escaped = static::escape($message);
        $infoPart = $info !== null ? sprintf(' info "%s"', static::escape($info)) : '';

        static::execute(<<<APPLESCRIPT
            log message "$escaped"$infoPart
        APPLESCRIPT);
    }

    public static function pasteClipboard(?string $groupUuid = null): ?string
    {
        $to = $groupUuid !== null ? sprintf(' to (%s)', static::recordRef($groupUuid)) : '';

        return static::capture(<<<APPLESCRIPT
            set theRecord to paste clipboard$to
            if theRecord is not missing value then return uuid of theRecord as text
        APPLESCRIPT) ?: null;
    }

    public static function make(string $type, array $properties = [], ?string $groupUuid = null): ?string
    {
        $at = $groupUuid !== null ? sprintf(' at (%s)', static::recordRef($groupUuid)) : '';
        $propsPart = '';
        if ($properties !== []) {
            $parts = [];
            foreach ($properties as $key => $value) {
                $key = static::escape($key);
                if (is_bool($value)) {
                    $parts[] = sprintf('%s:%s', $key, $value ? 'true' : 'false');
                } elseif (is_int($value) || is_float($value)) {
                    $parts[] = sprintf('%s:%s', $key, $value);
                } else {
                    $parts[] = sprintf('%s:"%s"', $key, static::escape((string) $value));
                }
            }
            $propsPart = ' with properties {'.implode(', ', $parts).'}';
        }

        return static::capture(<<<APPLESCRIPT
            set theRecord to make new $type$at$propsPart
            if theRecord is not missing value then return uuid of theRecord as text
        APPLESCRIPT) ?: null;
    }
}
