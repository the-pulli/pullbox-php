<?php

use Pulli\Pullbox\DEVONthink;
use Pulli\Pullbox\Enums\RecordType;
use Pulli\Pullbox\Enums\SummaryType;
use Pulli\Pullbox\Enums\UpdateMode;

beforeEach(function () {
    DEVONthink::fake();
});

afterEach(function () {
    DEVONthink::unfake();
});

// =========================================================================
// Record CRUD
// =========================================================================

it('generates AppleScript for createRecordWith', function () {
    DEVONthink::createRecordWith(['name' => 'Test', 'type' => RecordType::Markdown]);

    expect(DEVONthink::lastScript())
        ->toContain('tell application id "DNtp"')
        ->toContain('create record with {name:"Test", type:markdown} in incoming group');
});

it('generates AppleScript for createRecordWith with group', function () {
    DEVONthink::createRecordWith(['name' => 'Test'], 'group-uuid');

    expect(DEVONthink::lastScript())
        ->toContain('create record with {name:"Test"} in (get record with uuid "group-uuid")')
        ->toContain('if theRecord is not missing value then return uuid of theRecord as text');
});

it('generates AppleScript for createRecordWith with boolean property', function () {
    DEVONthink::createRecordWith(['name' => 'Test', 'exclude' => true]);

    expect(DEVONthink::lastScript())
        ->toContain('create record with {name:"Test", exclude:true}');
});

it('generates AppleScript for createRecordWith with numeric property', function () {
    DEVONthink::createRecordWith(['name' => 'Test', 'rating' => 5]);

    expect(DEVONthink::lastScript())
        ->toContain('create record with {name:"Test", rating:5}');
});

it('generates AppleScript for delete', function () {
    DEVONthink::delete('test-uuid');

    expect(DEVONthink::lastScript())
        ->toContain('tell application id "DNtp"')
        ->toContain('set theRecord to get record with uuid "test-uuid"')
        ->toContain('return (delete record theRecord) as text');
});

it('generates AppleScript for duplicate', function () {
    DEVONthink::duplicate('source-uuid');

    expect(DEVONthink::lastScript())
        ->toContain('set theRecord to get record with uuid "source-uuid"')
        ->toContain('set theDuplicate to duplicate record theRecord')
        ->toContain('if theDuplicate is not missing value then return uuid of theDuplicate as text');
});

it('generates AppleScript for duplicate to group', function () {
    DEVONthink::duplicate('source-uuid', 'target-group-uuid');

    expect(DEVONthink::lastScript())
        ->toContain('set theDuplicate to duplicate record theRecord to (get record with uuid "target-group-uuid")');
});

it('generates AppleScript for move', function () {
    DEVONthink::move('record-uuid', 'group-uuid');

    expect(DEVONthink::lastScript())
        ->toContain('set theRecord to get record with uuid "record-uuid"')
        ->toContain('set theGroup to get record with uuid "group-uuid"')
        ->toContain('set theMoved to move record theRecord to theGroup');
});

it('generates AppleScript for moveIntoDatabase', function () {
    DEVONthink::moveIntoDatabase('record-uuid', 'My Database');

    expect(DEVONthink::lastScript())
        ->toContain('set theRecord to get record with uuid "record-uuid"')
        ->toContain('set theDatabase to database "My Database"')
        ->toContain('move into database record theRecord database theDatabase');
});

it('generates AppleScript for moveToExternalFolder', function () {
    DEVONthink::moveToExternalFolder('record-uuid');

    expect(DEVONthink::lastScript())
        ->toContain('set theRecord to get record with uuid "record-uuid"')
        ->toContain('return (move to external folder record theRecord) as text');
});

it('generates AppleScript for replicate', function () {
    DEVONthink::replicate('record-uuid', 'group-uuid');

    expect(DEVONthink::lastScript())
        ->toContain('set theRecord to get record with uuid "record-uuid"')
        ->toContain('set theGroup to get record with uuid "group-uuid"')
        ->toContain('set theReplicate to replicate record theRecord to theGroup');
});

it('generates AppleScript for merge', function () {
    DEVONthink::merge(['uuid-1', 'uuid-2', 'uuid-3']);

    expect(DEVONthink::lastScript())
        ->toContain('set theMerged to merge {(get record with uuid "uuid-1"), (get record with uuid "uuid-2"), (get record with uuid "uuid-3")}')
        ->toContain('if theMerged is not missing value then return uuid of theMerged as text');
});

