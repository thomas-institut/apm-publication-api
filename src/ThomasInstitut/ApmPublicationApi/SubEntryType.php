<?php

namespace ThomasInstitut\ApmPublicationApi;

enum SubEntryType: string
{
    case Empty = 'empty';
    case Addition = 'addition';
    case Omission = 'omission';
    case Variant = 'variant';
    case FullCustom = 'fullCustom';
    case Auto = 'auto';
    case AutoFoliation = 'autoFoliation';
}