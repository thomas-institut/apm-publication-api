<?php

namespace ThomasInstitut\FmtText;

/**
 * Allowed values for `FmtTextTextToken::$textDirection`.
 *
 * Mirrors the TS type `'' | 'ltr' | 'rtl'`.
 */
enum TextDirection: string
{
    case Default = '';
    case Ltr = 'ltr';
    case Rtl = 'rtl';
}