it('generates AppleScript for merge with group', function () {
    DEVONthink::merge(['uuid-1', 'uuid-2'], 'group-uuid');

    expect(DEVONthink::lastScript())
        ->toContain('merge {(get record with uuid "uuid-1"), (get record with uuid "uuid-2")} in (get record with uuid "group-uuid")');
});

it('generates AppleScript for update', function () {
    DEVONthink::update('record-uuid', 'new content', UpdateMode::Replacing);

    expect(DEVONthink::lastScript())
        ->toContain('set theRecord to get record with uuid "record-uuid"')
        ->toContain('return (update record theRecord with text "new content" mode replacing) as text');
});

it('generates AppleScript for update with URL', function () {
    DEVONthink::update('record-uuid', 'content', UpdateMode::Appending, 'https://example.com');

    expect(DEVONthink::lastScript())
        ->toContain('update record theRecord with text "content" mode appending URL "https://example.com"');
});

// =========================================================================
// Record Access
// =========================================================================

it('generates AppleScript for getRecordAt', function () {
    DEVONthink::getRecordAt('/path/to/record');

    expect(DEVONthink::lastScript())
        ->toContain('set theRecord to get record at "/path/to/record"')
        ->toContain('if theRecord is not missing value then return uuid of theRecord as text');
});

it('generates AppleScript for getRecordAt with database', function () {
    DEVONthink::getRecordAt('/path', 'My DB');

    expect(DEVONthink::lastScript())
        ->toContain('get record at "/path" in database "My DB"');
});

it('generates AppleScript for getRecordWithId', function () {
    DEVONthink::getRecordWithId(42);

    expect(DEVONthink::lastScript())
        ->toContain('set theRecord to get record with id 42');
});

it('generates AppleScript for getRecordWithUuid', function () {
    DEVONthink::getRecordWithUuid('my-uuid');

    expect(DEVONthink::lastScript())
        ->toContain('set theRecord to get record with uuid "my-uuid"')
        ->toContain('if theRecord is not missing value then return uuid of theRecord as text');
});

it('generates AppleScript for getDatabaseWithId', function () {
    DEVONthink::getDatabaseWithId(1);

    expect(DEVONthink::lastScript())
        ->toContain('set theDatabase to get database with id 1')
        ->toContain('if theDatabase is not missing value then return name of theDatabase as text');
});

it('generates AppleScript for getDatabaseWithUuid', function () {
    DEVONthink::getDatabaseWithUuid('db-uuid');

    expect(DEVONthink::lastScript())
        ->toContain('set theDatabase to get database with uuid "db-uuid"');
});

// =========================================================================
// Record Content
// =========================================================================

it('generates AppleScript for getTextOf', function () {
    DEVONthink::getTextOf('record-uuid');

    expect(DEVONthink::lastScript())
        ->toContain('set theRecord to get record with uuid "record-uuid"')
        ->toContain('return get text of theRecord');
});

it('generates AppleScript for getRichTextOf', function () {
    DEVONthink::getRichTextOf('record-uuid');

    expect(DEVONthink::lastScript())
        ->toContain('return get rich text of theRecord');
});

it('generates AppleScript for getTitleOf', function () {
    DEVONthink::getTitleOf('record-uuid');

    expect(DEVONthink::lastScript())
        ->toContain('return get title of theRecord');
});

it('generates AppleScript for getMetadataOf', function () {
    DEVONthink::getMetadataOf('record-uuid');

    expect(DEVONthink::lastScript())
        ->toContain('return get metadata of theRecord');
});

it('generates AppleScript for getConcordanceOf', function () {
    DEVONthink::getConcordanceOf('record-uuid');

    expect(DEVONthink::lastScript())
        ->toContain('return get concordance of theRecord');
});

it('generates AppleScript for getLinksOf', function () {
    DEVONthink::getLinksOf('record-uuid');

    expect(DEVONthink::lastScript())
        ->toContain('set theRecords to get links of theRecord')
        ->toContain('set theResult to theResult & (r as text)');
});

it('generates AppleScript for getEmbeddedImagesOf', function () {
    DEVONthink::getEmbeddedImagesOf('record-uuid');

    expect(DEVONthink::lastScript())
        ->toContain('set theRecords to get embedded images of theRecord');
});

it('generates AppleScript for getEmbeddedObjectsOf', function () {
    DEVONthink::getEmbeddedObjectsOf('record-uuid');

    expect(DEVONthink::lastScript())
        ->toContain('set theRecords to get embedded objects of theRecord');
});

