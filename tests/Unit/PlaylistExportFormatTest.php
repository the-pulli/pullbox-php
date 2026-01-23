<?php

use Pulli\Pullbox\Enums\PlaylistExportFormat;

it('can resolve format from string input', function (string $input, PlaylistExportFormat $expected) {
    expect(PlaylistExportFormat::fromInput($input))
        ->toBe($expected);
})->with([
    'm3u' => ['m3u', PlaylistExportFormat::M3U],
    'm3u8' => ['m3u8', PlaylistExportFormat::M3U8],
    'plain_text' => ['plain_text', PlaylistExportFormat::PlainText],
    'unicode_text' => ['unicode_text', PlaylistExportFormat::UnicodeText],
    'xml' => ['xml', PlaylistExportFormat::XML],
    'unknown defaults to xml' => ['unknown', PlaylistExportFormat::XML],
    'empty defaults to xml' => ['', PlaylistExportFormat::XML],
]);

it('returns the same instance when given a PlaylistExportFormat', function () {
    $format = PlaylistExportFormat::M3U;

    expect(PlaylistExportFormat::fromInput($format))
        ->toBe($format);
});

it('has correct AppleScript values', function (PlaylistExportFormat $format, string $expected) {
    expect($format->value)->toBe($expected);
})->with([
    'M3U' => [PlaylistExportFormat::M3U, 'M3U'],
    'M3U8' => [PlaylistExportFormat::M3U8, 'M3U8'],
    'PlainText' => [PlaylistExportFormat::PlainText, 'plain text'],
    'UnicodeText' => [PlaylistExportFormat::UnicodeText, 'Unicode text'],
    'XML' => [PlaylistExportFormat::XML, 'XML'],
]);
