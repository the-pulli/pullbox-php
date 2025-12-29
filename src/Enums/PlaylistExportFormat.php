<?php

namespace Pulli\Pullbox\Enums;

enum PlaylistExportFormat: string
{
    case M3U = 'M3U';
    case M3U8 = 'M3U8';
    case PlainText = 'plain text';
    case UnicodeText = 'Unicode text';
    case XML = 'XML';

    public static function fromInput(string|PlaylistExportFormat $format): self
    {
        if ($format instanceof PlaylistExportFormat) {
            return $format;
        }

        return match ($format) {
            'm3u' => self::M3U,
            'm3u8' => self::M3U8,
            'plain_text' => self::PlainText,
            'unicode_text' => self::UnicodeText,
            default => self::XML,
        };
    }
}