it('generates AppleScript for getEmbeddedSheetsAndScriptsOf', function () {
    DEVONthink::getEmbeddedSheetsAndScriptsOf('record-uuid');

    expect(DEVONthink::lastScript())
        ->toContain('set theRecords to get embedded sheets and scripts of theRecord');
});

it('generates AppleScript for getFramesOf', function () {
    DEVONthink::getFramesOf('record-uuid');

    expect(DEVONthink::lastScript())
        ->toContain('set theRecords to get frames of theRecord');
});

it('generates AppleScript for getFaviconOf', function () {
    DEVONthink::getFaviconOf('record-uuid');

    expect(DEVONthink::lastScript())
        ->toContain('return get favicon of theRecord');
});

// =========================================================================
// Custom Meta Data
// =========================================================================

it('generates AppleScript for addCustomMetaData with string value', function () {
    DEVONthink::addCustomMetaData('test value', 'myKey', 'record-uuid');

    expect(DEVONthink::lastScript())
        ->toContain('set theRecord to get record with uuid "record-uuid"')
        ->toContain('add custom meta data "test value" for "myKey" to theRecord');
});

it('generates AppleScript for addCustomMetaData with boolean value', function () {
    DEVONthink::addCustomMetaData(true, 'myFlag', 'record-uuid');

    expect(DEVONthink::lastScript())
        ->toContain('add custom meta data true for "myFlag" to theRecord');
});

it('generates AppleScript for addCustomMetaData with numeric value', function () {
    DEVONthink::addCustomMetaData(42, 'count', 'record-uuid');

    expect(DEVONthink::lastScript())
        ->toContain('add custom meta data 42 for "count" to theRecord');
});

it('generates AppleScript for getCustomMetaData', function () {
    DEVONthink::getCustomMetaData('myKey', 'record-uuid');

    expect(DEVONthink::lastScript())
        ->toContain('return get custom meta data for "myKey" from theRecord');
});

// =========================================================================
// Search & Lookup
// =========================================================================

it('generates AppleScript for search', function () {
    DEVONthink::search('test query');

    expect(DEVONthink::lastScript())
        ->toContain('set theRecords to search "test query"')
        ->toContain('set theResult to theResult & (uuid of r as text)');
});

it('generates AppleScript for search with all options', function () {
    DEVONthink::search('query', 'group-uuid', 'fuzzy', true);

    expect(DEVONthink::lastScript())
        ->toContain('search "query" comparison fuzzy exclude subgroups true in (get record with uuid "group-uuid")');
});

it('generates AppleScript for lookupRecordsWithComment', function () {
    DEVONthink::lookupRecordsWithComment('my comment');

    expect(DEVONthink::lastScript())
        ->toContain('set theRecords to lookup records with comment "my comment"');
});

it('generates AppleScript for lookupRecordsWithContentHash', function () {
    DEVONthink::lookupRecordsWithContentHash('abc123');

    expect(DEVONthink::lastScript())
        ->toContain('lookup records with content hash "abc123"');
});

it('generates AppleScript for lookupRecordsWithFile', function () {
    DEVONthink::lookupRecordsWithFile('/path/to/file.pdf');

    expect(DEVONthink::lastScript())
        ->toContain('lookup records with file "/path/to/file.pdf"');
});

it('generates AppleScript for lookupRecordsWithPath', function () {
    DEVONthink::lookupRecordsWithPath('/Group/Subgroup');

    expect(DEVONthink::lastScript())
        ->toContain('lookup records with path "/Group/Subgroup"');
});

it('generates AppleScript for lookupRecordsWithTags', function () {
    DEVONthink::lookupRecordsWithTags(['tag1', 'tag2']);

    expect(DEVONthink::lastScript())
        ->toContain('lookup records with tags {"tag1", "tag2"}');
});

it('generates AppleScript for lookupRecordsWithUrl', function () {
    DEVONthink::lookupRecordsWithUrl('https://example.com');

    expect(DEVONthink::lastScript())
        ->toContain('lookup records with URL "https://example.com"');
});

it('generates AppleScript for exists', function () {
    DEVONthink::exists('record-uuid');

    expect(DEVONthink::lastScript())
        ->toContain('exists record id (id of (get record with uuid "record-uuid"))');
});

it('generates AppleScript for existsRecordAt', function () {
    DEVONthink::existsRecordAt('/path/to/record', 'My DB');

    expect(DEVONthink::lastScript())
        ->toContain('exists record at "/path/to/record" in database "My DB"');
});

it('generates AppleScript for classify', function () {
    DEVONthink::classify('record-uuid');

    expect(DEVONthink::lastScript())
        ->toContain('set theRecord to get record with uuid "record-uuid"')
        ->toContain('set theRecords to classify record theRecord');
});

