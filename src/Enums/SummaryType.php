<?php

namespace Pulli\Pullbox\Enums;

enum SummaryType: string
{
    case Markdown = 'markdown';
    case Rich = 'rich';
    case Sheet = 'sheet';
    case Simple = 'simple';
}
