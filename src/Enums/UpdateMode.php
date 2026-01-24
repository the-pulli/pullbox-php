<?php

namespace Pulli\Pullbox\Enums;

enum UpdateMode: string
{
    case Appending = 'appending';
    case Inserting = 'inserting';
    case Replacing = 'replacing';
}