it('generates AppleScript for compare', function () {
    DEVONthink::compare('uuid-1', 'uuid-2');

    expect(DEVONthink::lastScript())
        ->toContain('set theRecord to get record with uuid "uuid-1"')
        ->toContain('set theOther to get record with uuid "uuid-2"')
        ->toContain('return (compare record theRecord to theOther) as text');
});

it('generates AppleScript for count', function () {
    DEVONthink::count('group-uuid');

    expect(DEVONthink::lastScript())
        ->toContain('set theRecord to get record with uuid "group-uuid"')
        ->toContain('return (count of theRecord) as text');
});

// =========================================================================
// Import & Export
// =========================================================================

it('generates AppleScript for importPath', function () {
    DEVONthink::importPath('/path/to/file.pdf');

    expect(DEVONthink::lastScript())
        ->toContain('set theRecord to import "/path/to/file.pdf"')
        ->toContain('if theRecord is not missing value then return uuid of theRecord as text');
});

it('generates AppleScript for importPath with group', function () {
    DEVONthink::importPath('/path/to/file.pdf', 'group-uuid');

    expect(DEVONthink::lastScript())
        ->toContain('import "/path/to/file.pdf" to (get record with uuid "group-uuid")');
});

it('generates AppleScript for importTemplate', function () {
    DEVONthink::importTemplate('/path/to/template');

    expect(DEVONthink::lastScript())
        ->toContain('set theRecord to import template "/path/to/template"');
});

it('generates AppleScript for importAttachmentsOf', function () {
    DEVONthink::importAttachmentsOf('record-uuid');

    expect(DEVONthink::lastScript())
        ->toContain('set theRecord to get record with uuid "record-uuid"')
        ->toContain('set theRecords to import attachments of theRecord');
});

it('generates AppleScript for indexPath', function () {
    DEVONthink::indexPath('/path/to/folder', 'group-uuid');

    expect(DEVONthink::lastScript())
        ->toContain('set theRecord to index "/path/to/folder" to (get record with uuid "group-uuid")');
});

it('generates AppleScript for export', function () {
    DEVONthink::export('record-uuid', '/path/to/export');

    expect(DEVONthink::lastScript())
        ->toContain('set theRecord to get record with uuid "record-uuid"')
        ->toContain('return (export record theRecord to "/path/to/export") as text');
});

it('generates AppleScript for exportTagsOf', function () {
    DEVONthink::exportTagsOf('record-uuid');

    expect(DEVONthink::lastScript())
        ->toContain('return export tags of theRecord');
});

it('generates AppleScript for exportWebsite', function () {
    DEVONthink::exportWebsite('record-uuid', '/export/path');

    expect(DEVONthink::lastScript())
        ->toContain('return (export website record theRecord to "/export/path") as text');
});

// =========================================================================
// Web Capture & Download
// =========================================================================

it('generates AppleScript for createWebDocumentFrom', function () {
    DEVONthink::createWebDocumentFrom('https://example.com');

    expect(DEVONthink::lastScript())
        ->toContain('set theRecord to create web document from "https://example.com"')
        ->toContain('if theRecord is not missing value then return uuid of theRecord as text');
});

it('generates AppleScript for createMarkdownFrom', function () {
    DEVONthink::createMarkdownFrom('https://example.com', 'group-uuid');

    expect(DEVONthink::lastScript())
        ->toContain('create Markdown from "https://example.com" in (get record with uuid "group-uuid")');
});

it('generates AppleScript for createPdfDocumentFrom', function () {
    DEVONthink::createPdfDocumentFrom('https://example.com');

    expect(DEVONthink::lastScript())
        ->toContain('create PDF document from "https://example.com"');
});

it('generates AppleScript for createFormattedNoteFrom', function () {
    DEVONthink::createFormattedNoteFrom('https://example.com');

    expect(DEVONthink::lastScript())
        ->toContain('create formatted note from "https://example.com"');
});

it('generates AppleScript for downloadUrl', function () {
    DEVONthink::downloadUrl('https://example.com/file.pdf');

    expect(DEVONthink::lastScript())
        ->toContain('set theRecord to download URL "https://example.com/file.pdf"');
});

it('generates AppleScript for downloadMarkupFrom', function () {
    DEVONthink::downloadMarkupFrom('https://example.com');

    expect(DEVONthink::lastScript())
        ->toContain('return download markup from "https://example.com"');
});

