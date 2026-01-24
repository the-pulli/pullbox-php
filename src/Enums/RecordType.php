<?php

namespace Pulli\Pullbox\Enums;

enum RecordType: string
{
    case Bookmark = 'bookmark';
    case Feed = 'feed';
    case FormattedNote = 'formatted note';
    case Group = 'group';
    case HTML = 'html';
    case Markdown = 'markdown';
    case Picture = 'picture';
    case Plist = 'plist';
    case Quicktime = 'quicktime';
    case RTF = 'rtf';
    case RTFD = 'rtfd';
    case Script = 'script';
    case Sheet = 'sheet';
    case SmartGroup = 'smart group';
    case Text = 'txt';
    case WebArchive = 'web archive';
    case Unknown = 'unknown';
}