it('generates AppleScript for addDownload', function () {
    DEVONthink::addDownload('https://example.com/file.zip');

    expect(DEVONthink::lastScript())
        ->toContain('add download "https://example.com/file.zip"');
});

it('generates AppleScript for addReadingList', function () {
    DEVONthink::addReadingList('https://example.com/article');

    expect(DEVONthink::lastScript())
        ->toContain('add reading list "https://example.com/article"');
});

it('generates AppleScript for getCachedDataForUrl', function () {
    DEVONthink::getCachedDataForUrl('https://example.com');

    expect(DEVONthink::lastScript())
        ->toContain('return get cached data for URL "https://example.com"');
});

it('generates AppleScript for startDownloads', function () {
    DEVONthink::startDownloads();

    expect(DEVONthink::lastScript())
        ->toContain('return (start downloads) as text');
});

it('generates AppleScript for stopDownloads', function () {
    DEVONthink::stopDownloads();

    expect(DEVONthink::lastScript())
        ->toContain('return (stop downloads) as text');
});

// =========================================================================
// Conversion
// =========================================================================

it('generates AppleScript for convert', function () {
    DEVONthink::convert('record-uuid', 'PDF document');

    expect(DEVONthink::lastScript())
        ->toContain('set theRecord to get record with uuid "record-uuid"')
        ->toContain('set theConverted to convert record theRecord to PDF document');
});

it('generates AppleScript for convert with group', function () {
    DEVONthink::convert('record-uuid', 'rich text', 'group-uuid');

    expect(DEVONthink::lastScript())
        ->toContain('convert record theRecord to rich text in (get record with uuid "group-uuid")');
});

it('generates AppleScript for convertFeedToHtml', function () {
    DEVONthink::convertFeedToHtml('feed-uuid');

    expect(DEVONthink::lastScript())
        ->toContain('return convert feed to HTML theRecord');
});

it('generates AppleScript for convertImage', function () {
    DEVONthink::convertImage('image-uuid');

    expect(DEVONthink::lastScript())
        ->toContain('set theConverted to convert image theRecord');
});

// =========================================================================
// Text Content
// =========================================================================

it('generates AppleScript for extractKeywordsFrom', function () {
    DEVONthink::extractKeywordsFrom('some text content');

    expect(DEVONthink::lastScript())
        ->toContain('return extract keywords from "some text content"');
});

// =========================================================================
// OCR
// =========================================================================

it('generates AppleScript for ocr', function () {
    DEVONthink::ocr('image-uuid');

    expect(DEVONthink::lastScript())
        ->toContain('set theRecord to get record with uuid "image-uuid"')
        ->toContain('set theOcr to ocr theRecord');
});

it('generates AppleScript for ocr with group', function () {
    DEVONthink::ocr('image-uuid', 'group-uuid');

    expect(DEVONthink::lastScript())
        ->toContain('ocr theRecord in (get record with uuid "group-uuid")');
});

// =========================================================================
// Database Operations
// =========================================================================

it('generates AppleScript for createDatabase', function () {
    DEVONthink::createDatabase('/path/to/new.dtBase2');

    expect(DEVONthink::lastScript())
        ->toContain('set theDatabase to create database "/path/to/new.dtBase2"')
        ->toContain('if theDatabase is not missing value then return name of theDatabase as text');
});

it('generates AppleScript for openDatabase', function () {
    DEVONthink::openDatabase('/path/to/db.dtBase2');

    expect(DEVONthink::lastScript())
        ->toContain('set theDatabase to open database "/path/to/db.dtBase2"');
});

it('generates AppleScript for optimize', function () {
    DEVONthink::optimize('My Database');

    expect(DEVONthink::lastScript())
        ->toContain('return (optimize database "My Database") as text');
});

it('generates AppleScript for verify', function () {
    DEVONthink::verify('My Database');

    expect(DEVONthink::lastScript())
        ->toContain('return (verify database "My Database") as text');
});

it('generates AppleScript for checkFileIntegrityOf', function () {
    DEVONthink::checkFileIntegrityOf('record-uuid');

    expect(DEVONthink::lastScript())
        ->toContain('return (check file integrity of theRecord) as text');
});

it('generates AppleScript for compress', function () {
    DEVONthink::compress('My Database');

    expect(DEVONthink::lastScript())
        ->toContain('return (compress database "My Database") as text');
});

it('generates AppleScript for synchronize', function () {
    DEVONthink::synchronize('record-uuid', 'My Database');

    expect(DEVONthink::lastScript())
        ->toContain('return (synchronize record (get record with uuid "record-uuid") database "My Database") as text');
});

it('generates AppleScript for createLocation', function () {
    DEVONthink::createLocation('/Group/Subgroup', 'My Database');

    expect(DEVONthink::lastScript())
        ->toContain('set theRecord to create location "/Group/Subgroup" in database "My Database"');
});

// =========================================================================
// Thumbnails
// =========================================================================

it('generates AppleScript for createThumbnail', function () {
    DEVONthink::createThumbnail('record-uuid');

    expect(DEVONthink::lastScript())
        ->toContain('return (create thumbnail of theRecord) as text');
});

it('generates AppleScript for deleteThumbnail', function () {
    DEVONthink::deleteThumbnail('record-uuid');

    expect(DEVONthink::lastScript())
        ->toContain('return (delete thumbnail of theRecord) as text');
});

it('generates AppleScript for updateThumbnail', function () {
    DEVONthink::updateThumbnail('record-uuid');

    expect(DEVONthink::lastScript())
        ->toContain('return (update thumbnail of theRecord) as text');
});

// =========================================================================
// Versions
// =========================================================================

it('generates AppleScript for getVersionsOf', function () {
    DEVONthink::getVersionsOf('record-uuid');

    expect(DEVONthink::lastScript())
        ->toContain('set theRecords to get versions of record theRecord');
});

it('generates AppleScript for saveVersionOf', function () {
    DEVONthink::saveVersionOf('record-uuid');

    expect(DEVONthink::lastScript())
        ->toContain('set theVersion to save version of record theRecord')
        ->toContain('if theVersion is not missing value then return uuid of theVersion as text');
});

it('generates AppleScript for restoreRecordWith', function () {
    DEVONthink::restoreRecordWith('version-uuid');

    expect(DEVONthink::lastScript())
        ->toContain('set theVersion to get record with uuid "version-uuid"')
        ->toContain('return (restore record with version theVersion) as text');
});

// =========================================================================
// AI & Chat (DEVONthink 4)
// =========================================================================

it('generates AppleScript for getChatCapabilitiesForEngine', function () {
    DEVONthink::getChatCapabilitiesForEngine('openai');

    expect(DEVONthink::lastScript())
        ->toContain('return get chat capabilities for engine "openai"');
});

it('generates AppleScript for getChatModelsForEngine', function () {
    DEVONthink::getChatModelsForEngine('anthropic');

    expect(DEVONthink::lastScript())
        ->toContain('return get chat models for engine "anthropic"');
});

it('generates AppleScript for getChatResponseForMessage', function () {
    DEVONthink::getChatResponseForMessage('Hello world');

    expect(DEVONthink::lastScript())
        ->toContain('return get chat response for message "Hello world"');
});

it('generates AppleScript for getChatResponseForMessage with engine and model', function () {
    DEVONthink::getChatResponseForMessage('Hello', 'openai', 'gpt-4');

    expect(DEVONthink::lastScript())
        ->toContain('get chat response for message "Hello" engine "openai" model "gpt-4"');
});

it('generates AppleScript for downloadImageForPrompt', function () {
    DEVONthink::downloadImageForPrompt('a sunset', 'dall-e');

    expect(DEVONthink::lastScript())
        ->toContain('return download image for prompt "a sunset" engine "dall-e"');
});

it('generates AppleScript for summarizeText', function () {
    DEVONthink::summarizeText('Long text here', 'bullet points');

    expect(DEVONthink::lastScript())
        ->toContain('return summarize text "Long text here" as bullet points');
});

it('generates AppleScript for summarizeAnnotationsOf', function () {
    DEVONthink::summarizeAnnotationsOf(['uuid-1', 'uuid-2'], SummaryType::Markdown);

    expect(DEVONthink::lastScript())
        ->toContain('summarize annotations of records {(get record with uuid "uuid-1"), (get record with uuid "uuid-2")} to markdown');
});

it('generates AppleScript for summarizeContentsOf', function () {
    DEVONthink::summarizeContentsOf(['uuid-1'], SummaryType::Rich, 'brief');

    expect(DEVONthink::lastScript())
        ->toContain('summarize contents of records {(get record with uuid "uuid-1")} to rich as brief');
});

it('generates AppleScript for summarizeMentionsOf', function () {
    DEVONthink::summarizeMentionsOf(['uuid-1'], SummaryType::Sheet, 'group-uuid');

    expect(DEVONthink::lastScript())
        ->toContain('summarize mentions of records {(get record with uuid "uuid-1")} to sheet in (get record with uuid "group-uuid")');
});

it('generates AppleScript for transcribe', function () {
    DEVONthink::transcribe('audio-uuid', 'en', true);

    expect(DEVONthink::lastScript())
        ->toContain('set theRecord to get record with uuid "audio-uuid"')
        ->toContain('return transcribe record theRecord language "en" timestamps true');
});

// =========================================================================
// Feeds
// =========================================================================

it('generates AppleScript for getFeedItemsOf', function () {
    DEVONthink::getFeedItemsOf('feed-uuid');

    expect(DEVONthink::lastScript())
        ->toContain('set theRecords to get feed items of theRecord');
});

it('generates AppleScript for getItemsOfFeed', function () {
    DEVONthink::getItemsOfFeed('feed-uuid');

    expect(DEVONthink::lastScript())
        ->toContain('set theRecords to get items of feed theRecord');
});

it('generates AppleScript for refresh', function () {
    DEVONthink::refresh('feed-uuid');

    expect(DEVONthink::lastScript())
        ->toContain('return (refresh record theRecord) as text');
});

// =========================================================================
// Sheets
// =========================================================================

it('generates AppleScript for addRow', function () {
    DEVONthink::addRow('sheet-uuid');

    expect(DEVONthink::lastScript())
        ->toContain('set theRecord to get record with uuid "sheet-uuid"')
        ->toContain('add row to theRecord');
});

it('generates AppleScript for deleteRowAt', function () {
    DEVONthink::deleteRowAt(3, 'sheet-uuid');

    expect(DEVONthink::lastScript())
        ->toContain('delete row at 3 of theRecord');
});

it('generates AppleScript for getCellAt', function () {
    DEVONthink::getCellAt(2, 5, 'sheet-uuid');

    expect(DEVONthink::lastScript())
        ->toContain('return get cell at column 2 row 5 of theRecord');
});

it('generates AppleScript for setCellAt', function () {
    DEVONthink::setCellAt(1, 3, 'new value', 'sheet-uuid');

    expect(DEVONthink::lastScript())
        ->toContain('return (set cell at column 1 row 3 to "new value" of theRecord) as text');
});

// =========================================================================
// UI Dialogs
// =========================================================================

it('generates AppleScript for displayAuthenticationDialog', function () {
    DEVONthink::displayAuthenticationDialog('Enter credentials', 'Login');

    expect(DEVONthink::lastScript())
        ->toContain('display authentication dialog "Enter credentials" with title "Login"')
        ->toContain('return (name of theResult) & linefeed & (password of theResult)');
});

it('generates AppleScript for displayChatDialog', function () {
    DEVONthink::displayChatDialog('Ask something', 'Chat');

    expect(DEVONthink::lastScript())
        ->toContain('return display chat dialog "Ask something" with title "Chat"');
});

it('generates AppleScript for displayDateEditor', function () {
    DEVONthink::displayDateEditor('2024-01-01', 'Pick date');

    expect(DEVONthink::lastScript())
        ->toContain('return display date editor "2024-01-01" with title "Pick date"');
});

it('generates AppleScript for displayGroupSelector', function () {
    DEVONthink::displayGroupSelector('Choose group', 'Select', 'My DB');

    expect(DEVONthink::lastScript())
        ->toContain('display group selector "Choose group" with title "Select" for database "My DB"')
        ->toContain('if theGroup is not missing value then return uuid of theGroup as text');
});

it('generates AppleScript for displayNameEditor', function () {
    DEVONthink::displayNameEditor('Enter name', 'Name', 'Default');

    expect(DEVONthink::lastScript())
        ->toContain('return display name editor "Enter name" with title "Name" default name "Default"');
});

// =========================================================================
// UI Windows & Tabs
// =========================================================================

it('generates AppleScript for openTabFor', function () {
    DEVONthink::openTabFor('record-uuid', 'https://example.com', 'https://referrer.com');

    expect(DEVONthink::lastScript())
        ->toContain('open tab for record (get record with uuid "record-uuid") URL "https://example.com" referrer "https://referrer.com"');
});

it('generates AppleScript for openWindowFor', function () {
    DEVONthink::openWindowFor('record-uuid', true);

    expect(DEVONthink::lastScript())
        ->toContain('set theRecord to get record with uuid "record-uuid"')
        ->toContain('open window for record theRecord force true');
});

it('generates AppleScript for showSearch', function () {
    DEVONthink::showSearch('my query');

    expect(DEVONthink::lastScript())
        ->toContain('show search "my query"');
});

// =========================================================================
// Progress Indicator
// =========================================================================

it('generates AppleScript for showProgressIndicator', function () {
    DEVONthink::showProgressIndicator('Processing', true, 10);

    expect(DEVONthink::lastScript())
        ->toContain('show progress indicator "Processing" cancel button true steps 10');
});

it('generates AppleScript for stepProgressIndicator', function () {
    DEVONthink::stepProgressIndicator('Step 1 of 10');

    expect(DEVONthink::lastScript())
        ->toContain('return (step progress indicator "Step 1 of 10") as text');
});

it('generates AppleScript for hideProgressIndicator', function () {
    DEVONthink::hideProgressIndicator();

    expect(DEVONthink::lastScript())
        ->toContain('return (hide progress indicator) as text');
});

// =========================================================================
// Workspaces
// =========================================================================

it('generates AppleScript for saveWorkspace', function () {
    DEVONthink::saveWorkspace('My Workspace');

    expect(DEVONthink::lastScript())
        ->toContain('return (save workspace "My Workspace") as text');
});

it('generates AppleScript for loadWorkspace', function () {
    DEVONthink::loadWorkspace('My Workspace');

    expect(DEVONthink::lastScript())
        ->toContain('load workspace "My Workspace"');
});

it('generates AppleScript for deleteWorkspace', function () {
    DEVONthink::deleteWorkspace('Old Workspace');

    expect(DEVONthink::lastScript())
        ->toContain('delete workspace "Old Workspace"');
});

// =========================================================================
// Smart Rules
// =========================================================================

it('generates AppleScript for performSmartRule', function () {
    DEVONthink::performSmartRule('My Rule', 'record-uuid', 'on demand');

    expect(DEVONthink::lastScript())
        ->toContain('perform smart rule name "My Rule" record (get record with uuid "record-uuid") trigger on demand');
});

// =========================================================================
// Imprinting
// =========================================================================

it('generates AppleScript for imprint', function () {
    DEVONthink::imprint('record-uuid', 'My Config');

    expect(DEVONthink::lastScript())
        ->toContain('set theRecord to get record with uuid "record-uuid"')
        ->toContain('imprint record theRecord configuration "My Config"');
});

it('generates AppleScript for imprintConfiguration', function () {
    DEVONthink::imprintConfiguration('My Config');

    expect(DEVONthink::lastScript())
        ->toContain('return imprint configuration "My Config"');
});

it('generates AppleScript for imprinterConfigurationNames', function () {
    DEVONthink::imprinterConfigurationNames();

    expect(DEVONthink::lastScript())
        ->toContain('set theRecords to imprinter configuration names');
});

// =========================================================================
// Misc Operations
// =========================================================================

it('generates AppleScript for addReminder', function () {
    DEVONthink::addReminder('record-uuid', '2024-12-25');

    expect(DEVONthink::lastScript())
        ->toContain('set theRecord to get record with uuid "record-uuid"')
        ->toContain('add reminder to theRecord alarm "2024-12-25"');
});

it('generates AppleScript for logMessage', function () {
    DEVONthink::logMessage('Processing complete', 'Details here');

    expect(DEVONthink::lastScript())
        ->toContain('log message "Processing complete" info "Details here"');
});

it('generates AppleScript for pasteClipboard', function () {
    DEVONthink::pasteClipboard('group-uuid');

    expect(DEVONthink::lastScript())
        ->toContain('set theRecord to paste clipboard to (get record with uuid "group-uuid")')
        ->toContain('if theRecord is not missing value then return uuid of theRecord as text');
});

it('generates AppleScript for make', function () {
    DEVONthink::make('document', ['name' => 'New Doc', 'type' => 'markdown'], 'group-uuid');

    expect(DEVONthink::lastScript())
        ->toContain('set theRecord to make new document at (get record with uuid "group-uuid") with properties {name:"New Doc", type:"markdown"}');
});

// =========================================================================
// String Escaping
// =========================================================================

it('escapes special characters in generated scripts', function () {
    DEVONthink::search('test "quoted" and back\\slash');

    expect(DEVONthink::lastScript())
        ->toContain('search "test \\"quoted\\" and back\\\\slash"');
});

// =========================================================================
// Script Structure
// =========================================================================

it('wraps all scripts with the standard AppleScript intro and tell block', function () {
    DEVONthink::delete('any-uuid');

    expect(DEVONthink::lastScript())
        ->toContain('use AppleScript version "2.8"')
        ->toContain('use scripting additions')
        ->toContain('tell application id "DNtp"')
        ->toContain('end tell');
});
